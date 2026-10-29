import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import Workspace, { BackendAccount } from './components/Workspace';
import './styles.css';
const configuredAccountUrl = import.meta.env.VITE_ELANG_ACCOUNT_URL || 'http://127.0.0.1:8000/dashboard';
function accountUrl() { try { const url = new URL(configuredAccountUrl); return ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password ? url.href : 'http://127.0.0.1:8000/dashboard'; } catch { return 'http://127.0.0.1:8000/dashboard'; } }
createRoot(document.getElementById('root')!).render(<StrictMode>{window.location.pathname === '/backend-account' ? <BackendAccount accountUrl={accountUrl()} /> : <Workspace />}</StrictMode>);
