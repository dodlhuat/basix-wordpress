// Copies only the parts of @dodlhuat/basix that the theme actually uses into theme/assets/vendor/basix/:
//  - the stylesheet, font and the small icon files that the CSS references,
//  - the JS modules the theme imports (plus their dependencies),
//  - a trimmed icon sprite with just the icons the theme and those modules use.
// BASIX_PATH=../basix npm run sync  -> use a local checkout instead of node_modules.
import { cp, rm, mkdir, readdir, readFile, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const src = path.resolve(root, process.env.BASIX_PATH ?? 'node_modules/@dodlhuat/basix');
const themeDir = path.join(root, 'theme');
const dest = path.join(themeDir, 'assets/vendor/basix');

if (!existsSync(src)) {
  console.error(`basix not found at ${src} - run "npm install" first.`);
  process.exit(1);
}

/** All files under dir with one of the given extensions, skipping the vendor folder. */
async function walk(dir, exts) {
  const out = [];
  for (const entry of await readdir(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (full === path.join(themeDir, 'assets/vendor')) continue;
    if (entry.isDirectory()) out.push(...(await walk(full, exts)));
    else if (exts.includes(path.extname(entry.name))) out.push(full);
  }
  return out;
}

const read = (file) => readFile(file, 'utf8');

// Examples in comments must not count as usage.
const stripComments = (text) => text.replace(/\/\*[\s\S]*?\*\//g, '').replace(/^\s*\/\/.*$/gm, '');
const readSource = async (file) => (file.endsWith('.js') ? stripComments(await read(file)) : read(file));
const matches = (text, re) => [...text.matchAll(re)].map((m) => m[1]);

// 1. Source of truth: everything in the theme (PHP templates and its own JS).
const themeFiles = await walk(themeDir, ['.php', '.js']);
const themeText = (await Promise.all(themeFiles.map(readSource))).join('\n');

// 2. JS modules: the ones the theme imports, plus their imports, recursively.
const modules = new Set(matches(themeText, /vendor\/basix\/js\/([a-z0-9-]+)\.js/g));
const queue = [...modules];
while (queue.length) {
  const name = queue.pop();
  const file = path.join(src, 'js', `${name}.js`);
  if (!existsSync(file)) {
    console.error(`Theme imports basix/js/${name}.js, which does not exist in this Basix version.`);
    process.exit(1);
  }
  for (const dep of matches(await read(file), /from '\.\/([a-z0-9-]+)\.js'/g)) {
    if (!modules.has(dep)) {
      modules.add(dep);
      queue.push(dep);
    }
  }
}

// 3. Icons: names used by the theme, by the copied modules, and the optional extras list.
const moduleText = (await Promise.all([...modules].map((m) => read(path.join(src, 'js', `${m}.js`))))).join('\n');
const extras = JSON.parse(await read(path.join(root, 'scripts/basix-assets.json'))).icons ?? [];
const icons = new Set([
  ...matches(themeText + moduleText, /icons\.svg#([a-z0-9_-]+)/g),
  ...matches(themeText, /data-icon-(?:light|dark)="([a-z0-9_-]+)"/g),
  ...matches(moduleText, /icon\('([a-z0-9_-]+)'\)/g),
  ...extras,
]);

const sprite = await read(path.join(src, 'svg-icons/icons.svg'));
const symbols = new Map();
for (const m of sprite.matchAll(/<symbol id="([^"]+)"[\s\S]*?<\/symbol>/g)) symbols.set(m[1], m[0]);
const missing = [...icons].filter((id) => !symbols.has(id));
if (missing.length) {
  console.error(`Icons not found in the Basix sprite: ${missing.join(', ')}`);
  process.exit(1);
}

// 4. Write everything out.
await rm(dest, { recursive: true, force: true });
await mkdir(path.join(dest, 'css'), { recursive: true });
await mkdir(path.join(dest, 'js'), { recursive: true });
await mkdir(path.join(dest, 'svg-icons'), { recursive: true });

await cp(path.join(src, 'fonts'), path.join(dest, 'fonts'), { recursive: true });
const css = await read(path.join(src, 'css/style.min.css'));
await writeFile(path.join(dest, 'css/style.min.css'), css);

// The CSS refers to a few icons as standalone files (../svg-icons/x.svg); keep the directory layout.
for (const file of new Set(matches(css, /svg-icons\/([a-z0-9_-]+\.svg)/g))) {
  await cp(path.join(src, 'svg-icons', file), path.join(dest, 'svg-icons', file));
}

for (const name of [...modules].sort()) {
  await cp(path.join(src, 'js', `${name}.js`), path.join(dest, 'js', `${name}.js`));
}

const trimmed = `<svg xmlns="http://www.w3.org/2000/svg" style="display:none">\n  ${[...icons]
  .sort()
  .map((id) => symbols.get(id))
  .join('\n  ')}\n</svg>\n`;
await writeFile(path.join(dest, 'svg-icons/icons.svg'), trimmed);

const kb = (n) => `${(n / 1024).toFixed(1)} KB`;
console.log(
  `basix synced from ${src}: ${modules.size} JS modules (${[...modules].sort().join(', ')}), ` +
    `${icons.size} icons (sprite ${kb(Buffer.byteLength(trimmed))} instead of ${kb(Buffer.byteLength(sprite))})`,
);
