// Optional browser-unit check: NODE_PATH=<directory containing jsdom> node --test tests/fixtures/analytics-consent.cjs
const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {JSDOM}=require('jsdom');
const source=fs.readFileSync(path.join(__dirname,'../../public/assets/analytics.js'),'utf8');
const id='G-TEST123456';
function browser({stored=null,dnt=false,gpc=false,storageBlocked=false}={}) {
  const dom=new JSDOM(`<head><link rel="canonical" href="https://elangphp.com/docs/v0.1/installation?email=secret#token"><script data-analytics-id="${id}"></script></head><body><section data-analytics-panel hidden><button data-analytics-choice="granted">Allow</button><button data-analytics-choice="denied">Reject</button></section><button data-analytics-preferences>Preferences</button></body>`,{url:'https://elangphp.com/docs/v0.1/installation?email=private@example.test#secret',referrer:'https://example.test/private?token=secret',runScripts:'outside-only'});
  const w=dom.window;
  if(stored) w.localStorage.setItem('elang.analytics.v1',stored);
  Object.defineProperty(w.navigator,'doNotTrack',{value:dnt?'1':null});
  Object.defineProperty(w.navigator,'globalPrivacyControl',{value:gpc});
  if(storageBlocked) Object.defineProperty(w,'localStorage',{get(){throw new Error('blocked');}});
  w.eval(source);
  return {w,document:w.document,click:value=>w.document.querySelector(`[data-analytics-choice="${value}"]`).click(),scripts:()=>[...w.document.querySelectorAll('script[src]')].map(s=>s.src)};
}
test('no Google requests or cookies before a choice',()=>{
  const b=browser();assert.equal(b.scripts().length,0);assert.equal(b.w.dataLayer,undefined);assert.equal(b.document.cookie,'');assert.equal(b.document.querySelector('[data-analytics-panel]').hidden,false);b.w.close();
});
test('reject persists without loading Google',()=>{
  const b=browser();b.click('denied');assert.equal(b.scripts().length,0);assert.equal(b.w.localStorage.getItem('elang.analytics.v1'),'denied');assert.equal(b.document.querySelector('[data-analytics-panel]').hidden,true);b.w.close();
});
test('allow loads once, strips private URL data, and disables advertising',()=>{
  const b=browser();b.click('granted');b.click('granted');assert.deepEqual(b.scripts(),[`https://www.googletagmanager.com/gtag/js?id=${id}`]);
  const config=b.w.dataLayer.find(entry=>entry[0]==='config')[2];assert.equal(config.page_location,'https://elangphp.com/docs/v0.1/installation');assert.equal(config.page_referrer,'https://example.test/');assert.equal(config.allow_google_signals,false);assert.equal(config.allow_ad_personalization_signals,false);assert.equal(JSON.stringify(b.w.dataLayer).includes('private@example.test'),false);b.w.close();
});
test('saved permission loads automatically on the next public page',()=>{
  const b=browser({stored:'granted'});assert.equal(b.scripts().length,1);assert.equal(b.document.querySelector('[data-analytics-panel]').hidden,true);b.w.close();
});
test('saved rejection stays rejected, preferences can reopen the choice',()=>{
  const b=browser({stored:'denied'});assert.equal(b.scripts().length,0);assert.equal(b.document.querySelector('[data-analytics-panel]').hidden,true);b.document.querySelector('[data-analytics-preferences]').click();assert.equal(b.document.querySelector('[data-analytics-panel]').hidden,false);b.w.close();
});
test('withdrawing permission disables measurement and removes GA cookies',()=>{
  const b=browser({stored:'granted'});b.document.cookie='_ga=example; path=/; Secure';b.document.cookie='_ga_TEST123456=session; path=/; Secure';b.click('denied');assert.equal(b.w[`ga-disable-${id}`],true);assert.equal(b.document.cookie,'');assert.equal(b.w.localStorage.getItem('elang.analytics.v1'),'denied');b.w.close();
});
for(const signal of ['dnt','gpc']) test(`${signal} overrides even saved permission`,()=>{
  const b=browser({stored:'granted',[signal]:true});assert.equal(b.scripts().length,0);b.click('granted');assert.equal(b.scripts().length,0);assert.equal(b.w[`ga-disable-${id}`],true);b.w.close();
});
test('blocked browser storage still permits a session-only decision',()=>{
  const b=browser({storageBlocked:true});assert.equal(b.scripts().length,0);b.click('granted');assert.equal(b.scripts().length,1);b.click('denied');assert.equal(b.w[`ga-disable-${id}`],true);b.w.close();
});
