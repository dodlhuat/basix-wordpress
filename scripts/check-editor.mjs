// Opens the block editor in headless Chrome, inserts every basix/* pattern and reports blocks that
// fail the editor's own validation. Needs the local site running (make up).
//   node scripts/check-editor.mjs [screenshot.png]
// Env: WP_URL (default http://localhost:8090), WP_USER / WP_PASS (admin / admin), CHROME (path to Chrome).
import puppeteer from 'puppeteer-core';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const screenshot = process.argv[2];

const browser = await puppeteer.launch({ executablePath: chrome, headless: true, defaultViewport: { width: 1400, height: 1000 } });
try {
  const page = await browser.newPage();
  const problems = [];
  page.on('pageerror', (e) => problems.push(`page error: ${e.message}`));

  await page.goto(`${url}/wp-login.php`);
  await page.type('#user_login', process.env.WP_USER ?? 'admin');
  await page.type('#user_pass', process.env.WP_PASS ?? 'admin');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);

  await page.goto(`${url}/wp-admin/post-new.php`, { waitUntil: 'networkidle2' });
  await page.waitForFunction(() => window.wp?.data?.select('core/block-editor') && document.querySelector('.editor-styles-wrapper, iframe[name="editor-canvas"]'));
  await page.evaluate(() => {
    wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
    wp.data.dispatch('core/preferences').set('core/edit-post', 'fullscreenMode', false);
    wp.data.dispatch('core/edit-post').closeGeneralSidebar?.();
  });

  const result = await page.evaluate(async () => {
    const patterns = (await wp.apiFetch({ path: '/wp/v2/block-patterns/patterns' })).filter((p) => p.name.startsWith('basix/'));
    const invalid = [];
    const walk = (blocks, pattern) => blocks.forEach((b) => {
      if (!b.isValid) invalid.push(`${pattern}: ${b.name} ${JSON.stringify((b.validationIssues ?? []).map((i) => i.args ?? i.message).slice(0, 2))}`);
      walk(b.innerBlocks, pattern);
    });
    for (const p of patterns) {
      const blocks = wp.blocks.parse(p.content);
      walk(blocks, p.name);
      wp.data.dispatch('core/block-editor').insertBlocks(blocks);
    }
    // The seeded showcase page uses the core blocks the theme styles; check it as well when it exists.
    const [showcase] = await wp.apiFetch({ path: '/wp/v2/pages?slug=block-showcase&context=edit' });
    if (showcase) walk(wp.blocks.parse(showcase.content.raw), 'page:block-showcase');
    return { names: patterns.map((p) => p.name), invalid, showcase: Boolean(showcase) };
  });

  console.log(`Patterns checked: ${result.names.join(', ') || '(none)'}${result.showcase ? ' + the block showcase page' : ''}`);
  if (screenshot) {
    await new Promise((r) => setTimeout(r, 1500));
    await page.screenshot({ path: screenshot, fullPage: false });
  }
  const failures = [...result.invalid, ...problems];
  if (failures.length || !result.names.length) {
    console.error(`FAILED:\n${failures.join('\n') || 'no basix patterns found'}`);
    process.exitCode = 1;
  } else {
    console.log('All blocks are valid in the editor.');
  }
} finally {
  await browser.close();
}
