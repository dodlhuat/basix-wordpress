// Looks at the site as a right-to-left language (Arabic) in a real browser: the right stylesheets load, the page
// is mirrored, nothing sticks out sideways, the menu drawer opens from the right side, the slider and the calendar
// work, and axe finds nothing serious. The site language is switched to Arabic for the run and put back after it.
// Needs the local site running (make up); installs the Arabic language pack once.
//   node scripts/check-rtl.mjs
// Env: WP_URL (default http://localhost:8090), CHROME (path to Chrome).
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import puppeteer from 'puppeteer-core';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const failures = [];
const check = (ok, what) => {
  console.log(`${ok ? 'ok  ' : 'FAIL'} ${what}`);
  if (!ok) failures.push(what);
};
const wp = (...args) => execFileSync('docker', ['compose', 'run', '--rm', '-T', 'wpcli', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();

const language = wp('option', 'get', 'WPLANG');
wp('language', 'core', 'install', 'ar');
wp('option', 'update', 'WPLANG', 'ar');
const browser = await puppeteer.launch({ executablePath: chrome, headless: true });
const open = async (path, width = 1280) => {
  const page = await (await browser.createBrowserContext()).newPage();
  await page.setViewport({ width, height: 900 });
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await page.evaluateOnNewDocument(() => localStorage.setItem('theme', 'light'));
  await page.goto(`${url}${path}`, { waitUntil: 'networkidle0' });
  await sleep(600);
  return page;
};
try {
  let page = await open('/everything-in-one-post/');
  const state = await page.evaluate(() => {
    const box = (s) => document.querySelector(s)?.getBoundingClientRect();
    const sheets = [...document.styleSheets].map((s) => s.href ?? '').filter((h) => h.includes('/themes/basix/'));
    return {
      dir: document.documentElement.dir,
      rtlSheets: sheets.filter((h) => h.includes('-rtl.css')).length,
      ltrSheets: sheets.filter((h) => !h.includes('-rtl.css')).length,
      titleSide: box('.site-title').left > window.innerWidth / 2,
      toggleSide: box('.theme-toggle').left < window.innerWidth / 2,
      sidebarSide: box('.sidebar, .layout > aside')?.left < box('.layout-main').left,
      crumbs: getComputedStyle(document.querySelector('.breadcrumb')).direction,
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
    };
  });
  check(state.dir === 'rtl' && state.rtlSheets === 5 && state.ltrSheets === 0, `a right-to-left site loads the five mirrored stylesheets (${state.rtlSheets})`);
  check(state.titleSide && state.toggleSide, 'the site title sits at the right, the buttons at the left');
  check(state.sidebarSide, 'the sidebar moves to the left of the text');
  check(!state.overflow, 'nothing sticks out sideways');
  await page.evaluate(axeSource);
  const result = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
  check(result.violations.filter((v) => ['serious', 'critical'].includes(v.impact)).length === 0, 'axe finds nothing serious on a post');
  await page.close();

  page = await open('/', 390);
  const closed = await page.$eval('#site-flyout', (n) => n.getBoundingClientRect().left >= window.innerWidth - 1);
  await page.click('.site-menu-trigger');
  await sleep(700);
  const drawer = await page.$eval('#site-flyout', (n) => { const r = n.getBoundingClientRect(); return { covers: r.left <= 1 && r.right >= window.innerWidth - 1, overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth }; });
  check(closed && drawer.covers && !drawer.overflow, 'on a phone the menu drawer waits outside the screen and opens over it without sideways overflow');
  await page.close();

  page = await open('/');
  const arrows = await page.$$eval('.featured .carousel-button', (b) => b.map((x) => x.getBoundingClientRect().left));
  const before = await page.$eval('.featured .carousel-track', (t) => t.style.transform);
  await page.click('.featured .carousel-button--right');
  await sleep(700);
  const after = await page.$eval('.featured .carousel-track', (t) => t.style.transform);
  check(arrows.length === 2 && arrows[0] < arrows[1] && before !== after, 'the slider keeps its arrows in place and still moves');
  await page.close();

  page = await open('/calendar/');
  await page.waitForSelector('.cal__event-pill', { timeout: 15000 });
  const calendar = await page.evaluate(() => ({ pills: document.querySelectorAll('.cal__event-pill').length, days: document.querySelectorAll('.cal__day').length, overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth }));
  check(calendar.pills > 0 && calendar.days >= 28 && !calendar.overflow, `the calendar draws its days and events (${calendar.pills} events)`);
  await page.close();

  for (const path of ['/', '/events/', '/pattern-library/']) {
    page = await open(path);
    await page.evaluate(axeSource);
    const r = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
    const bad = r.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
    check(bad.length === 0 && !(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)), `${path}: no serious axe problems and no sideways overflow${bad.length ? ` (${bad.map((v) => v.id)})` : ''}`);
    await page.close();
  }
} finally {
  wp('option', 'update', 'WPLANG', language);
  await browser.close();
}
if (failures.length) {
  console.log(`\nFAILED: ${failures.length}`);
  process.exit(1);
}
console.log('\nRight-to-left check passed.');
