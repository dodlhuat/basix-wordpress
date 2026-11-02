// Switches the site through every look (theme/styles/*.json) and, in light and dark mode on the main pages, checks that
// axe finds nothing serious, the look's background and fonts are really in use, and the fonts have loaded. The
// look that was set is put back at the end. Needs the local site running (make up).
//   node scripts/check-looks.mjs
// Env: WP_URL (default http://localhost:8090), CHROME (path to Chrome).
import { execFileSync } from 'node:child_process';
import { readFileSync, readdirSync } from 'node:fs';
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

const fontNames = { outfit: 'Outfit', inter: 'Inter', 'space-grotesk': 'Space Grotesk', 'source-serif-4': 'Source Serif 4', fraunces: 'Fraunces' };
const looks = readdirSync('theme/styles')
  .filter((file) => file.endsWith('.json'))
  .map((file) => ({ slug: file.replace(/\.json$/, ''), ...JSON.parse(readFileSync(`theme/styles/${file}`, 'utf8')).settings.custom.basix }))
  .sort((a, b) => a.order - b.order);
const pages = ['/', '/everything-in-one-post/', '/events/', '/calendar/', '/event/design-systems-meetup/', '/docs/getting-started/'];
const rgb = (hex) => `rgb(${[1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(', ')})`;

const original = wp('theme', 'mod', 'get', 'basix_look') || 'basix';
const launch = () => puppeteer.launch({ executablePath: chrome, headless: true });
let browser = await launch();
const scanPage = async (look, mode, path) => {
  const problems = [];
  const page = await (await browser.createBrowserContext()).newPage();
  try {
    await page.setViewport({ width: 1280, height: 900 });
    await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
    await page.evaluateOnNewDocument((theme) => localStorage.setItem('theme', theme), mode);
    await page.goto(`${url}${path}`, { waitUntil: 'networkidle0' });
    await sleep(700);
    const used = await page.evaluate(async (bodyFont, headFont) => {
      await document.fonts.ready;
      const first = (el) => getComputedStyle(el).fontFamily.split(',')[0].replaceAll('"', '').trim();
      const heading = document.querySelector('h1, h2');
      return {
        background: getComputedStyle(document.body).backgroundColor,
        body: first(document.body),
        heading: heading ? first(heading) : '',
        bodyLoaded: document.fonts.check(`16px "${bodyFont}"`),
        headingLoaded: document.fonts.check(`16px "${headFont}"`),
      };
    }, fontNames[look.fonts.body], fontNames[look.fonts.heading]);
    const want = look[mode].background;
    if (want && used.background !== rgb(want)) problems.push(`${mode} ${path}: background ${used.background}, wanted ${rgb(want)}`);
    if (used.body !== fontNames[look.fonts.body]) problems.push(`${path}: body font ${used.body}`);
    if (used.heading && used.heading !== fontNames[look.fonts.heading]) problems.push(`${path}: heading font ${used.heading}`);
    if (!used.bodyLoaded || !used.headingLoaded) problems.push(`${path}: a font did not load`);
    await page.evaluate(axeSource);
    const result = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
    for (const violation of result.violations.filter((v) => ['serious', 'critical'].includes(v.impact))) {
      problems.push(`${mode} ${path}: ${violation.id} (${violation.nodes.length}) ${violation.nodes[0].target.join(' ')}`);
    }
  } finally {
    await page.close().catch(() => {});
  }
  return problems;
};

try {
  for (const look of looks) {
    wp('theme', 'mod', 'set', 'basix_look', look.slug);
    const problems = [];
    let scanned = 0;
    for (const mode of ['light', 'dark']) {
      for (const path of pages) {
        // Chrome now and then drops a page when many are opened in a row; try again before giving up.
        for (let attempt = 1; ; attempt++) {
          try {
            problems.push(...(await scanPage(look, mode, path)));
            break;
          } catch (error) {
            if (attempt === 3) throw error;
            if (!browser.connected) browser = await launch(); // Chrome itself went away: start a new one
            await sleep(1000);
          }
        }
        scanned++;
      }
    }
    check(problems.length === 0, `${look.slug}: ${scanned} pages in light and dark, colours, fonts and axe${problems.length ? `\n     ${[...new Set(problems)].slice(0, 6).join('\n     ')}` : ''}`);
  }
} finally {
  wp('theme', 'mod', 'set', 'basix_look', original);
  await browser.close();
}
if (failures.length) {
  console.log(`\nFAILED: ${failures.length} look(s)`);
  process.exit(1);
}
console.log('\nLooks browser check passed.');
