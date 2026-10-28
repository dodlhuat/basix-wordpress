// Drives the public site in headless Chrome: the search overlay, a dropdown link, and the docs drawer on a
// phone. Needs the local site running with the seeded content (make up, make seed).
//   node scripts/check-frontend.mjs
// Env: WP_URL (default http://localhost:8090), CHROME (path to Chrome).
import puppeteer from 'puppeteer-core';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const failures = [];
const check = (ok, label) => {
  console.log(`${ok ? 'ok  ' : 'FAIL'} ${label}`);
  if (!ok) failures.push(label);
};

const browser = await puppeteer.launch({ executablePath: chrome, headless: true });
const open = async (path, viewport = { width: 1280, height: 800 }) => {
  const page = await (await browser.createBrowserContext()).newPage();
  await page.setViewport(viewport);
  page.on('pageerror', (e) => failures.push(`page error on ${path}: ${e.message}`));
  await page.goto(`${url}${path}`, { waitUntil: 'networkidle0' });
  await sleep(400);
  return page;
};
const overlayOpen = (page) => page.$('.modal-wrapper .search-overlay').then(Boolean);

try {
  // Search overlay: button, focus, Escape, focus returns, the "/" key, submitting.
  let page = await open('/');
  await page.click('.site-search-trigger');
  await sleep(500);
  check(await overlayOpen(page), 'search button opens the overlay');
  check(await page.evaluate(() => document.activeElement?.matches('.modal-wrapper input[type="search"]')), 'search field has focus');
  check(await page.evaluate(() => document.querySelector('.modal-wrapper .modal')?.getAttribute('role') === 'dialog'), 'overlay is marked as a dialog');
  await page.keyboard.press('Escape');
  await sleep(600);
  check(!(await overlayOpen(page)), 'Escape closes the overlay');
  check(await page.evaluate(() => document.activeElement?.classList.contains('site-search-trigger')), 'focus returns to the search button');

  await page.keyboard.press('/');
  await sleep(500);
  check(await overlayOpen(page), '"/" opens the overlay');
  await page.keyboard.type('design');
  await Promise.all([page.waitForNavigation(), page.keyboard.press('Enter')]);
  check(new URL(page.url()).searchParams.get('s') === 'design', 'submitting goes to the search results');

  // "/" must not steal the key while typing in a field.
  page = await open('/?s=design');
  await page.focus('.archive-header input[type="search"]');
  await page.keyboard.press('/');
  await sleep(300);
  check(!(await overlayOpen(page)), '"/" inside a field does not open the overlay');

  // Without JavaScript the button is a link to the search page.
  const href = await page.$eval('.site-search-trigger', (a) => a.getAttribute('href'));
  check(href.endsWith('/?s='), 'search button is a plain link without JavaScript');

  // Dropdown link navigates.
  page = await open('/');
  await page.click('.dropdown-trigger');
  await sleep(300);
  await page.click('.dropdown-menu > li > a[href*="/category/"]');
  const navigated = await page.waitForFunction(() => location.pathname.startsWith('/category/'), { timeout: 10000 }).then(() => true, () => false);
  check(navigated, 'dropdown link navigates');

  // Docs: the tree is on the page, the current page is marked, and the drawer works on a phone.
  page = await open('/docs/getting-started/installation/', { width: 390, height: 800 });
  check(await page.$eval('.docs-nav a.is-active', (a) => a.textContent) === 'Installation', 'docs nav marks the current page');
  check(await page.$$eval('.docs-nav li.is-open', (l) => l.length) >= 1, 'docs nav opens the current branch');
  const offscreen = await page.$eval('#docs-sidebar', (e) => e.getBoundingClientRect().right <= 0);
  check(offscreen, 'docs drawer starts closed on a phone');
  await page.click('.docs-toggle');
  await sleep(500);
  check(await page.$eval('#docs-sidebar', (e) => e.getBoundingClientRect().left >= 0 && e.classList.contains('is-open')), 'docs toggle opens the drawer');
  await page.mouse.click(375, 400); // the strip of backdrop to the right of the drawer
  await sleep(500);
  check(await page.$eval('#docs-sidebar', (e) => !e.classList.contains('is-open')), 'backdrop closes the drawer');

  // Docs on desktop: sidebar sits beside the content.
  page = await open('/docs/');
  const sideBySide = await page.evaluate(() => document.querySelector('#docs-sidebar').getBoundingClientRect().right <= document.querySelector('.docs-main').getBoundingClientRect().left + 1);
  check(sideBySide, 'docs sidebar is beside the content on desktop');
} catch (error) {
  failures.push(`stopped early: ${error.message}`);
} finally {
  await browser.close();
}
if (failures.length) {
  console.error(`\nFAILED:\n${failures.join('\n')}`);
  process.exitCode = 1;
} else {
  console.log('\nFront-end check passed.');
}
