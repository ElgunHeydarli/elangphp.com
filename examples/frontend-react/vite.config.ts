import { defineConfig, loadEnv, type Plugin, type ProxyOptions } from 'vite';
import react from '@vitejs/plugin-react';
const apiPath = /^\/api\/(?:session(?:\/(?:login|register|mfa|logout))?|notes(?:\/[1-9][0-9]*)?)$/;
function restrictApiPaths(): Plugin {
  const install = (server: { middlewares: { use: (handler: (req: { url?: string; method?: string; headers: { host?: string; origin?: string } }, res: { statusCode: number; setHeader: (name: string, value: string) => void; end: (body: string) => void }, next: () => void) => void) => void } }) => {
    server.middlewares.use((request, response, next) => {
      const path = new URL(request.url || '/', 'http://localhost').pathname;
      if (path.startsWith('/api') && !apiPath.test(path)) { response.statusCode = 404; response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({ message: 'API route not found.' })); return; }
      if (apiPath.test(path) && !['GET', 'HEAD', 'OPTIONS'].includes(request.method || 'GET') && request.headers.origin) {
        let origin: string;
        try { origin = new URL(`http://${request.headers.host}`).origin; }
        catch { response.statusCode = 400; response.end(JSON.stringify({ message: 'Invalid request host.' })); return; }
        if (request.headers.origin !== origin) { response.statusCode = 403; response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({ message: 'Cross-origin requests are not allowed.' })); return; }
      }
      next();
    });
  };
  return { name: 'elang-api-allowlist', configureServer: install, configurePreviewServer: install };
}
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), 'ELANG_');
  const backend = new URL(process.env.ELANG_BACKEND_URL || env.ELANG_BACKEND_URL || 'http://127.0.0.1:8000');
  if (!['http:', 'https:'].includes(backend.protocol) || backend.username || backend.password || backend.pathname !== '/' || backend.search || backend.hash) throw new Error('ELANG_BACKEND_URL must be a trusted HTTP(S) origin without credentials or a path.');
  const proxy: Record<string, ProxyOptions> = { '/api': { target: backend.origin, changeOrigin: true, cookieDomainRewrite: '', configure(proxy) { proxy.on('proxyReq', request => request.removeHeader('origin')); } } };
  return { plugins: [react(), restrictApiPaths()], server: { port: 5173, strictPort: true, proxy }, preview: { proxy }, build: { sourcemap: false } };
});
