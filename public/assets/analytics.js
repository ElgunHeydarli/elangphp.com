(() => {
  'use strict';
  const bootstrap = document.querySelector('script[data-analytics-id]');
  const id = bootstrap?.dataset.analyticsId;
  if (!/^G-[A-Z0-9]{6,20}$/.test(id || '')) return;
  const key = 'elang.analytics.v1';
  const panel = document.querySelector('[data-analytics-panel]');
  const optOut = navigator.globalPrivacyControl === true || ['1', 'yes'].includes(navigator.doNotTrack || window.doNotTrack);
  let loaded = false;
  let choice = null;
  try { choice = localStorage.getItem(key); } catch (_) { /* Session-only choice when storage is unavailable. */ }
  const show = visible => { if (panel) panel.hidden = !visible; };
  const eraseCookies = () => {
    document.cookie.split(';').forEach(cookie => {
      const name = cookie.split('=')[0].trim();
      if (!/^_ga(?:_|$)/.test(name)) return;
      ['', `; domain=${location.hostname}`, `; domain=.${location.hostname}`].forEach(domain => {
        document.cookie = `${name}=; Max-Age=0; path=/${domain}; SameSite=Lax; Secure`;
      });
    });
  };
  const load = () => {
    if (optOut || loaded) return;
    loaded = true;
    window[`ga-disable-${id}`] = false;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('consent', 'default', {analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied'});
    window.gtag('js', new Date());
    const page = new URL(document.querySelector('link[rel="canonical"]')?.href || location.href);
    page.search = ''; page.hash = '';
    let referrer = '';
    try { referrer = new URL(document.referrer).origin + '/'; } catch (_) { /* Direct visit. */ }
    window.gtag('config', id, {page_location: page.href, page_referrer: referrer, allow_google_signals: false, allow_ad_personalization_signals: false, send_page_view: true});
    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${id}`;
    document.head.appendChild(script);
  };
  const choose = value => {
    choice = value;
    try { localStorage.setItem(key, value); } catch (_) { /* Keep the current page choice. */ }
    show(false);
    if (value === 'granted' && !optOut) {
      window[`ga-disable-${id}`] = false;
      if (loaded && window.gtag) window.gtag('consent', 'update', {analytics_storage: 'granted'});
      load();
    } else {
      window[`ga-disable-${id}`] = true;
      if (window.gtag) window.gtag('consent', 'update', {analytics_storage: 'denied'});
      eraseCookies();
    }
  };
  document.querySelectorAll('[data-analytics-choice]').forEach(button => button.addEventListener('click', () => choose(button.dataset.analyticsChoice)));
  document.querySelectorAll('[data-analytics-preferences]').forEach(button => button.addEventListener('click', () => show(true)));
  if (optOut) choose('denied');
  else if (choice === 'granted') { show(false); load(); }
  else show(choice !== 'denied');
})();
