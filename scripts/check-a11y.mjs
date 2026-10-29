// Runs the axe accessibility engine on the main pages of the local site, in light and dark mode, on a desktop
// and on a phone. Violations are listed by rule; serious and critical ones fail the check.
//   node scripts/check-a11y.mjs [--all]     (--all also lists moderate and minor findings)
// Env: WP_URL (default http://localhost:8090), CHROME (path to Chrome).
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import puppeteer from 'puppeteer-core';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const showAll = process.argv.includes('--all');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const pages = ['/', '/everything-in-one-post/', '/about/', '/?s=design', '/category/design/', '/docs/getting-started/', '/calendar/', '/events/', '/event/design-systems-meetup/', '/photo-gallery/', '/block-showcase/', '/landing-demo/', '/members-only/', '/nope/'];
const setups = [
  { name: 'desktop light', width: 1280, theme: 'light' },
  { name: 'desktop dark', width: 1280, theme: 'dark' },
  { name: 'phone light', width: 390, theme: 'light' },
  { name: 'phone dark', width: 390, theme: 'dark' },
];

const browser = await puppeteer.launch({ executablePath: chrome, headless: true });
const found = new Map(); // rule -> { impact, help, nodes: Set("setup path target") }
let scanned = 0;
try {
  for (const setup of setups) {
    for (const path of pages) {
      const page = await (await browser.createBrowserContext()).newPage();
      await page.setViewport({ width: setup.width, height: 900 });
      // Reduced motion: the checks look at the finished page, not at things mid-fade.
      await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
      await page.evaluateOnNewDocument((theme) => localStorage.setItem('theme', theme), setup.theme);
      await page.goto(`${url}${path}`, { waitUntil: 'networkidle0' });
      await sleep(900);
      await page.evaluate(axeSource);
      const result = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
      scanned++;
      for (const violation of result.violations) {
        const entry = found.get(violation.id) ?? { impact: violation.impact, help: violation.help, nodes: new Set() };
        violation.nodes.forEach((node) => entry.nodes.add(`${setup.name} | ${path} | ${node.target.join(' ')}`));
        found.set(violation.id, entry);
      }
      await page.close();
    }
  }
} finally {
  await browser.close();
}

const rank = { critical: 0, serious: 1, moderate: 2, minor: 3 };
const rules = [...found.entries()].sort((a, b) => rank[a[1].impact] - rank[b[1].impact]);
let blocking = 0;
console.log(`Scanned ${scanned} pages (${pages.length} pages x ${setups.length} setups).`);
if (!rules.length) console.log('No violations.');
for (const [id, entry] of rules) {
  const isBlocking = ['critical', 'serious'].includes(entry.impact);
  if (isBlocking) blocking += entry.nodes.size;
  if (!isBlocking && !showAll) {
    console.log(`  (${entry.impact}) ${id}: ${entry.nodes.size} places - run with --all to list`);
    continue;
  }
  console.log(`\n[${entry.impact}] ${id}: ${entry.help} (${entry.nodes.size})`);
  [...entry.nodes].slice(0, 12).forEach((n) => console.log(`    ${n}`));
  if (entry.nodes.size > 12) console.log(`    ... and ${entry.nodes.size - 12} more`);
}
if (blocking) {
  console.error(`\nFAILED: ${blocking} serious or critical accessibility problems.`);
  process.exitCode = 1;
} else {
  console.log('\nAccessibility check passed (no serious or critical violations).');
}
