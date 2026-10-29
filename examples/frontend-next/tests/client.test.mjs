import test from 'node:test';
import assert from 'node:assert/strict';
import { createElangClient, ApiError } from '../lib/elang-client.ts';
const signedOut = token => ({ user: null, csrfToken: token, registration: true, mailAvailable: true });
const signedIn = token => ({ ...signedOut(token), user: { id: 1, name: 'Ada', email: 'ada@example.com', verified: true, role: 'user' } });
function mockRequests(t, responses) {
  const calls = []; const original = globalThis.fetch;
  globalThis.fetch = async (path, init) => {
    calls.push({ path, ...init });
    assert.ok(responses.length, `Unexpected request: ${path}`);
    const response = responses.shift();
    if (response instanceof Error) throw response;
    return response;
  };
  t.after(() => { globalThis.fetch = original; assert.equal(responses.length, 0, 'All expected requests should occur'); });
  return calls;
}
test('login and MFA refresh the session and use each newly rotated CSRF token', async t => {
  const calls = mockRequests(t, [Response.json(signedOut('initial')), Response.json({ mfaRequired: true }, { status: 202 }), Response.json(signedOut('mfa-token')), Response.json({ user: signedIn('').user }), Response.json(signedIn('signed-in'))]);
  const states = []; const client = createElangClient(state => states.push(state));
  await client.session(); assert.equal((await client.login('ada@example.com', 'a-long-password')).mfaRequired, true); await client.mfa('123456');
  assert.equal(calls[1].headers['X-CSRF-Token'], 'initial'); assert.equal(JSON.parse(calls[1].body)._token, 'initial');
  assert.equal(calls[3].headers['X-CSRF-Token'], 'mfa-token'); assert.equal(JSON.parse(calls[3].body).code, '123456');
  assert.equal(states.at(-1).user.id, 1);
  assert.ok(calls.every(call => call.credentials === 'include' && call.cache === 'no-store'));
  assert.ok(calls.every(call => !('Authorization' in call.headers)));
});
test('note writes carry a CSRF header and refresh even after a validation error', async t => {
  const calls = mockRequests(t, [Response.json(signedIn('first')), Response.json({ message: 'Invalid input', errors: { title: ['Title is required.'] } }, { status: 422 }), Response.json(signedIn('second')), Response.json({ data: { id: 2, title: 'Idea', body: '' } }, { status: 201 }), Response.json(signedIn('third'))]);
  const client = createElangClient(() => {}); await client.session();
  await assert.rejects(client.createNote({ title: '', body: '' }), error => error instanceof ApiError && error.status === 422 && error.errors.title[0] === 'Title is required.');
  await client.createNote({ title: 'Idea', body: '' });
  assert.equal(calls.filter(call => call.method === 'POST').length, 2, 'No automatic write retry');
  assert.equal(calls[3].headers['X-CSRF-Token'], 'second'); assert.deepEqual(JSON.parse(calls[3].body), { title: 'Idea', body: '' });
});
test('401 reads refresh account status without replaying the failed request', async t => {
  const calls = mockRequests(t, [Response.json(signedIn('active')), Response.json({ message: 'Sign in first.' }, { status: 401 }), Response.json(signedOut('new-session'))]);
  const states = []; const client = createElangClient(state => states.push(state)); await client.session();
  await assert.rejects(client.notes(2), error => error.status === 401); assert.equal(states.at(-1).user, null); assert.equal(calls.filter(call => call.path === '/api/notes?page=2').length, 1);
});
test('a failed post-write refresh never reuses the previous CSRF token', async t => {
  const calls = mockRequests(t, [Response.json(signedIn('old')), Response.json({ data: { id: 1, title: 'Idea', body: '' } }, { status: 201 }), new TypeError('network unavailable'), Response.json(signedIn('fresh')), new Response(null, { status: 204 }), Response.json(signedIn('last'))]);
  const warnings = []; const client = createElangClient(() => {}, warning => warnings.push(warning)); await client.session();
  await client.createNote({ title: 'Idea', body: '' }); await client.deleteNote(1);
  assert.ok(warnings.some(Boolean)); assert.equal(calls[4].headers['X-CSRF-Token'], 'fresh'); assert.equal(calls[3].path, '/api/session');
});
test('aborted requests remain aborts and logout refreshes the signed-out state', async t => {
  mockRequests(t, [new DOMException('Aborted', 'AbortError'), Response.json(signedIn('active')), new Response(null, { status: 204 }), Response.json(signedOut('logged-out'))]);
  const states = []; const client = createElangClient(state => states.push(state));
  await assert.rejects(client.session(), error => error.name === 'AbortError'); await client.session(); await client.logout(); assert.equal(states.at(-1).user, null);
});
