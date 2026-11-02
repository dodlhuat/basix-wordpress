// Makes the right-to-left versions of the stylesheets with RTLCSS, which mirrors left and right, margins, paddings,
// borders, transforms and so on. WordPress swaps in "-rtl" files by itself on a right-to-left site.
//   node scripts/build-rtl.mjs           writes the theme's files (committed) and Basix's (generated, in assets/vendor)
//   node scripts/build-rtl.mjs --check   only checks that the committed theme files are up to date
// Things that cannot be mirrored in CSS are written into the sources in /*rtl:raw: ... */ comments, which only the
// right-to-left version contains.
import { readFile, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import rtlcss from 'rtlcss';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const theme = (file) => path.join(root, 'theme', file);
const check = process.argv.includes('--check');

const files = [
  ['style.css', 'style-rtl.css'],
  ['assets/css/content.css', 'assets/css/content-rtl.css'],
  ['assets/css/polish.css', 'assets/css/polish-rtl.css'],
  ['assets/css/forms.css', 'assets/css/forms-rtl.css'],
];
const vendor = [['assets/vendor/basix/css/style.min.css', 'assets/vendor/basix/css/style.min-rtl.css']];

const flip = (css) => rtlcss.process(css);
let stale = 0;
for (const [from, to] of check ? files : [...files, ...vendor]) {
  if (!existsSync(theme(from))) {
    console.error(`${from} is missing (run npm run sync first)`);
    process.exit(1);
  }
  const out = flip(await readFile(theme(from), 'utf8'));
  if (check) {
    const have = existsSync(theme(to)) ? await readFile(theme(to), 'utf8') : '';
    if (have !== out) {
      console.error(`${to} is out of date: run node scripts/build-rtl.mjs`);
      stale++;
    }
  } else {
    await writeFile(theme(to), out);
  }
}
if (check) {
  if (stale) process.exit(1);
  console.log('Right-to-left stylesheets are up to date.');
}
