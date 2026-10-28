// Copies the shipped parts of @dodlhuat/basix into the theme so it runs without npm.
// BASIX_PATH=../basix npm run sync  -> use a local checkout instead of node_modules.
import { cp, rm, mkdir, readdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const src = path.resolve(root, process.env.BASIX_PATH ?? 'node_modules/@dodlhuat/basix');
const dest = path.join(root, 'theme/assets/vendor/basix');

if (!existsSync(src)) {
  console.error(`basix not found at ${src} - run "npm install" first.`);
  process.exit(1);
}

await rm(dest, { recursive: true, force: true });
await mkdir(dest, { recursive: true });

// CSS references ../fonts and ../svg-icons, so keep the directory layout.
await cp(path.join(src, 'fonts'), path.join(dest, 'fonts'), { recursive: true });
await cp(path.join(src, 'svg-icons'), path.join(dest, 'svg-icons'), { recursive: true });

await mkdir(path.join(dest, 'css'));
await cp(path.join(src, 'css/style.min.css'), path.join(dest, 'css/style.min.css'));

await mkdir(path.join(dest, 'js'));
for (const f of await readdir(path.join(src, 'js'))) {
  if (f.endsWith('.js')) await cp(path.join(src, 'js', f), path.join(dest, 'js', f));
}

console.log(`basix synced from ${src}`);
