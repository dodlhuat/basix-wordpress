// Visits every page of a site that holds the official Theme Unit Test data (the test content the WordPress theme review
// team uses) and reports: addresses that are not found or print a PHP message, pages that overflow sideways on a phone
// or a desktop, script errors, broken pictures, and serious or critical axe problems. It also sets the edge-case
// settings the theme handbook asks for (a very long site title and tagline, five posts per page, threaded and paged
// comments, no large image size) and puts the old values back. Not part of `make check`, because it needs the test
// content in the site: see docs/developers.md ("Testing with the official test data").
//   node scripts/audit-unit-data.mjs
// Env: CHROME (path to Chrome). The addresses come from the site WP-CLI talks to (the Docker site).
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import puppeteer from 'puppeteer-core';

const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const wp = (...args) => execFileSync('docker', ['compose', 'run', '--rm', '-T', 'wpcli', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'], maxBuffer: 64 * 1024 * 1024 }).trim();

const posts = Number(wp('post', 'list', '--post_type=any', '--post_status=publish', '--format=count'));
if (posts < 100) {
  console.error(`Only ${posts} published items: import the Theme Unit Test data first (docs/developers.md).`);
  process.exit(2);
}

// The addresses: every public item, every term, every author, and a few list pages, a search and a missing page.
const addresses = wp('eval', `
$urls = array();
foreach ( get_posts( array( 'post_type' => 'any', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $p ) { $urls[] = get_permalink( $p ); }
foreach ( array( 'category', 'post_tag' ) as $t ) { foreach ( get_terms( array( 'taxonomy' => $t, 'hide_empty' => false ) ) as $term ) { $urls[] = get_term_link( $term ); } }
foreach ( get_users() as $u ) { $urls[] = get_author_posts_url( $u->ID ); }
foreach ( array( '/', '/page/2/', '/page/3/', '/?s=lorem', '/?s=', '/?s=zzzzzzqq', '/2013/', '/2013/01/' ) as $p ) { $urls[] = home_url( $p ); }
echo implode( "\\n", array_unique( array_filter( $urls ) ) );`).split('\n').filter((u) => u.startsWith('http'));

const settings = {
  blogname: 'A very long site title that keeps going and going to see what the header does when the name is far too long for one line',
  blogdescription: 'An even longer tagline that goes on for a very long time, because someone will one day write a tagline that is longer than a sentence has any business being, and the theme should still look fine',
  posts_per_page: '5',
  thread_comments: '1',
  thread_comments_depth: '5',
  page_comments: '1',
  comments_per_page: '5',
  large_size_w: '0',
  large_size_h: '0',
};
const before = Object.fromEntries(Object.keys(settings).map((k) => [k, wp('option', 'get', k)]));
const failures = [];
const browser = await puppeteer.launch({ executablePath: chrome, headless: true });
try {
  for (const [k, v] of Object.entries(settings)) wp('option', 'update', k, v);
  console.log(`${addresses.length} addresses, ${posts} items`);

  const messages = /<b>(Warning|Notice|Fatal error|Deprecated)<\/b>|Fatal error:|Warning: |Uncaught /;
  for (const address of addresses) {
    const response = await fetch(address, { redirect: 'follow' });
    const html = await response.text();
    if (response.status !== 200) failures.push(`${address}: status ${response.status}`);
    if (messages.test(html)) failures.push(`${address}: a PHP message`);
  }

  const seen = { overflow: 0, errors: 0, broken: 0, axe: new Map() };
  for (const address of addresses) {
    for (const width of [390, 1280]) {
      const context = await browser.createBrowserContext();
      const page = await context.newPage();
      await page.setViewport({ width, height: 900 });
      await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
      const errors = [];
      page.on('pageerror', (e) => errors.push(String(e.message).slice(0, 120)));
      try {
        await page.goto(address, { waitUntil: 'load', timeout: 30000 });
        await sleep(350);
        const facts = await page.evaluate(() => ({
          overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
          broken: [...document.images].filter((i) => i.complete && i.naturalWidth === 0).length,
        }));
        if (facts.overflow) { seen.overflow++; failures.push(`${address} (${width}px): sticks out sideways`); }
        if (facts.broken) { seen.broken++; failures.push(`${address}: ${facts.broken} broken pictures`); }
        if (width === 1280) {
          await page.evaluate(axeSource);
          const result = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
          for (const v of result.violations.filter((x) => ['serious', 'critical'].includes(x.impact))) {
            seen.axe.set(v.id, [...(seen.axe.get(v.id) ?? []), `${address} ${v.nodes[0].target.join(' ').slice(0, 70)}`]);
          }
        }
      } catch (e) {
        errors.push(String(e.message).slice(0, 100));
      }
      if (errors.length) { seen.errors++; failures.push(`${address} (${width}px): ${errors[0]}`); }
      await context.close();
    }
  }
  for (const [rule, where] of seen.axe) {
    console.log(`axe ${rule}: ${where.length} pages, for example ${where[0]}`);
  }
  console.log(`\noverflow pages: ${seen.overflow}, pages with script errors: ${seen.errors}, pages with broken pictures: ${seen.broken}, axe rules: ${seen.axe.size}`);
} finally {
  for (const [k, v] of Object.entries(before)) wp('option', 'update', k, v);
  await browser.close();
}
if (failures.length) {
  console.log(`\n${failures.length} problems:\n${failures.slice(0, 40).join('\n')}`);
  process.exit(1);
}
console.log('\nUnit test data audit finished without problems (except any axe rules listed above, which come from the content).');
