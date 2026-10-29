/** Server-only configuration. Never import this module into a client component. */
import 'server-only';
export function backendOrigin(): URL {
  const url = new URL(process.env.ELANG_BACKEND_URL || 'http://127.0.0.1:8000');
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password || url.pathname !== '/' || url.search || url.hash) throw new Error('ELANG_BACKEND_URL must be a trusted HTTP(S) origin without credentials or a path.');
  return url;
}
export function accountUrl(): string {
  const url = new URL(process.env.ELANG_ACCOUNT_URL || '/dashboard', backendOrigin());
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) throw new Error('ELANG_ACCOUNT_URL must be a public HTTP(S) URL.');
  return url.href;
}
