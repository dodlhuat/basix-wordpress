// The Customizer preview works out the accent colours in JavaScript (theme/assets/js/accent-math.js); the site does it in
// PHP (inc/customizer.php). They must agree, or the preview would show something the site does not. This compares both on
// a spread of colours, in both modes, against the pages and surfaces of every look. Needs the local site running (make up).
//   node scripts/check-accent-math.mjs
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

await import('../theme/assets/js/accent-math.js');
const js = globalThis.basixAccent;

const looks = ['basix', 'editorial', 'bold', 'calm', 'paper'].map((slug) => JSON.parse(readFileSync(`theme/styles/${slug}.json`, 'utf8')).settings.custom.basix);
const shipped = { light: { page: '#f9f9fb', surface: '#e7e8ec' }, dark: { page: '#111113', surface: '#19191b' } };
const pairs = [];
for (const look of looks) {
  for (const mode of ['light', 'dark']) {
    pairs.push({ mode, page: look[mode].background ?? shipped[mode].page, surface: look[mode].surface ?? shipped[mode].surface });
  }
}
// A spread of colours: the primaries, greys, the looks' accents, and a deterministic pseudo-random set.
const colours = ['#3d63dd', '#1971a6', '#b4491f', '#6d28d9', '#1f7a55', '#111111', '#e8e8e8', '#ffffff', '#000000', '#ff0000', '#00ff00', '#0000ff', '#ffff00', '#808080', '#fc0', '#0af'];
let seed = 12345;
const next = () => (seed = (seed * 1103515245 + 12345) & 0x7fffffff);
for (let i = 0; i < 40; i++) colours.push('#' + [next() >> 3, next() >> 5, next() >> 7].map((n) => (n & 255).toString(16).padStart(2, '0')).join(''));

const cases = [];
for (const accent of colours) for (const p of pairs) cases.push({ accent, ...p });
const php = `
$cases = json_decode( '${JSON.stringify(cases).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}', true );
$out = array();
foreach ( $cases as $c ) {
	$full = strlen( ltrim( $c['accent'], '#' ) ) === 3 ? '#' . implode( '', array_map( static function ( $ch ) { return $ch . $ch; }, str_split( ltrim( $c['accent'], '#' ) ) ) ) : $c['accent'];
	$out[] = array( basix_readable_on( $full ), basix_accent_text( $full, 'dark' === $c['mode'], $c['page'], $c['surface'] ) );
}
echo wp_json_encode( $out );`;
const raw = execFileSync('docker', ['compose', 'run', '--rm', '-T', 'wpcli', 'wp', 'eval', php], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] });
const expected = JSON.parse(raw.slice(raw.indexOf('[[')));
let wrong = 0;
cases.forEach((c, i) => {
  const accent = c.accent.length === 4 ? '#' + [...c.accent.slice(1)].map((ch) => ch + ch).join('') : c.accent;
  const got = [js.readableOn(accent), js.accentText(accent, c.mode === 'dark', c.page, c.surface)];
  if (got[0] !== expected[i][0] || got[1] !== expected[i][1]) {
    if (wrong++ < 5) console.log(`differs for ${c.accent} ${c.mode} on ${c.page}/${c.surface}: JS ${got} PHP ${expected[i]}`);
  }
});
if (wrong) {
  console.log(`FAILED: ${wrong} of ${cases.length} cases differ`);
  process.exit(1);
}
console.log(`ok   the preview and the site agree on ${cases.length} cases (${colours.length} colours, ${pairs.length} page and surface pairs)`);
console.log('\nAccent maths check passed.');
