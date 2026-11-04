// The theme must work when the Basix Events plugin is off (that is how it is reviewed for the directory, and how many
// sites will run it): the plugin is switched off for the run, the main pages are visited, and it is switched on again.
// Needs the local site running (make up).
//   node scripts/check-without-plugin.mjs
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

const wasActive = wp('plugin', 'is-installed', 'basix-paperlight-events') === '' && wp('plugin', 'list', '--status=active', '--field=name').split('\n').includes('basix-paperlight-events');
const browser = await puppeteer.launch({ executablePath: chrome, headless: true });
try {
  wp('plugin', 'deactivate', 'basix-paperlight-events');
  wp('rewrite', 'flush');
  check(wp('eval', 'echo basix_has_events() ? "yes" : "no";') === 'no', 'the theme knows the plugin is off');

  const messages = /<b>(Warning|Notice|Fatal error|Deprecated)<\/b>|Fatal error:|Warning: |Uncaught /;
  const pages = ['/', '/everything-in-one-post/', '/calendar/', '/pattern-library/', '/landing-demo/', '/category/design/', '/?s=design', '/about/'];
  for (const path of pages) {
    const page = await (await browser.createBrowserContext()).newPage();
    await page.setViewport({ width: 1280, height: 900 });
    await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
    const response = await page.goto(`${url}${path}`, { waitUntil: 'networkidle0' });
    await sleep(500);
    const html = await page.content();
    let ok = response.status() === 200 && !messages.test(html);
    let detail = '';
    if (ok) {
      await page.evaluate(axeSource);
      const result = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
      const bad = result.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
      ok = bad.length === 0;
      detail = bad.map((v) => v.id).join(', ');
    }
    check(ok, `${path}: loads, no PHP messages, no serious axe problems${detail ? ` (${detail})` : ''}`);
    if (path === '/calendar/') {
      const source = await page.$eval('.calendar-wrap', (e) => e.dataset.config ?? e.getAttribute('data-basix-config') ?? e.outerHTML.slice(0, 600)).catch(() => '');
      check(String(source).includes('"source":"posts"') || String(source).includes('source&quot;:&quot;posts'), 'the calendar page shows the posts calendar when there are no events');
    }
    await page.close();
  }
  for (const path of ['/events/', '/events/feed.ics', '/event/design-systems-meetup/']) {
    const response = await fetch(`${url}${path}`);
    check(response.status === 404, `${path}: is not found without the plugin`);
  }

  // The setup screen offers the starters that need no plugin.
  const starters = JSON.parse(wp('basix', 'starter', 'list', '--format=json')).map((s) => s.slug);
  check(starters.join(',') === 'blog,magazine,business', `the events starter is left out (${starters.join(', ')})`);
  const imported = wp('basix', 'starter', 'import', 'blog');
  check(/Imported blog/.test(imported), 'a starter can still be imported');
  wp('basix', 'starter', 'undo');
} finally {
  if (wasActive) {
    wp('plugin', 'activate', 'basix-paperlight-events');
    wp('rewrite', 'flush');
  }
  await browser.close();
}
if (failures.length) {
  console.log(`\nFAILED: ${failures.length}`);
  process.exit(1);
}
console.log('\nWithout-plugin check passed.');
