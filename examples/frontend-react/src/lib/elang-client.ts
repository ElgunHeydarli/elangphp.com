/** Typed, same-origin Elang session client. CSRF lives in memory; the session cookie stays HttpOnly. */
import { createApiClient, ApiResponseError } from './generated.ts';
import type { User, Session, Note, NoteInput, NotesPage } from './generated.ts';
export type { User, Session, Note, NoteInput, NotesPage } from './generated.ts';
export type SessionResult = Partial<Session>;
export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;
  constructor(message: string, status: number, errors: Record<string, string[]> = {}) { super(message); this.name = 'ApiError'; this.status = status; this.errors = errors; }
}
export function errorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    const details = Object.values(error.errors).flat().filter(value => typeof value === 'string');
    return details.length ? details.join(' ') : error.message;
  }
  return error instanceof Error ? error.message : 'Something went wrong. Please try again.';
}
export function createElangClient(onSession: (session: Session) => void, onSessionIssue: (message: string) => void = () => {}) {
  let csrfToken = '';
  const generated = createApiClient();
  async function sdk<K extends keyof import('./generated').Operations>(operation: K, input: import('./generated').Operations[K]['input']): Promise<import('./generated').Operations[K]['output']> {
    try { return await generated.request(operation,input); } catch(error) {
      if(error instanceof ApiResponseError) { const data=(error.payload && typeof error.payload==='object' ? error.payload : {}) as { message?:string; errors?:Record<string,string[]> };throw new ApiError(data.message || error.message,error.status,data.errors); }
      throw error;
    }
  }
  async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
    let response: Response;
    try { response = await fetch(path, { ...init, credentials: 'include', cache: 'no-store', headers: { Accept: 'application/json', ...init.headers } }); }
    catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') throw error;
      throw new ApiError('We could not reach your workspace. Check the connection and try again.', 0);
    }
    let data: unknown = null;
    if (response.status !== 204) {
      try { data = await response.json(); }
      catch { throw new ApiError('The backend returned an unreadable response. Check that Elang is running.', response.status); }
    }
    if (!response.ok) {
      const payload = (data && typeof data === 'object' ? data : {}) as { message?: string; errors?: Record<string, string[]> };
      throw new ApiError(payload.message || `The request could not be completed (HTTP ${response.status}).`, response.status, payload.errors);
    }
    return data as T;
  }
  async function session(signal?: AbortSignal): Promise<Session> {
    const state = await request<Session>('/api/session', { signal });
    if (typeof state.csrfToken !== 'string' || !('user' in state)) throw new ApiError('The session response is incomplete. Check the Elang session API.', 502);
    csrfToken = state.csrfToken; onSession(state); onSessionIssue(''); return state;
  }
  async function refreshAfterRequest(): Promise<void> {
    try { await session(); }
    catch { csrfToken = ''; onSessionIssue('Account status could not be refreshed. Refresh it before your next change.'); }
  }
  async function read<T>(path: string, signal?: AbortSignal): Promise<T> {
    try { return await request<T>(path, { signal }); }
    catch (error) { if (error instanceof ApiError && error.status === 401) await refreshAfterRequest(); throw error; }
  }
  async function write<T>(method: string, path: string, values: Record<string, unknown> = {}): Promise<T> {
    if (!csrfToken) await session();
    const json = path.startsWith('/api/session/') ? { ...values, _token: csrfToken } : values;
    try { return await request<T>(path, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(json) }); }
    finally { await refreshAfterRequest(); }
  }
  return {
    session,
    login: (email: string, password: string) => write<SessionResult>('POST', '/api/session/login', { email, password }),
    register: (name: string, email: string, password: string, password_confirmation: string) => write<SessionResult>('POST', '/api/session/register', { name, email, password, password_confirmation }),
    mfa: (code: string) => write<SessionResult>('POST', '/api/session/mfa', { code }),
    logout: () => write<void>('POST', '/api/session/logout'),
    notes: (page = 1, signal?: AbortSignal) => read<NotesPage>(`/api/notes?page=${page}`, signal),
    createNote: async (values: NoteInput) => { if(!csrfToken) await session();try { return await sdk('createNote',{body:values,headers:{'X-CSRF-Token':csrfToken}}); }finally { await refreshAfterRequest(); } },
    updateNote: async (id: number, values: NoteInput) => { if(!csrfToken) await session();try { return await sdk('updateNote',{path:{id},body:values,headers:{'X-CSRF-Token':csrfToken}}); }finally { await refreshAfterRequest(); } },
    deleteNote: async (id: number) => { if(!csrfToken) await session();try { return await sdk('deleteNote',{path:{id},headers:{'X-CSRF-Token':csrfToken}}); }finally { await refreshAfterRequest(); } },
  };
}
export type ElangClient = ReturnType<typeof createElangClient>;
