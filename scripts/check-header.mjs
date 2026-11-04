// Tries the header and footer options in a real browser: the button and top bar, sticky and static headers, the
// transparent header over a cover, a page without a header, the mega menu, footer layouts, per-page title and sidebar,
// the header image, the live preview in the Customizer, and axe with all of it on. Settings are changed with WP-CLI and
// put back at the end. Needs the local site running (make up).
//   node scripts/check-header.mjs
// Env: WP_URL (default http://localhost:8090), WP_USER / WP_PASS (admin / admin), CHROME (path to Chrome).
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
const setMod = (name, value) => wp('theme', 'mod', 'set', name, String(value));
const mods = new Set();
const mod = (name, value) => { mods.add(name); setMod(name, value); };

const landing = wp('post', 'list', '--post_type=page', '--name=landing-demo', '--field=ID');
const menu = wp('menu', 'list', '--fields=term_id,locations', '--format=csv').split('\n').find((l) => l.includes('primary')).split(',')[0];
const topics = wp('menu', 'item', 'list', menu, '--fields=ID,title', '--format=csv').split('\n').find((l) => l.endsWith(',Topics')).split(',')[0];
const pages = [];
const makePage = (title, meta = {}) => {
  const id = wp('post', 'create', '--post_type=page', '--post_status=publish', `--post_title=${title}`, '--post_content=<!-- wp:paragraph --><p>Text of the page.</p><!-- /wp:paragraph -->', '--porcelain');
  pages.push(id);
  for (const [key, value] of Object.entries(meta)) wp('post', 'meta', 'update', id, key, value);
  return id;
};
const link = (id) => wp('post', 'list', '--post_type=page', `--post__in=${id}`, '--field=url');

let browser = await puppeteer.launch({ executablePath: chrome, headless: true });
const open = async (address, { width = 1280, theme = 'light', action } = {}) => {
  const page = await (await browser.createBrowserContext()).newPage();
  await page.setViewport({ width, height: 900 });
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await page.evaluateOnNewDocument((t) => localStorage.setItem('theme', t), theme);
  await page.goto(address.startsWith('http') ? address : `${url}${address}`, { waitUntil: 'networkidle0' });
  await sleep(500);
  if (action) await action(page);
  return page;
};
const scrollTo = async (page, y) => { await page.evaluate((v) => window.scrollTo(0, v), y); await sleep(350); };
const headerTop = (page) => page.$eval('.site-header', (h) => Math.round(h.getBoundingClientRect().top));

try {
  // The button, the top bar, and a header that stays at the top.
  mod('basix_header_cta_label', 'Get a quote');
  mod('basix_header_cta_url', 'https://example.com/quote');
  mod('basix_topbar_enable', 1);
  mod('basix_topbar_text', 'Free shipping this week');
  let page = await open('/');
  check(await page.$eval('.header-cta', (a) => a.offsetParent !== null && a.textContent.trim() === 'Get a quote' && a.href === 'https://example.com/quote'), 'the header has its button');
  check((await page.$eval('.top-bar-text', (p) => p.textContent)) === 'Free shipping this week' && (await page.$$('.top-bar-social a')).length > 0, 'the top bar shows the message and the social links');
  await scrollTo(page, 800);
  check((await headerTop(page)) === 0, 'the header stays at the top while scrolling');
  await page.close();
  page = await open('/', { width: 390 });
  check(await page.$eval('.header-cta', (a) => getComputedStyle(a).display === 'none'), 'on a phone the header button steps aside');
  await page.click('.site-menu-trigger');
  await sleep(600);
  check(await page.$eval('.menu-cta a', (a) => a.offsetParent !== null && a.textContent.trim() === 'Get a quote'), 'and is the last thing in the opened menu');
  await page.close();

  mod('basix_header_sticky', 0);
  page = await open('/');
  await scrollTo(page, 800);
  check((await headerTop(page)) < 0, 'a header that is not sticky scrolls away');
  await page.close();
  setMod('basix_header_sticky', 1);

  // The mega menu.
  wp('menu', 'item', 'update', topics, '--classes=mega-menu');
  page = await open('/', { action: (p) => p.click('.site-nav .dropdown-trigger') });
  await sleep(400);
  const mega = await page.evaluate(() => {
    const panel = document.querySelector('.dropdown-menu.is-mega');
    const links = [...panel.querySelectorAll('.mega-column ul a')];
    const box = panel.getBoundingClientRect();
    return { columns: panel.querySelectorAll('.mega-column').length, visible: links.length > 0 && links.every((a) => a.getBoundingClientRect().height > 0 && getComputedStyle(a.closest('ul')).visibility === 'visible' && getComputedStyle(a.closest('ul')).opacity === '1'), inside: box.left >= 0 && box.right <= window.innerWidth, wide: box.width > 600 };
  });
  check(mega.columns >= 3 && mega.visible && mega.inside && mega.wide, `the mega menu opens as a wide panel with ${mega.columns} columns and their items`);
  await page.evaluate(axeSource);
  const axeMega = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
  check(axeMega.violations.filter((v) => ['serious', 'critical'].includes(v.impact)).length === 0, 'the page with button, top bar and an open mega menu has no serious axe problems');
  await page.close();
  page = await open('/', { width: 390, action: async (p) => { await p.click('.site-menu-trigger'); } });
  await sleep(500);
  check((await page.$eval('#site-flyout', (n) => n.textContent)).includes('tokens'), 'on a phone the menu is a plain nested list in the drawer');
  await page.close();
  wp('menu', 'item', 'update', topics, '--classes=');

  // The transparent header over a cover.
  wp('post', 'meta', 'update', landing, '_basix_header', 'transparent');
  page = await open(`/?page_id=${landing}`);
  const top = await page.evaluate(() => {
    const header = document.querySelector('.site-header');
    const cover = document.querySelector('.entry-content > .wp-block-cover');
    const bg = getComputedStyle(header).backgroundColor;
    return { clear: bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent', light: getComputedStyle(header.querySelector('.site-title')).color === 'rgb(255, 255, 255)', overlap: Math.abs(cover.getBoundingClientRect().top - header.getBoundingClientRect().top) <= 4 };
  });
  check(top.clear && top.light && top.overlap, 'a transparent header is clear, has light text and sits over the cover');
  await scrollTo(page, 400);
  check(await page.$eval('.site-header', (h) => h.classList.contains('is-scrolled') && getComputedStyle(h).backgroundColor !== 'rgba(0, 0, 0, 0)'), 'and turns solid once the page scrolls');
  await page.close();
  wp('post', 'meta', 'update', landing, '_basix_header', 'none');
  page = await open(`/?page_id=${landing}`);
  check((await page.$$('.site-header')).length === 0 && (await page.$$('.top-bar')).length === 0 && (await page.$$('#content')).length === 1 && (await page.$$('.site-footer')).length === 1, 'a page can have no header (and no top bar) and keeps its content and footer');
  await page.close();
  wp('post', 'meta', 'delete', landing, '_basix_header');
  mod('basix_header_style', 'transparent');
  page = await open('/');
  check(await page.evaluate(() => document.body.classList.contains('header-transparent')), 'the Customizer can make every header transparent');
  await page.close();
  setMod('basix_header_style', 'default');

  // Footer layouts.
  mod('basix_footer_layout', 'centered');
  page = await open('/');
  check(await page.$eval('.site-footer-inner', (n) => getComputedStyle(n).flexDirection === 'column' && getComputedStyle(n).textAlign === 'center'), 'a centred footer is stacked and centred');
  await page.close();
  setMod('basix_footer_layout', 'minimal');
  page = await open('/');
  check((await page.$$('.footer-widgets')).length === 0 && (await page.$$('.site-footer-inner')).length === 1, 'a minimal footer has no widget columns');
  await page.close();
  setMod('basix_footer_layout', 'columns');

  // Per-page title, sidebar and footer.
  const quiet = makePage('Quiet page', { _basix_hide_title: '1', _basix_footer: 'none', _basix_sidebar: 'right' });
  const plain = makePage('Plain page');
  page = await open(link(quiet));
  const quietState = await page.evaluate(() => {
    const h = document.querySelector('.entry-header');
    const box = h.getBoundingClientRect();
    return { hidden: h.classList.contains('screen-reader-text') && box.width <= 1 && box.height <= 1, heading: h.querySelector('h1')?.textContent, sidebar: !!document.querySelector('.layout.has-sidebar'), footer: !!document.querySelector('.site-footer') };
  });
  check(quietState.hidden && quietState.heading === 'Quiet page', 'a page can hide its title for sight and keep it for screen readers');
  check(quietState.sidebar, 'a page can ask for a sidebar');
  check(!quietState.footer, 'and for no footer');
  await page.close();
  page = await open(link(plain));
  check(await page.evaluate(() => !document.querySelector('.layout.has-sidebar') && !!document.querySelector('.site-footer') && !document.querySelector('.entry-header').classList.contains('screen-reader-text')), 'another page keeps the defaults: no sidebar, a footer and a visible title');
  await page.close();

  // A very long site name takes two lines in the header at most, and the front page still shows it whole.
  const longName = 'A very long site title that keeps going and going to see what the header does when the name is far too long for one line';
  const oldName = wp('option', 'get', 'blogname');
  wp('option', 'update', 'blogname', longName);
  try {
    page = await open('/');
    const longHeader = await page.evaluate(() => ({ header: document.querySelector('.site-header').getBoundingClientRect().height, title: document.querySelector('.site-title').getBoundingClientRect().height, full: document.querySelector('.site-title').textContent.trim(), hero: document.querySelector('.hero-title, .hero-minimal .hero-title')?.textContent.trim() }));
    check(longHeader.header < 110 && longHeader.title < 60 && longHeader.full.length > 100, `a very long site name stays within two lines of the header (${Math.round(longHeader.header)}px high)`);
    check(!longHeader.hero || longHeader.hero.length > 100, 'and the front page introduction shows it whole');
    await page.close();
  } finally {
    wp('option', 'update', 'blogname', oldName);
  }

  // The header image behind the front page introduction.
  mod('header_image', `${url}/wp-content/themes/basix-paperlight/assets/images/wide-1.jpg`);
  page = await open('/');
  check(await page.$eval('.hero.has-image', (h) => getComputedStyle(h).backgroundImage.includes('wide-1.jpg') && getComputedStyle(h.querySelector('.hero-title')).color === 'rgb(255, 255, 255)'), 'a header image becomes the backdrop of the front page introduction, with light text');
  await page.close();
  wp('theme', 'mod', 'remove', 'header_image');

  // The Customizer preview changes live.
  const admin = await browser.newPage();
  await admin.setViewport({ width: 1400, height: 1000 });
  await admin.goto(`${url}/wp-login.php`);
  await admin.type('#user_login', process.env.WP_USER ?? 'admin');
  await admin.type('#user_pass', process.env.WP_PASS ?? 'admin');
  await Promise.all([admin.waitForNavigation(), admin.click('#wp-submit')]);
  await admin.goto(`${url}/wp-admin/customize.php`, { waitUntil: 'networkidle2' });
  await admin.waitForFunction(() => window.wp?.customize?.previewer?.targetWindow?.()?.document?.querySelector('.site-header'), { timeout: 30000 });
  const live = async (setting, value, ready, label) => {
    await admin.evaluate((s, v) => wp.customize(s).set(v), setting, value);
    await admin.waitForFunction((src) => { const w = wp.customize.previewer.targetWindow(); return new Function('w', `return (${src})(w)`)(w); }, { timeout: 15000 }, ready.toString()).then(
      () => check(true, `the preview shows ${label} at once`),
      () => check(false, `the preview shows ${label} at once`),
    );
  };
  await live('basix_header_cta_label', 'Book now', (w) => w.document.querySelector('.header-cta')?.textContent === 'Book now', 'a new button text');
  await live('basix_topbar_text', 'Open until 8 pm', (w) => w.document.querySelector('.top-bar-text')?.textContent === 'Open until 8 pm', 'a new top bar message');
  await live('basix_footer_text', '© {year} {site} lives', (w) => w.document.querySelector('.site-footer-text')?.textContent.includes('lives') && !w.document.querySelector('.site-footer-text').textContent.includes('{year}'), 'the footer line with the year filled in');
  await live('basix_header_sticky', false, (w) => w.document.body.classList.contains('header-static') && !w.document.body.classList.contains('header-sticky'), 'a header that is not sticky');
  await admin.close();

  // The page box is in the editor.
  const editor = await browser.newPage();
  await editor.setViewport({ width: 1400, height: 1000 });
  await editor.goto(`${url}/wp-login.php`);
  await editor.type('#user_login', process.env.WP_USER ?? 'admin');
  await editor.type('#user_pass', process.env.WP_PASS ?? 'admin');
  await Promise.all([editor.waitForNavigation(), editor.click('#wp-submit')]);
  await editor.goto(`${url}/wp-admin/post.php?post=${plain}&action=edit`, { waitUntil: 'networkidle2' });
  await editor.waitForSelector('#basix_page_options select[name="_basix_header"]', { timeout: 30000 }).then(() => check(true, 'the page editor has the "Page layout" box'), () => check(false, 'the page editor has the "Page layout" box'));
  await editor.close();
} finally {
  for (const name of mods) {
    try { wp('theme', 'mod', 'remove', name); } catch { /* not set */ }
  }
  try { wp('menu', 'item', 'update', topics, '--classes='); } catch { /* fine */ }
  try { wp('post', 'meta', 'delete', landing, '_basix_header'); } catch { /* fine */ }
  for (const id of pages) {
    try { wp('post', 'delete', id, '--force'); } catch { /* fine */ }
  }
  await browser.close();
}
if (failures.length) {
  console.log(`\nFAILED: ${failures.length}`);
  process.exit(1);
}
console.log('\nHeader check passed.');
