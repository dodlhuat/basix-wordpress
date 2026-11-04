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
const created = [];
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
    const sheets = [...document.styleSheets].map((s) => s.href ?? '').filter((h) => h.includes('/themes/basix-paperlight/'));
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

  // Real Arabic text: where it starts, how lists, quotes and tables sit, and mixed text with English words and numbers.
  const arabic = `<!-- wp:heading --><h2 class="wp-block-heading">مرحبًا بكم في موقعنا</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>هذا نص تجريبي باللغة العربية لاختبار اتجاه الكتابة من اليمين إلى اليسار في القالب. يجب أن يبدأ النص من الحافة اليمنى وأن يلتف بشكل صحيح عند نهاية السطر دون أن يخرج عن حدود الصفحة، حتى لو كان السطر طويلًا جدًا ويحتوي على كلمات كثيرة.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>يمكنك استخدام كلمات إنجليزية مثل WordPress وأرقام مثل 2026 داخل الجملة نفسها، وتبقى الجملة مقروءة.</p><!-- /wp:paragraph -->
<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>العنصر الأول</li><!-- /wp:list-item --><!-- wp:list-item --><li>العنصر الثاني</li><!-- /wp:list-item --></ul><!-- /wp:list -->
<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>الاقتباس يجب أن يظهر خطه الجانبي على اليمين.</p><!-- /wp:paragraph --><cite>كاتب مجهول</cite></blockquote><!-- /wp:quote -->
<!-- wp:table --><figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>الاسم</th><th>القيمة</th></tr></thead><tbody><tr><td>الأول</td><td>١٢٣</td></tr></tbody></table></figure><!-- /wp:table -->`;
  const pageId = wp('--user=admin', 'post', 'create', '--post_type=page', '--post_status=publish', '--post_title=صفحة تجريبية', `--post_content=${arabic}`, '--porcelain');
  created.push(pageId);
  page = await open(wp('post', 'list', '--post_type=page', `--post__in=${pageId}`, '--field=url').replace(url, '') || '/');
  const text = await page.evaluate(() => {
    const content = document.querySelector('.entry-content');
    const lineEdge = (el) => { const range = document.createRange(); range.selectNodeContents(el); const rects = [...range.getClientRects()]; return { right: Math.max(...rects.map((r) => r.right)), left: Math.min(...rects.map((r) => r.left)) }; };
    const para = content.querySelector('p');
    const box = para.getBoundingClientRect();
    const edge = lineEdge(para);
    const list = content.querySelector('ul');
    const quote = content.querySelector('blockquote');
    const cell = content.querySelector('td');
    const css = (el) => getComputedStyle(el);
    return {
      lang: document.documentElement.lang,
      heading: css(document.querySelector('.entry-header h1')).textAlign,
      direction: css(para).direction,
      startsRight: Math.abs(edge.right - box.right) < 6 && edge.left < box.left + box.width * 0.5,
      listSide: parseFloat(css(list).paddingRight) > parseFloat(css(list).paddingLeft),
      quoteSide: parseFloat(css(quote).borderRightWidth) > parseFloat(css(quote).borderLeftWidth) || parseFloat(css(quote).paddingRight) > parseFloat(css(quote).paddingLeft),
      cellAlign: css(cell).textAlign,
      upright: css(quote).fontStyle === 'normal' && css(quote.querySelector('p')).fontStyle === 'normal',
      joined: css(document.querySelector('.entry-header h1')).letterSpacing === 'normal' && css(content.querySelector('h2')).letterSpacing === 'normal',
      overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
      mixed: content.querySelectorAll('p')[1].textContent.includes('WordPress') && content.querySelectorAll('p')[1].textContent.includes('2026'),
    };
  });
  check(text.lang.startsWith('ar') && text.direction === 'rtl', `Arabic text reads right to left (language ${text.lang})`);
  check(['right', 'start'].includes(text.heading) && text.startsRight, 'a heading and a paragraph start at the right edge and wrap inside the page');
  check(text.listSide && text.quoteSide, 'a list is indented on the right and a quote has its bar on the right');
  check(['right', 'start'].includes(text.cellAlign), 'table cells are aligned to the right');
  check(text.upright && text.joined, 'Arabic quotes are not slanted and headings have no letter spacing, which would break the joined letters');
  check(!text.overflow && text.mixed, 'a sentence with English words and numbers fits and stays whole');
  await page.evaluate(axeSource);
  const axeArabic = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
  check(axeArabic.violations.filter((v) => ['serious', 'critical'].includes(v.impact)).length === 0, 'axe finds nothing serious on the Arabic page');
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
  for (const id of created) {
    try { wp('post', 'delete', id, '--force'); } catch { /* gone already */ }
  }
  wp('option', 'update', 'WPLANG', language);
  await browser.close();
}
if (failures.length) {
  console.log(`\nFAILED: ${failures.length}`);
  process.exit(1);
}
console.log('\nRight-to-left check passed.');
