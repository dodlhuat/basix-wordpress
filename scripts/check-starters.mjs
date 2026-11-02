// Tries the setup screen (Appearance > Basix setup) in a real browser, then imports every starter site and scans its
// pages with axe in light and dark mode on a desktop and on a phone. Each import is undone at the end, so the site is
// left as it was found. Needs the local site running (make up).
//   node scripts/check-starters.mjs
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
const rgb = (hex) => `rgb(${[1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(', ')})`;
const looks = Object.fromEntries(
  ['basix', 'editorial', 'bold', 'calm', 'paper'].map((slug) => [slug, JSON.parse(readFileSync(`theme/styles/${slug}.json`, 'utf8')).settings.custom.basix]),
);
const starters = JSON.parse(wp('basix', 'starter', 'list', '--format=json'));

const launch = () => puppeteer.launch({ executablePath: chrome, headless: true });
let browser = await launch();
try {
  // The setup screen.
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });
  await page.goto(`${url}/wp-login.php`);
  await page.type('#user_login', process.env.WP_USER ?? 'admin');
  await page.type('#user_pass', process.env.WP_PASS ?? 'admin');
  await Promise.all([page.waitForNavigation(), page.click('#wp-submit')]);
  await page.goto(`${url}/wp-admin/themes.php?page=basix-setup`, { waitUntil: 'networkidle2' });
  check((await page.$$('input[name="look"]')).length === 6, 'the setup screen offers the five looks and "the starter\'s own"');
  check((await page.$$('input[name="starter"]')).length === starters.length + 1, `and ${starters.length} starter sites and "only the look"`);
  check((await page.$$('.undo')).length === 0, 'there is no undo box before an import');

  await page.click('input[name="starter"][value="blog"]');
  await page.click('input[name="look"][value="paper"]');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('button[type="submit"].button-primary')]);
  check((await page.$eval('.notice-success', (n) => n.textContent)).includes('Blog'), 'setting up a starter site says it is ready');
  check((await page.$$('.undo')).length === 1, 'and offers to undo it');
  const site = await browser.newPage();
  await site.evaluateOnNewDocument(() => localStorage.setItem('theme', 'light'));
  await site.goto(`${url}/`, { waitUntil: 'networkidle0' });
  check((await site.$$eval('.post-grid .card', (c) => c.length)) >= 6, 'the site shows the six sample posts');
  check((await site.$eval('body', (b) => getComputedStyle(b).backgroundColor)) === rgb(looks.paper.light.background), 'and the look chosen on the screen (Paper), not the starter\'s own');
  await site.close();
  await page.goto(`${url}/wp-admin/themes.php?page=basix-setup`, { waitUntil: 'networkidle2' });
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('.undo button')]);
  check((await page.$eval('.notice-success', (n) => n.textContent)).includes('undone') && (await page.$$('.undo')).length === 0, 'undo removes the import and the box');
  check(JSON.stringify(JSON.parse(wp('basix', 'starter', 'status', '--format=json'))) === '[]', 'and nothing is left over');
  await page.close();

  // Every starter site, imported and scanned.
  const setups = [
    { name: 'desktop light', width: 1280, mode: 'light' },
    { name: 'desktop dark', width: 1280, mode: 'dark' },
    { name: 'phone light', width: 390, mode: 'light' },
  ];
  const scan = async (starter, address, setup) => {
    const problems = [];
    const p = await (await browser.createBrowserContext()).newPage();
    try {
      await p.setViewport({ width: setup.width, height: 900 });
      await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
      await p.evaluateOnNewDocument((mode) => localStorage.setItem('theme', mode), setup.mode);
      const response = await p.goto(address, { waitUntil: 'networkidle0' });
      await sleep(600);
      const path = new URL(address).pathname + new URL(address).search;
      if (!response || response.status() !== 200) problems.push(`${path}: status ${response?.status()}`);
      const html = await p.content();
      if (/<b>(Warning|Notice|Fatal error|Deprecated)<\/b>|Fatal error:|Warning: /.test(html)) problems.push(`${path}: a PHP message`);
      const want = looks[starter.look][setup.mode].background;
      const have = await p.$eval('body', (b) => getComputedStyle(b).backgroundColor);
      if (want && have !== rgb(want)) problems.push(`${setup.name} ${path}: background ${have}`);
      if (!address.endsWith('.ics')) {
        await p.evaluate(axeSource);
        const result = await p.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
        for (const v of result.violations.filter((x) => ['serious', 'critical'].includes(x.impact))) {
          problems.push(`${setup.name} ${path}: ${v.id} (${v.nodes.length}) ${v.nodes[0].target.join(' ')}`);
        }
      }
    } finally {
      await p.close().catch(() => {});
    }
    return problems;
  };
  for (const starter of starters) {
    wp('basix', 'starter', 'import', starter.slug);
    const record = JSON.parse(wp('basix', 'starter', 'status', '--format=json'));
    const addresses = record.urls.filter((a) => !a.includes('feed') && !a.endsWith('.ics')).slice(0, 8);
    const problems = [];
    let scanned = 0;
    for (const address of addresses) {
      for (const setup of setups) {
        for (let attempt = 1; ; attempt++) {
          try {
            problems.push(...(await scan(starter, address, setup)));
            break;
          } catch (error) {
            if (attempt === 3) throw error;
            if (!browser.connected) browser = await launch();
            await sleep(1000);
          }
        }
        scanned++;
      }
    }
    check(problems.length === 0, `${starter.slug}: ${addresses.length} pages in ${setups.length} setups (${scanned} scans), look ${starter.look}${problems.length ? `\n     ${[...new Set(problems)].slice(0, 6).join('\n     ')}` : ''}`);
    wp('basix', 'starter', 'undo');
  }
} finally {
  try {
    wp('basix', 'starter', 'undo');
  } catch {
    // nothing to undo
  }
  await browser.close();
}
if (failures.length) {
  console.log(`\nFAILED: ${failures.length}`);
  process.exit(1);
}
console.log('\nStarter sites check passed.');
