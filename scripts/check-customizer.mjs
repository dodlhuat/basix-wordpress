// Opens the Customizer in headless Chrome, checks the Basix panel, changes the header layout, saves,
// checks the preview, then restores the original value. Needs the local site running (make up).
//   node scripts/check-customizer.mjs
// Env: WP_URL (default http://localhost:8090), WP_USER / WP_PASS (admin / admin), CHROME (path to Chrome).
import puppeteer from 'puppeteer-core';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const expectedControls = ['custom_logo', 'basix_header_layout', 'basix_color_mode', 'basix_accent_light', 'basix_accent_dark', 'basix_sidebar_position', 'basix_footer_text', 'basix_back_to_top', 'basix_show_reading_time', 'basix_show_toc', 'basix_show_progress', 'basix_show_share', 'basix_show_author_box', 'basix_show_related', 'basix_show_featured', 'basix_card_layout', 'basix_hero_style', 'basix_look', 'basix_font_heading', 'basix_font_body', 'basix_text_size'];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await puppeteer.launch({ executablePath: chrome, headless: true, defaultViewport: { width: 1400, height: 900 } });
const failures = [];
try {
  const page = await browser.newPage();
  page.on('pageerror', (e) => failures.push(`page error: ${e.message}`));

  await page.goto(`${url}/wp-login.php`);
  await page.type('#user_login', process.env.WP_USER ?? 'admin');
  await page.type('#user_pass', process.env.WP_PASS ?? 'admin');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

  await page.goto(`${url}/wp-admin/customize.php`, { waitUntil: 'networkidle2' });
  await page.waitForFunction(() => window.wp?.customize?.panel?.('basix') && wp.customize.previewer?.targetWindow?.());

  const found = await page.evaluate((ids) => ({
    sections: wp.customize.panel('basix').sections().map((s) => s.id),
    missing: ids.filter((id) => !wp.customize.control(id)),
  }), expectedControls);
  console.log(`Panel sections: ${found.sections.join(', ')}`);
  if (found.sections.length !== 8) failures.push(`expected 8 sections, got ${found.sections.length}`);
  if (found.missing.length) failures.push(`missing controls: ${found.missing.join(', ')}`);

  // The post list layout and the front page introduction change the preview.
  const preview = async (setting, value, ready, label) => {
    await page.evaluate((s, v) => wp.customize(s).set(v), setting, value);
    await page.waitForFunction((src) => { const w = wp.customize.previewer.targetWindow(); return w?.document?.body && new Function('w', `return (${src})(w)`)(w); }, { timeout: 20000 }, ready.toString())
      .catch(() => failures.push(`preview did not show ${label}`));
  };
  const originalHero = await page.evaluate(() => wp.customize('basix_hero_style').get());
  const originalCards = await page.evaluate(() => wp.customize('basix_card_layout').get());
  await preview('basix_hero_style', 'minimal', (w) => w.document.querySelector('.hero-minimal') && !w.document.querySelector('.hero.card'), 'the minimal introduction');
  await preview('basix_hero_style', 'none', (w) => !w.document.querySelector('.hero-minimal, .hero.card'), 'no introduction');
  await preview('basix_card_layout', 'list', (w) => w.document.body.classList.contains('cards-list') && getComputedStyle(w.document.querySelector('.post-grid > .card')).display === 'grid', 'the list layout');
  await preview('basix_card_layout', 'magazine', (w) => { const first = w.document.querySelector('.post-grid > .card.is-first'); return first && getComputedStyle(first).gridColumnEnd === '-1'; }, 'the magazine layout');
  console.log('Preview shows the introduction and post list layout settings');
  // A look changes colours and fonts in the preview at once, and the text size scales the page.
  const originalLook = await page.evaluate(() => wp.customize('basix_look').get());
  await preview('basix_look', 'editorial', (w) => getComputedStyle(w.document.documentElement).getPropertyValue('--radius-md').trim() === '0.15rem' && getComputedStyle(w.document.body).fontFamily.startsWith('"Source Serif 4"'), 'the Editorial look');
  await preview('basix_text_size', 'large', (w) => getComputedStyle(w.document.documentElement).fontSize === '18px', 'the large text size');
  console.log('Preview shows a look and the text size');
  await page.evaluate((look) => { wp.customize('basix_look').set(look); wp.customize('basix_text_size').set('medium'); }, originalLook);

  await page.evaluate((hero, cards) => { wp.customize('basix_hero_style').set(hero); wp.customize('basix_card_layout').set(cards); }, originalHero, originalCards);

  const original = await page.evaluate(() => wp.customize('basix_header_layout').get());
  const target = original === 'flyout' ? 'inline' : 'flyout';
  await page.evaluate((v) => wp.customize('basix_header_layout').set(v), target);
  await page.waitForFunction((t) => {
    const w = wp.customize.previewer.targetWindow();
    return w?.document?.body?.classList.contains(`header-layout-${t}`);
  }, { timeout: 20000 }, target).catch(() => failures.push(`preview did not switch to ${target}`));
  console.log(`Preview shows header-layout-${target}`);

  await page.evaluate(() => wp.customize.previewer.save());
  await page.waitForFunction(() => wp.customize.state('saved').get() === true, { timeout: 20000 }).catch(() => failures.push('settings were not saved'));
  console.log('Saved');

  // restore, so the check leaves the site as it found it
  await page.evaluate((v) => wp.customize('basix_header_layout').set(v), original);
  await page.evaluate(() => wp.customize.previewer.save());
  await page.waitForFunction(() => wp.customize.state('saved').get() === true, { timeout: 20000 }).catch(() => failures.push('could not restore the original setting'));
  await sleep(300);
} finally {
  await browser.close();
}
if (failures.length) {
  console.error(`FAILED:\n${failures.join('\n')}`);
  process.exitCode = 1;
} else {
  console.log('Customizer check passed.');
}
