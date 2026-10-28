// Opens the Customizer in headless Chrome, checks the Basix panel, changes the header layout, saves,
// checks the preview, then restores the original value. Needs the local site running (make up).
//   node scripts/check-customizer.mjs
// Env: WP_URL (default http://localhost:8090), WP_USER / WP_PASS (admin / admin), CHROME (path to Chrome).
import puppeteer from 'puppeteer-core';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const expectedControls = ['custom_logo', 'basix_header_layout', 'basix_color_mode', 'basix_accent_light', 'basix_accent_dark', 'basix_sidebar_position', 'basix_footer_text', 'basix_back_to_top'];
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
  if (found.sections.length !== 4) failures.push(`expected 4 sections, got ${found.sections.length}`);
  if (found.missing.length) failures.push(`missing controls: ${found.missing.join(', ')}`);

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
