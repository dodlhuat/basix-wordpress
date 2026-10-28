// Runs Lighthouse (performance, accessibility, best practices, SEO) on the main pages of the local site, on a
// simulated phone and on a desktop, and prints the scores. Slow (about a minute a page), so not part of make check.
//   node scripts/audit-lighthouse.mjs
// Env: WP_URL (default http://localhost:8090), CHROME (path to Chrome).
// Note: the local server is plain Apache without compression or caching headers, so performance scores here
// are lower than they will be on a real host.
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const pages = ['/', '/everything-in-one-post/', '/docs/getting-started/', '/calendar/', '/photo-gallery/'];
const out = mkdtempSync(join(tmpdir(), 'lighthouse-'));

const rows = [];
for (const form of ['mobile', 'desktop']) {
  for (const path of pages) {
    const file = join(out, `${form}-${path.replace(/\W+/g, '_')}.json`);
    const args = ['--yes', 'lighthouse@12', `${url}${path}`, '--output=json', `--output-path=${file}`, '--quiet',
      '--only-categories=performance,accessibility,best-practices,seo', '--chrome-flags=--headless=new --no-sandbox'];
    if (form === 'desktop') args.push('--preset=desktop');
    execFileSync('npx', args, { env: { ...process.env, CHROME_PATH: chrome }, stdio: ['ignore', 'ignore', 'inherit'] });
    const report = JSON.parse(readFileSync(file, 'utf8'));
    const score = (id) => Math.round((report.categories[id]?.score ?? 0) * 100);
    const audit = (id) => report.audits[id]?.displayValue ?? '-';
    rows.push({ form, path, performance: score('performance'), accessibility: score('accessibility'), bestPractices: score('best-practices'), seo: score('seo'), lcp: audit('largest-contentful-paint'), cls: audit('cumulative-layout-shift'), tbt: audit('total-blocking-time'), failed: Object.values(report.audits).filter((a) => a.score !== null && a.score < 0.9 && a.scoreDisplayMode === 'binary').map((a) => a.id) });
  }
}
console.log('form     page                          perf  a11y  best  seo   LCP      CLS    TBT');
for (const r of rows) {
  console.log(`${r.form.padEnd(8)} ${r.path.padEnd(29)} ${String(r.performance).padEnd(5)} ${String(r.accessibility).padEnd(5)} ${String(r.bestPractices).padEnd(5)} ${String(r.seo).padEnd(5)} ${String(r.lcp).padEnd(8)} ${String(r.cls).padEnd(6)} ${r.tbt}`);
}
const failing = new Map();
rows.forEach((r) => r.failed.forEach((id) => failing.set(id, (failing.get(id) ?? 0) + 1)));
if (failing.size) console.log(`\nAudits that did not pass: ${[...failing.entries()].map(([id, n]) => `${id} (${n})`).join(', ')}`);
