// ESLint finds mistakes (unused variables, undefined names, unreachable code); it does not format. The files of the
// Basix library under assets/vendor are generated and left alone.
import js from '@eslint/js';
import globals from 'globals';

export default [
  { ignores: ['node_modules/**', 'vendor/**', 'dist/**', 'theme/assets/vendor/**'] },
  js.configs.recommended,
  {
    // The theme's own scripts: ES modules for the browser.
    files: ['theme/assets/js/**/*.js'],
    languageOptions: { sourceType: 'module', globals: { ...globals.browser } },
    rules: { 'no-unused-vars': ['error', { args: 'after-used', caughtErrors: 'none' }] },
  },
  {
    // Plain scripts that also run in the Customizer preview (and, for the colour maths, in Node for the checks).
    files: ['theme/assets/js/customizer-preview.js', 'theme/assets/js/accent-math.js'],
    languageOptions: { sourceType: 'script', globals: { ...globals.browser, wp: 'readonly', module: 'readonly' } },
  },
  {
    // The scripts that run the checks: Node, and code sent into the browser (document, window, axe, wp).
    files: ['scripts/**/*.mjs', 'eslint.config.js'],
    languageOptions: { sourceType: 'module', globals: { ...globals.node, ...globals.browser, axe: 'readonly', wp: 'readonly' } },
    rules: { 'no-unused-vars': ['error', { args: 'none', caughtErrors: 'none' }] },
  },
];
