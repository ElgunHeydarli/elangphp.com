(() => {
  'use strict';
  const messages = (() => { try { return JSON.parse(document.body?.dataset.ui || '{}'); } catch { return {}; } })();
  const text = value => typeof messages[value] === 'string' ? messages[value] : value;
  let activeRequest = null;
  let passkeyBusy = false;
  const registrationForms = [...document.querySelectorAll('[data-passkey-register]')];
  const loginButtons = [...document.querySelectorAll('[data-passkey-login]')];
  const passkeyButtons = [...document.querySelectorAll('[data-passkey-submit]'), ...loginButtons];
  const supported = window.isSecureContext && typeof window.PublicKeyCredential === 'function' && typeof navigator.credentials?.create === 'function' && typeof navigator.credentials?.get === 'function';

  class AccountRequestError extends Error {
    constructor(status) { super('Account request could not be completed.'); this.name = 'AccountRequestError'; this.status = status; }
  }
  function statusFor(element, login = false) {
    const selector = login ? '[data-passkey-login-status]' : '[data-passkey-status]';
    const existing = (login ? element.closest('form') : element)?.querySelector(selector) || document.querySelector(selector);
    if (existing) return existing;
    const status = document.createElement('p'); status.className = 'form-hint security-copy-status'; status.setAttribute(login ? 'data-passkey-login-status' : 'data-passkey-status', '');
    element.insertAdjacentElement('afterend', status); return status;
  }
  function showStatus(element, message, error = false) {
    if (!element) return;
    element.textContent = text(message); element.hidden = false; element.setAttribute('role', error ? 'alert' : 'status'); element.setAttribute('aria-live', error ? 'assertive' : 'polite');
    element.classList.toggle('security-status-error', error);
  }
  function csrfFor(element) {
    const input = element.closest('form')?.querySelector('input[name="_token"]') || document.querySelector('input[name="_token"]');
    const token = element.dataset.csrfToken || input?.value || document.querySelector('meta[name="csrf-token"]')?.content;
    if (typeof token !== 'string' || !token) throw new AccountRequestError(419);
    return token;
  }
  function decodeBase64url(value) {
    if (typeof value !== 'string' || !/^[A-Za-z0-9_-]*={0,2}$/.test(value) || value.length % 4 === 1) throw new TypeError('Invalid credential encoding.');
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const binary = atob(normalized + '='.repeat((4 - normalized.length % 4) % 4));
    return Uint8Array.from(binary, character => character.charCodeAt(0)).buffer;
  }
  function encodeBase64url(value) {
    const bytes = ArrayBuffer.isView(value) ? new Uint8Array(value.buffer, value.byteOffset, value.byteLength) : new Uint8Array(value);
    let binary = ''; for (let offset = 0; offset < bytes.length; offset += 0x8000) binary += String.fromCharCode(...bytes.subarray(offset, offset + 0x8000));
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  }
  function encodeBinaryValues(value) {
    if (value instanceof ArrayBuffer || ArrayBuffer.isView(value)) return encodeBase64url(value);
    if (Array.isArray(value)) return value.map(encodeBinaryValues);
    if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, encodeBinaryValues(item)]));
    return value;
  }
  function extensionOptions(extensions) {
    if (!extensions || typeof extensions !== 'object') return extensions;
    const result = { ...extensions };
    if (typeof result.credBlob === 'string') result.credBlob = decodeBase64url(result.credBlob);
    if (result.largeBlob && typeof result.largeBlob.write === 'string') result.largeBlob = { ...result.largeBlob, write: decodeBase64url(result.largeBlob.write) };
    if (result.prf) {
      const decodeValues = values => ({ ...values, ...(typeof values.first === 'string' ? { first: decodeBase64url(values.first) } : {}), ...(typeof values.second === 'string' ? { second: decodeBase64url(values.second) } : {}) });
      result.prf = { ...result.prf };
      if (result.prf.eval) result.prf.eval = decodeValues(result.prf.eval);
      if (result.prf.evalByCredential) result.prf.evalByCredential = Object.fromEntries(Object.entries(result.prf.evalByCredential).map(([key, values]) => [key, decodeValues(values)]));
    }
    return result;
  }
  function publicKeyOptions(json, creation) {
    if (!json || typeof json !== 'object' || Array.isArray(json)) throw new TypeError('Invalid credential options.');
    const options = json.publicKey && typeof json.publicKey === 'object' ? json.publicKey : json;
    const nativeParser = creation ? window.PublicKeyCredential.parseCreationOptionsFromJSON : window.PublicKeyCredential.parseRequestOptionsFromJSON;
    if (typeof nativeParser === 'function') return nativeParser.call(window.PublicKeyCredential, options);
    const converted = { ...options, challenge: decodeBase64url(options.challenge) };
    const descriptors = list => list.map(item => ({ ...item, id: decodeBase64url(item.id) }));
    if (creation) { converted.user = { ...options.user, id: decodeBase64url(options.user?.id) }; if (options.excludeCredentials) converted.excludeCredentials = descriptors(options.excludeCredentials); }
    else if (options.allowCredentials) converted.allowCredentials = descriptors(options.allowCredentials);
    if (options.extensions) converted.extensions = extensionOptions(options.extensions);
    return converted;
  }
  function serializeCredential(credential) {
    if (!credential || credential.type !== 'public-key' || !credential.response || !credential.rawId) throw new TypeError('No public-key credential returned.');
    if (typeof credential.toJSON === 'function') return credential.toJSON();
    const response = credential.response;
    const serialized = { id: credential.id, rawId: encodeBase64url(credential.rawId), type: credential.type, response: { clientDataJSON: encodeBase64url(response.clientDataJSON) }, clientExtensionResults: encodeBinaryValues(credential.getClientExtensionResults?.() || {}) };
    if (credential.authenticatorAttachment) serialized.authenticatorAttachment = credential.authenticatorAttachment;
    if (response.attestationObject) {
      serialized.response.attestationObject = encodeBase64url(response.attestationObject);
      serialized.response.transports = typeof response.getTransports === 'function' ? response.getTransports() : [];
      if (typeof response.getAuthenticatorData === 'function') serialized.response.authenticatorData = encodeBase64url(response.getAuthenticatorData());
      if (typeof response.getPublicKeyAlgorithm === 'function') serialized.response.publicKeyAlgorithm = response.getPublicKeyAlgorithm();
      if (typeof response.getPublicKey === 'function') { const key = response.getPublicKey(); if (key) serialized.response.publicKey = encodeBase64url(key); }
    } else {
      serialized.response.authenticatorData = encodeBase64url(response.authenticatorData); serialized.response.signature = encodeBase64url(response.signature);
      if (response.userHandle !== null && response.userHandle !== undefined) serialized.response.userHandle = encodeBase64url(response.userHandle);
    }
    return serialized;
  }
  async function post(path, values, token, signal) {
    const allowed = ['/security/passkeys/options', '/security/passkeys/register', '/passkeys/options', '/passkeys/login'];
    if (!allowed.includes(path)) throw new AccountRequestError(400);
    let response;
    try { response = await fetch(path, { method: 'POST', credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': token }, body: JSON.stringify(values) }); }
    catch (error) { if (error?.name === 'AbortError') throw error; throw new AccountRequestError(0); }
    if (!response.ok) throw new AccountRequestError(response.status);
    try { return response.status === 204 ? {} : await response.json(); } catch { throw new AccountRequestError(502); }
  }
  function friendlyError(error) {
    if (error instanceof AccountRequestError) {
      if (error.status === 419 || error.status === 403) return 'Your session changed or this action could not be authorized. Reload the page and try again.';
      if (error.status === 429) return 'Too many attempts. Please wait before trying again.';
      if (error.status === 401 || error.status === 422) return 'Please check your account details and try again, or sign in with your password.';
      if (error.status === 0) return 'Could not reach the server. Check your connection and try again.';
      return 'The passkey request could not be completed. Reload the page and try again.';
    }
    if (error?.name === 'NotAllowedError' || error?.name === 'AbortError') return 'The passkey request was cancelled or timed out. Try again when you are ready.';
    if (error?.name === 'InvalidStateError') return 'This passkey may already be registered. Try another device or security key.';
    if (error?.name === 'NotSupportedError') return 'This browser or authenticator does not support the requested passkey.';
    if (error?.name === 'SecurityError') return 'Passkeys are not available at this address. Open the application at its configured HTTPS address.';
    return 'The passkey request could not be completed. Try again or use your password.';
  }
  function setBusy(busy) { passkeyBusy = busy; for (const button of passkeyButtons) { button.disabled = busy || !supported; button.setAttribute('aria-busy', String(busy)); } }
  function localRedirect(value) {
    if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//') || /[\\\u0000-\u0020]/.test(value)) return '/dashboard';
    try { const target = new URL(value, window.location.origin); return target.origin === window.location.origin ? target.pathname + target.search + target.hash : '/dashboard'; }
    catch { return '/dashboard'; }
  }
  for (const form of registrationForms) {
    const status = statusFor(form);
    showStatus(status, supported ? 'Your browser supports passkeys. Add one when you are ready.' : 'Passkeys require HTTPS or localhost in a compatible browser. You can still use your password.');
    form.addEventListener('submit', async event => {
      event.preventDefault(); if (!supported || passkeyBusy || !form.reportValidity()) return;
      setBusy(true); activeRequest = new AbortController();
      try {
        const token = csrfFor(form); const values = new FormData(form);
        showStatus(status, 'Confirm the passkey request on your device.');
        const result = await post('/security/passkeys/options', { current_password: String(values.get('current_password') || ''), name: String(values.get('name') || '') }, token, activeRequest.signal);
        const credential = await navigator.credentials.create({ publicKey: publicKeyOptions(result.options, true), signal: activeRequest.signal });
        await post('/security/passkeys/register', { credential: serializeCredential(credential) }, token, activeRequest.signal);
        showStatus(status, 'Passkey added. Refreshing your security settings…'); window.location.reload();
      } catch (error) { showStatus(status, friendlyError(error), true); }
      finally { const password = form.querySelector('input[name="current_password"]'); if (password) password.value = ''; activeRequest = null; setBusy(false); }
    });
  }
  for (const button of loginButtons) {
    const status = statusFor(button, true);
    if (!supported) showStatus(status, 'Passkeys require HTTPS or localhost in a compatible browser. You can still use your password.');
    button.addEventListener('click', async event => {
      event.preventDefault(); if (!supported || passkeyBusy) return;
      setBusy(true); activeRequest = new AbortController();
      try {
        const token = csrfFor(button); showStatus(status, 'Choose a passkey to continue.');
        const options = await post('/passkeys/options', {}, token, activeRequest.signal);
        const credential = await navigator.credentials.get({ publicKey: publicKeyOptions(options.options, false), signal: activeRequest.signal });
        const result = await post('/passkeys/login', { credential: serializeCredential(credential) }, token, activeRequest.signal);
        showStatus(status, 'Signed in. Opening your account…'); window.location.assign(localRedirect(result.redirect));
      } catch (error) { showStatus(status, friendlyError(error), true); }
      finally { activeRequest = null; setBusy(false); }
    });
  }
  setBusy(false);
  window.addEventListener('pagehide', () => activeRequest?.abort());

  async function copyText(value) {
    if (navigator.clipboard?.writeText) { try { await navigator.clipboard.writeText(value); return; } catch { /* A denied clipboard can still permit explicit selection copying. */ } }
    const previous = document.activeElement; const field = document.createElement('textarea'); field.className = 'sr-only'; field.value = value; field.setAttribute('readonly', ''); document.body.appendChild(field); field.select();
    try { if (!document.execCommand('copy')) throw new Error('Copy unavailable.'); }
    finally { field.remove(); previous?.focus({ preventScroll: true }); }
  }
  for (const button of document.querySelectorAll('[data-security-copy]')) button.addEventListener('click', async () => {
    const target = document.getElementById(button.dataset.copyTarget || ''); const status = button.closest('details')?.querySelector('[data-copy-status]'); if (!target) return;
    try { await copyText(target.textContent.trim()); showStatus(status, 'Copied. Keep this setup key private.'); }
    catch { showStatus(status, 'Could not copy. Select the setup key and copy it manually.', true); }
  });
  const recovery = document.querySelector('[data-recovery-codes]');
  if (recovery) {
    const codes = () => [...recovery.querySelectorAll('[data-recovery-code]')].map(code => code.textContent.trim()).filter(Boolean).join('\n');
    const status = recovery.querySelector('[data-recovery-status]');
    recovery.querySelector('[data-copy-recovery]')?.addEventListener('click', async () => { try { await copyText(codes()); showStatus(status, 'Recovery codes copied. Store them somewhere private.'); } catch { showStatus(status, 'Could not copy. Select the recovery codes and copy them manually.', true); } });
    recovery.querySelector('[data-download-recovery]')?.addEventListener('click', () => {
      let url;
      try { const content = codes(); if (!content) return; url = URL.createObjectURL(new Blob([text('Elang recovery codes')+'\n\n', content, '\n'], { type: 'text/plain;charset=utf-8' })); const link = document.createElement('a'); link.href = url; link.download = 'elang-recovery-codes.txt'; document.body.appendChild(link); link.click(); link.remove(); showStatus(status, 'Recovery codes downloaded. Store the file somewhere private.'); }
      catch { showStatus(status, 'Could not download. Copy or save the recovery codes manually.', true); }
      finally { if (url) setTimeout(() => URL.revokeObjectURL(url), 1000); }
    });
  }
})();
