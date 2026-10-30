'use strict';
const elangLocale = document.body.dataset.siteLocale || 'en';
let elangMessages = {};
try { elangMessages = JSON.parse(document.body.dataset.ui || '{}'); } catch (_) {}
const elangText = (message, values = {}) => Object.entries(values).reduce((text, [key, value]) => text.replaceAll(`{${key}}`, value), elangMessages[message] || message);
(() => {
  const root = document.documentElement;
  const themeButton = document.querySelector('.theme-toggle');
  let theme;
  try { theme = localStorage.getItem('elang-theme'); } catch (_) {}
  if (theme !== 'light' && theme !== 'dark') theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  const applyTheme = () => {
    root.dataset.theme = theme;
    themeButton?.setAttribute('aria-label', elangText(theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'));
  };
  applyTheme();
  themeButton?.addEventListener('click', () => {
    theme = theme === 'dark' ? 'light' : 'dark'; applyTheme();
    try { localStorage.setItem('elang-theme', theme); } catch (_) {}
  });
  const menuButton = document.querySelector('.menu-toggle');
  menuButton?.addEventListener('click', () => {
    const open = menuButton.getAttribute('aria-expanded') !== 'true';
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? elangText('Close navigation') : elangText('Open navigation'));
    document.querySelector('#main-nav')?.classList.toggle('is-open', open);
  });
  const docsMenu = document.querySelector('.docs-menu-toggle');
  docsMenu?.addEventListener('click', () => {
    const open = docsMenu.getAttribute('aria-expanded') !== 'true';
    docsMenu.setAttribute('aria-expanded', String(open));
    document.querySelector('#docs-navigation')?.classList.toggle('is-open', open);
  });
  document.querySelectorAll('.prose pre').forEach(pre => {
    const code = pre.querySelector('code');
    if (!code) return;
    const button = document.createElement('button');
    button.className = 'copy-code'; button.textContent = elangText('Copy'); button.setAttribute('aria-label', elangText('Copy code example'));
    button.addEventListener('click', async () => {
      try { await navigator.clipboard.writeText(code.textContent); button.textContent = elangText('Copied'); }
      catch (_) { const selection = getSelection(); const range = document.createRange(); range.selectNodeContents(code); selection.removeAllRanges(); selection.addRange(range); button.textContent = elangText('Selected'); }
      setTimeout(() => { button.textContent = elangText('Copy'); }, 1800);
    });
    pre.append(button);
  });
  const dialog = document.querySelector('.search-dialog');
  const searchInput = document.querySelector('#docs-search');
  const results = document.querySelector('#search-results');
  let previousFocus, searchTimer, controller;
  const hint = text => { const p = document.createElement('p'); p.className = 'search-hint'; p.textContent = text; results.replaceChildren(p); };
  const openSearch = () => {
    if (!dialog || dialog.open) return;
    previousFocus = document.activeElement; dialog.showModal(); searchInput.focus();
  };
  document.querySelectorAll('[data-search-open]').forEach(button => button.addEventListener('click', openSearch));
  document.querySelector('[data-search-close]')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('close', () => { previousFocus?.focus(); });
  dialog?.addEventListener('click', event => { if (event.target === dialog) { const bounds = dialog.getBoundingClientRect(); if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close(); } });
  document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openSearch(); }
    if (event.key === 'Escape' && dialog?.open) { event.preventDefault(); dialog.close(); return; }
    if (event.key === 'Escape' && menuButton?.getAttribute('aria-expanded') === 'true') menuButton.click();
  });
  dialog?.addEventListener('keydown', event => {
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
    const links = [...results.querySelectorAll('a')]; if (!links.length) return;
    event.preventDefault(); const index = links.indexOf(document.activeElement);
    const next = event.key === 'ArrowDown' ? (index + 1) % links.length : (index <= 0 ? links.length - 1 : index - 1);
    links[next].focus();
  });
  searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer); controller?.abort();
    const query = searchInput.value.trim();
    if (query.length < 2) { hint(elangText('Type at least two characters to search.')); return; }
    hint(elangText('Searching…'));
    searchTimer = setTimeout(async () => {
      controller = new AbortController();
      try {
        const response = await fetch(`/api/search?lang=${elangLocale}&q=${encodeURIComponent(query)}`, {signal: controller.signal, headers: {'Accept': 'application/json'}});
        if (!response.ok) throw new Error(elangText('Search is temporarily unavailable. Please try again.'));
        const data = await response.json();
        if (searchInput.value.trim() !== query) return;
        if (!data.results.length) { hint(elangText('No results for “{query}”. Try routing, validation, or database.', {query})); return; }
        results.replaceChildren();
        data.results.forEach(result => {
          const link = document.createElement('a'); link.href = result.url; link.className = 'search-result';
          const section = document.createElement('span'); section.textContent = result.section;
          const title = document.createElement('strong'); title.textContent = result.title;
          const excerpt = document.createElement('p'); excerpt.textContent = result.excerpt;
          link.append(section, title, excerpt); results.append(link);
        });
      } catch (error) { if (error.name !== 'AbortError') hint(error.message || elangText('Could not search. Please try again.')); }
    }, 180);
  });
  const playground = document.querySelector('[data-playground]');
  if (playground) {
    let example = 'hello';
    const tabs = [...playground.querySelectorAll('[data-example]')];
    const name = document.querySelector('#playground-name');
    const email = document.querySelector('#playground-email');
    const run = document.querySelector('#playground-run');
    const output = document.querySelector('#playground-output');
    const status = document.querySelector('#playground-status');
    let running = false;
    const select = tab => {
      if (running) return;
      example = tab.dataset.example;
      tabs.forEach(item => { const selected = item === tab; item.setAttribute('aria-selected', String(selected)); item.tabIndex = selected ? 0 : -1; document.querySelector(`#code-${item.dataset.example}`).hidden = !selected; });
      name.closest('label').hidden = example === 'database'; email.closest('label').hidden = example !== 'validate';
      if (example === 'validate' && name.value === 'World') name.value = 'Ada';
      output.textContent = '// Ready when you are.\n// Hit “Run request” to see the response.';
      status.textContent = elangText('Waiting for a request');
    };
    tabs.forEach(tab => {
      tab.addEventListener('click', () => select(tab));
      tab.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault(); const index = tabs.indexOf(tab); const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length]; select(next); next.focus();
      });
    });
    run.addEventListener('click', async () => {
      if (running) return; running = true; run.disabled = true; tabs.forEach(tab => { tab.disabled = true; }); status.textContent = elangText('Sending request…'); output.setAttribute('aria-busy', 'true');
      try {
        const options = {headers: {'Accept': 'application/json'}};
        let url = `/api/playground/${example}`;
        if (example === 'hello') url += `?name=${encodeURIComponent(name.value || 'World')}`;
        if (example === 'validate') { options.method = 'POST'; options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify({name: name.value, email: email.value}); }
        const response = await fetch(url, options); const data = await response.json();
        status.textContent = `${response.status} ${response.statusText}`;
        output.textContent = JSON.stringify(data, null, 2);
      } catch (_) { status.textContent = elangText('Request failed'); output.textContent = elangText('Could not reach the backend. Please try again.'); }
      finally { running = false; run.disabled = false; tabs.forEach(tab => { tab.disabled = false; }); output.removeAttribute('aria-busy'); }
    });
  }
})();

// Lesson completion stays in this browser; the server stores no progress profile.
(() => {
  const key = 'elang-v0.1-learning-progress';
  const known = ['environment','php-basics','first-route','json-api','validate-input','database','templates','build-a-task-board','debugging','ship','partner-api','entities-and-resources','test-your-application'];
  let completed = [];
  try { const saved = JSON.parse(localStorage.getItem(key) || '[]'); if (Array.isArray(saved)) completed = [...new Set(saved.filter(id => known.includes(id)))]; } catch (_) {}
  const button = document.querySelector('[data-lesson-id]');
  const render = () => {
    if (button) { const done = completed.includes(button.dataset.lessonId); button.setAttribute('aria-pressed', String(done)); button.textContent = done ? elangText('Completed ✓') : elangText('Mark lesson complete'); }
    document.querySelectorAll('.lesson-row').forEach(row => { const slug = new URL(row.href).pathname.split('/').pop(); row.classList.toggle('is-complete', completed.includes(slug)); });
    const count = document.querySelector('#learning-progress-count'); if (count) count.textContent = String(completed.length);
  };
  button?.addEventListener('click', () => {
    const id = button.dataset.lessonId;
    completed = completed.includes(id) ? completed.filter(item => item !== id) : [...completed, id];
    try { localStorage.setItem(key, JSON.stringify(completed)); render(); }
    catch (_) { button.textContent = elangText('Browser storage unavailable'); }
  });
  const progress = document.querySelector('[data-learning-progress]');
  if (progress) progress.hidden = false;
  document.querySelector('#learning-progress-reset')?.addEventListener('click', () => { completed = []; try { localStorage.removeItem(key); } catch (_) {} render(); });
  render();
})();

// Installation commands are copyable without a frontend dependency.
document.querySelectorAll('[data-copy-target]').forEach(button => {
  button.addEventListener('click', async () => {
    const code = document.getElementById(button.dataset.copyTarget);
    try { await navigator.clipboard.writeText(code.textContent); button.textContent = elangText('Copied'); }
    catch (_) { const range = document.createRange(); range.selectNodeContents(code); const selection = getSelection(); selection.removeAllRanges(); selection.addRange(range); button.textContent = elangText('Selected'); }
    setTimeout(() => { button.textContent = elangText('Copy'); }, 1800);
  });
});

// Native details supplies keyboard activation; close the language picker on dismissal.
document.querySelectorAll('.language-picker').forEach(picker => {
  document.addEventListener('click', event => { if (!picker.contains(event.target)) picker.open = false; });
  picker.addEventListener('keydown', event => { if (event.key === 'Escape') { picker.open = false; picker.querySelector('summary').focus(); event.stopPropagation(); } });
});
