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
  const context = await browser.createBrowserContext();
  await context.overridePermissions(url, ['clipboard-read', 'clipboard-write', 'clipboard-sanitized-write']);
  const page = await context.newPage();
  // Chrome only allows clipboard writes from a focused page, and headless pages are not focused by default.
  await (await page.createCDPSession()).send('Emulation.setFocusEmulationEnabled', { enabled: true });
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

  // Post features, on the long demo post.
  page = await open('/everything-in-one-post/');
  check(/min read/.test(await page.$eval('.entry-header .post-meta', (e) => e.textContent)), 'reading time is shown');

  const tocLinks = await page.$$eval('.toc a', (links) => links.map((a) => a.getAttribute('href')));
  check(tocLinks.length >= 5, 'table of contents lists the headings');
  check(await page.evaluate((hrefs) => hrefs.every((h) => document.querySelector(h)), tocLinks), 'every table of contents link has a heading to go to');
  await page.click('.toc a[href$="comparing-the-options"]');
  await sleep(1000);
  check(await page.$eval('#comparing-the-options', (e) => Math.abs(e.getBoundingClientRect().top) < 220), 'table of contents link scrolls to its heading');

  const barWidth = () => page.$eval('.reading-progress .progress-bar', (e) => parseFloat(e.style.width));
  await page.evaluate(() => window.scrollTo(0, 0));
  await sleep(400);
  const atTop = await barWidth();
  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight / 2));
  await sleep(400);
  const inMiddle = await barWidth();
  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
  await sleep(400);
  const atEnd = await barWidth();
  check(atTop < 5 && inMiddle > 20 && inMiddle < 95 && atEnd === 100, `reading progress follows the scroll (${atTop}% / ${inMiddle}% / ${atEnd}%)`);

  const blocks = await page.$$eval('.code-display .code-language', (labels) => labels.map((l) => l.textContent));
  check(['css', 'js', 'html', 'php'].every((l) => blocks.includes(l)), `code blocks get a language label (${blocks.join(', ')})`);
  await page.click('.code-display .copy-button');
  await sleep(300);
  const copied = await page.evaluate(() => navigator.clipboard.readText());
  check(copied.startsWith(':root {') && !copied.includes('<span'), 'the code copy button copies the plain code');

  const firstCell = () => page.$eval('.is-style-sortable tbody tr td', (e) => e.textContent);
  const ariaSort = () => page.$eval('.is-style-sortable th:first-child', (e) => e.getAttribute('aria-sort'));
  check((await firstCell()) === 'Hard-coded values', 'sortable table starts in its original order');
  await page.click('.is-style-sortable th:first-child .sort-button');
  check((await firstCell()) === 'Full theming engine' && (await ariaSort()) === 'ascending', 'first click sorts ascending');
  await page.click('.is-style-sortable th:first-child .sort-button');
  check((await firstCell()) === 'Two token layers' && (await ariaSort()) === 'descending', 'second click sorts descending');
  await page.click('.is-style-sortable th:first-child .sort-button');
  check((await firstCell()) === 'Hard-coded values' && (await ariaSort()) === 'none', 'third click restores the order');

  await page.$eval('.entry-content .wp-block-image img', (e) => e.scrollIntoView({ block: 'center' }));
  await page.click('.entry-content .wp-block-image img');
  await sleep(700);
  const lightboxSrc = () => page.$eval('.lightbox-img', (e) => e.getAttribute('src')).catch(() => null);
  const firstSrc = await lightboxSrc();
  check(Boolean(firstSrc), 'clicking an image opens the lightbox');
  await page.keyboard.press('ArrowRight');
  await sleep(500);
  check((await lightboxSrc()) !== firstSrc, 'arrow key moves to the next image');
  await page.keyboard.press('Escape');
  await sleep(700);
  check((await lightboxSrc()) === null, 'Escape closes the lightbox');

  await page.$eval('.share', (e) => e.scrollIntoView({ block: 'center' }));
  await page.click('.share-button');
  await sleep(500);
  check(await page.$eval('.popover.is-open', (e) => e.textContent.includes('LinkedIn')).catch(() => false), 'share button opens a popover with the networks');
  await page.click('[data-share-copy]');
  await sleep(600);
  const shared = await page.evaluate(() => navigator.clipboard.readText());
  check(shared === page.url(), 'copy link copies the post address');
  check(await page.$eval('.toast', (e) => e.textContent.includes('Link copied')).catch(() => false), 'a toast confirms the copied link');

  check((await page.$eval('.author-box .author-name', (e) => e.textContent)) === 'Basix Editor', 'author box shows the author');
  const related = await page.$$eval('.related .card-title a', (links) => links.map((a) => a.href));
  check(related.length >= 1 && !related.includes(page.url()), `related posts are listed and exclude this post (${related.length})`);

  // Share on a phone uses a bottom sheet.
  page = await open('/everything-in-one-post/', { width: 390, height: 800 });
  await page.$eval('.share', (e) => e.scrollIntoView({ block: 'center' }));
  await page.click('.share-button');
  await sleep(700);
  check(await page.$eval('.bottom-sheet', (e) => e.textContent.includes('Copy link')).catch(() => false), 'share opens a bottom sheet on a phone');

  // Featured slider on the front page.
  page = await open('/');
  const slides = await page.$$eval('.featured .carousel-slide', (s) => s.length);
  check(slides >= 2, `front page shows a featured slider (${slides} slides)`);
  const trackPosition = () => page.$eval('.featured .carousel-track', (e) => e.style.transform);
  const before = await trackPosition();
  await page.click('.featured .carousel-button--right');
  await sleep(700);
  check((await trackPosition()) !== before, 'slider moves to the next slide');
  check(await page.$eval('.featured-title a', (e) => getComputedStyle(e).color === 'rgb(255, 255, 255)'), 'featured titles are white on the image');

  // Filters: chips, the modal with the Basix pickers, applying, and the results.
  page = await open('/');
  const chipNames = await page.$$eval('.filter-chips a.chip', (chips) => chips.map((c) => c.textContent.trim()));
  check(chipNames[0] === 'All' && chipNames.includes('Design') && chipNames.includes('Engineering'), `category chips are shown (${chipNames.join(', ')})`);
  await page.click('.filter-toggle');
  await sleep(900);
  check(Boolean(await page.$('.filter-host .filter-picker .group-picker, .filter-host .filter-picker')), 'Filter opens a modal with the category picker');
  await page.evaluate(() => [...document.querySelectorAll('.filter-picker button')].find((b) => /select all/i.test(b.textContent))?.click());
  await sleep(300);
  const chosen = await page.$eval('.filter-host input[name="categories"]', (e) => e.value);
  check(/^\d+$/.test(chosen), `choosing a category group sets the filter (${chosen})`);
  await page.click('.filter-host .datepicker-input');
  await sleep(500);
  const pickDay = (day) => page.evaluate((d) => [...document.querySelectorAll('.datepicker .datepicker-day:not(.other-month)')].find((e) => e.textContent.trim() === String(d))?.click(), day);
  await pickDay(5);
  await sleep(200);
  await pickDay(12);
  await sleep(400);
  const dates = await page.evaluate(() => [document.querySelector('.filter-host input[name="date_from"]').value, document.querySelector('.filter-host input[name="date_to"]').value]);
  check(/-05$/.test(dates[0]) && /-12$/.test(dates[1]), `the date picker sets a range (${dates.join(' to ')})`);
  await page.evaluate(() => document.querySelector('.filter-host button[type="submit"]').click());
  const applied = await page.waitForFunction(() => location.search.includes('categories=') && location.search.includes('date_from='), { timeout: 10000 }).then(() => true, () => false);
  check(applied, 'Apply loads the filtered list');
  await sleep(500);
  const activeChips = await page.$$eval('.filter-active a.chip', (chips) => chips.map((c) => c.textContent.trim()));
  check(activeChips.length >= 3 && /Design/.test(activeChips[0]), `active filters are shown as chips (${activeChips.join(' | ')})`);
  check((await page.$eval('.filter-toggle .badge', (e) => e.textContent.trim()).catch(() => '')) === '2', 'the Filter button shows how many filters are on');
  await page.click('.filter-active a.chip');
  await page.waitForFunction(() => !location.search.includes('categories='), { timeout: 10000 }).catch(() => {});
  check(!new URL(page.url()).searchParams.has('categories') && new URL(page.url()).searchParams.has('date_from'), 'removing a chip removes only that filter');

  // The filter actually filters.
  page = await open('/?categories=3');
  const badges = await page.$$eval('.post-grid .card', (cards) => cards.map((c) => c.querySelector('.post-terms')?.textContent ?? ''));
  check(badges.length > 0 && badges.every((t) => t.includes('Design')), `the category filter only lists posts in that category (${badges.length})`);
  page = await open('/?date_from=2026-10-20&date_to=2026-10-28');
  const days = await page.$$eval('.post-grid .card time', (times) => times.map((t) => t.getAttribute('datetime').slice(0, 10)));
  check(days.length > 0 && days.every((d) => d >= '2026-10-20' && d <= '2026-10-28'), `the date filter only lists posts in the range (${days.length})`);
  page = await open('/?categories=999999&date_from=nonsense');
  check((await page.$$('.post-grid .card')).length > 0 && !(await page.$('.filter-active')), 'unknown filter values are ignored, the normal list shows');

  // Without JavaScript the same filter is a plain form.
  const plain = await (await browser.createBrowserContext()).newPage();
  await plain.setJavaScriptEnabled(false);
  await plain.setViewport({ width: 1280, height: 800 });
  await plain.goto(`${url}/`, { waitUntil: 'load' });
  await plain.click('.filter-toggle');
  check(await plain.$eval('details.filter', (e) => e.open), 'without JavaScript the Filter button opens a plain form');
  await plain.$eval('input[name="date_from"]', (e) => { e.value = '2026-10-20'; });
  await Promise.all([plain.waitForNavigation(), plain.click('.filter-actions .button-primary')]);
  check(new URL(plain.url()).searchParams.get('date_from') === '2026-10-20', 'the plain form submits the filter');

  // Calendar: posts on their publish dates, reloaded when the visible range changes.
  page = await open('/calendar/');
  await page.waitForSelector('.cal__event-pill', { timeout: 10000 }).catch(() => {});
  const pillTitles = () => page.$$eval('.cal__event-pill', (pills) => pills.map((p) => p.textContent.trim()).sort().join('|'));
  const thisMonth = await pillTitles();
  check(thisMonth.length > 0, 'calendar shows posts on the days they were published');
  check(!(await page.$('.calendar-skeleton')), 'the skeleton is gone once the calendar is ready');
  await page.click('button[aria-label="Previous"]');
  await page.waitForFunction((before) => [...document.querySelectorAll('.cal__event-pill')].map((p) => p.textContent.trim()).sort().join('|') !== before, { timeout: 10000 }, thisMonth).catch(() => {});
  check((await pillTitles()) !== thisMonth, 'going to the previous month loads that month\'s posts');
  await page.click('.cal__btn--today');
  await page.waitForFunction((before) => [...document.querySelectorAll('.cal__event-pill')].map((p) => p.textContent.trim()).sort().join('|') === before, { timeout: 10000 }, thisMonth).catch(() => {});
  check((await pillTitles()) === thisMonth, 'Today returns to the current month');
  await page.click('.cal__event-pill');
  const leftCalendar = await page.waitForFunction(() => !location.pathname.startsWith('/calendar'), { timeout: 10000 }).then(() => true, () => false);
  check(leftCalendar, 'clicking a post in the calendar opens it');

  // Gallery page: masonry, endless loading that stops, and the lightbox with working icons.
  page = await open('/photo-gallery/');
  const mediaRequests = [];
  page.on('request', (r) => r.url().includes('wp/v2/media') && mediaRequests.push(r.url()));
  await page.reload({ waitUntil: 'networkidle0' });
  await sleep(1500);
  const tiles = await page.$$eval('.masonry-item:not(.masonry-item--skeleton)', (items) => items.length);
  check(tiles >= 12, `gallery loads the images from the media REST endpoint (${tiles})`);
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
  await sleep(2500);
  const settled = mediaRequests.length;
  await sleep(2000);
  check(mediaRequests.length === settled && settled <= 3, `gallery stops asking when the images run out (${settled} requests)`);
  check(await page.$eval('.gallery-wrap .loader', (e) => e.classList.contains('hidden')), 'the loader is hidden at the end');
  await page.click('.masonry-item:not(.masonry-item--skeleton) img');
  await sleep(800);
  check(Boolean(await page.$eval('.lightbox-img', (e) => e.getAttribute('src')).catch(() => null)), 'clicking a gallery image opens the lightbox');
  check(await page.$eval('.lightbox-close use', (u) => u.getAttribute('href').startsWith('http')).catch(() => false), 'lightbox icons use the theme icon path');
  await page.keyboard.press('Escape');
  const plainGallery = await (await browser.createBrowserContext()).newPage();
  await plainGallery.setJavaScriptEnabled(false);
  await plainGallery.goto(`${url}/photo-gallery/`, { waitUntil: 'load' });
  check((await plainGallery.$$('.gallery-fallback a')).length > 0, 'without JavaScript the first images are listed as links');

  // The Masonry style of the Gallery block.
  page = await open('/block-showcase/');
  await page.$eval('.wp-block-gallery.is-style-masonry', (e) => e.scrollIntoView({ block: 'center' }));
  await sleep(1200);
  const blockTiles = await page.$$eval('.wp-block-gallery.is-style-masonry .masonry-item:not(.masonry-item--skeleton)', (items) => items.length);
  check(blockTiles === 5, `the Masonry gallery block lays out its images (${blockTiles})`);
  await page.click('.wp-block-gallery.is-style-masonry .masonry-item:not(.masonry-item--skeleton) img');
  await sleep(800);
  check(Boolean(await page.$eval('.lightbox-img', (e) => e.getAttribute('src')).catch(() => null)), 'a masonry block image opens the lightbox');
  check((await page.$$('.lightbox-img')).length === 1, 'only one lightbox opens (no double binding)');

  // Loading states: slow the REST responses down and look at what the visitor sees meanwhile.
  const slowPage = async (path, pattern) => {
    const page = await open('/about/');
    await page.setRequestInterception(true);
    page.on('request', (request) => {
      if (request.url().includes(pattern)) {
        setTimeout(() => request.continue().catch(() => {}), 1800);
      } else {
        request.continue().catch(() => {});
      }
    });
    await page.goto(`${url}${path}`, { waitUntil: 'domcontentloaded' });
    return page;
  };

  page = await slowPage('/calendar/', 'wp/v2/posts');
  await page.waitForSelector('.cal', { timeout: 10000 });
  check(await page.$eval('.calendar-wrap', (e) => e.classList.contains('is-loading') && e.getAttribute('aria-busy') === 'true'), 'the calendar is marked busy while posts load');
  check(await page.$eval('.calendar-status', (e) => !e.hidden && Boolean(e.querySelector('.progress-bar.animated'))), 'an animated Basix progress bar shows while posts load');
  await page.waitForFunction(() => !document.querySelector('.calendar-wrap').classList.contains('is-loading'), { timeout: 10000 });
  check(await page.$eval('.calendar-status', (e) => e.hidden), 'the progress bar is hidden again afterwards');

  page = await slowPage('/photo-gallery/', 'wp/v2/media');
  await page.waitForSelector('.masonry-item--skeleton', { timeout: 10000 });
  const skeletons = await page.$$eval('.masonry-item--skeleton .placeholder', (e) => e.length);
  check(skeletons > 0, `the gallery shows Basix placeholder cards while images load (${skeletons})`);
  await page.waitForFunction(() => !document.querySelector('.masonry-item--skeleton') && document.querySelector('.masonry-item img'), { timeout: 15000 });
  check(true, 'the placeholder cards are replaced by the images');
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
