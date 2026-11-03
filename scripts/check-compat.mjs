// Plugin forms and page builders in a real browser: forms in the markup of Contact Form 7, WPForms, Fluent Forms and
// Gravity Forms look like the theme's own fields and buttons (light and dark), show their errors readably and take
// keyboard focus visibly; and the Canvas template shows a page with nothing but its content. Needs the local site
// running (make up).
//   node scripts/check-compat.mjs
// Env: CHROME (path to Chrome).
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import puppeteer from 'puppeteer-core';

const chrome = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const axeSource = readFileSync(createRequire(import.meta.url).resolve('axe-core/axe.min.js'), 'utf8');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const failures = [];
const check = (ok, what) => {
  console.log(`${ok ? 'ok  ' : 'FAIL'} ${what}`);
  if (!ok) failures.push(what);
};
const wp = (...args) => execFileSync('docker', ['compose', 'run', '--rm', '-T', 'wpcli', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();

// The markup those plugins print, reduced to what matters for the styling.
const forms = `<!-- wp:html -->
<div class="wpcf7" id="wpcf7-f1-p1-o1" lang="en-US" dir="ltr"><form class="wpcf7-form invalid" novalidate="novalidate">
<p><label>Your name<br><span class="wpcf7-form-control-wrap"><input class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required wpcf7-not-valid" type="text" name="your-name" size="40" aria-invalid="true"><span class="wpcf7-not-valid-tip" aria-hidden="true">The field is required.</span></span></label></p>
<p><label>Your message<br><span class="wpcf7-form-control-wrap"><textarea class="wpcf7-form-control wpcf7-textarea" name="msg" cols="40" rows="6"></textarea></span></label></p>
<p><input class="wpcf7-form-control wpcf7-submit" type="submit" value="Send"></p>
<div class="wpcf7-response-output">One or more fields have an error. Please check and try again.</div></form></div>
<!-- /wp:html -->
<!-- wp:html -->
<div class="wpforms-container wpforms-container-full"><form class="wpforms-form"><div class="wpforms-field-container">
<div class="wpforms-field wpforms-field-text"><label class="wpforms-field-label" for="wpf-name">Name</label><input type="text" id="wpf-name" class="wpforms-field-medium wpforms-error" aria-invalid="true"><label id="wpf-name-error" class="wpforms-error" for="wpf-name">This field is required.</label></div>
<div class="wpforms-field wpforms-field-select"><label class="wpforms-field-label" for="wpf-topic">Topic</label><select id="wpf-topic" class="wpforms-field-medium"><option>One</option><option>Two</option></select><div class="wpforms-field-description">Pick the closest one.</div></div>
</div><div class="wpforms-submit-container"><button type="submit" class="wpforms-submit">Submit</button></div></form></div>
<!-- /wp:html -->
<!-- wp:html -->
<div class="fluentform ff-default"><form class="frm-fluent-form ff-el-form-top"><fieldset><div class="ff-el-group ff-el-is-error"><div class="ff-el-input--label ff-el-is-required"><label for="ff-email">Email</label></div><div class="ff-el-input--content"><input type="email" id="ff-email" class="ff-el-form-control" aria-invalid="true"><div class="error text-danger">Please enter a valid email.</div></div></div>
<div class="ff-el-group ff_submit_btn_wrapper"><button type="submit" class="ff-btn ff-btn-submit ff-btn-md ff_btn_style">Send</button></div></fieldset><div class="ff-message-success">Thank you, your message was sent.</div></form></div>
<!-- /wp:html -->
<!-- wp:html -->
<div class="gform_wrapper gravity-theme"><form><div class="gform_fields"><div class="gfield gfield_error"><label class="gfield_label" for="input_1_1">Phone</label><div class="ginput_container"><input type="tel" id="input_1_1" aria-invalid="true"></div><div class="gfield_description validation_message gfield_validation_message">This field is required.</div></div></div>
<div class="gform_footer"><input type="submit" class="gform_button button" value="Submit"></div></form></div>
<!-- /wp:html -->`;

const ids = [];
const create = (title, content, template = '') => {
  const args = ['--user=admin', 'post', 'create', '--post_type=page', '--post_status=publish', `--post_title=${title}`, `--post_content=${content}`, '--porcelain'];
  const id = wp(...args);
  ids.push(id);
  if (template) wp('post', 'meta', 'update', id, '_wp_page_template', template);
  return id;
};
const link = (id) => wp('post', 'list', '--post_type=page', `--post__in=${id}`, '--field=url');

const browser = await puppeteer.launch({ executablePath: chrome, headless: true });
const open = async (address, theme) => {
  const page = await (await browser.createBrowserContext()).newPage();
  await page.setViewport({ width: 1280, height: 900 });
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await page.evaluateOnNewDocument((t) => localStorage.setItem('theme', t), theme);
  await page.goto(address, { waitUntil: 'networkidle0' });
  await sleep(500);
  return page;
};
try {
  const formPage = link(create('Form fixtures', forms));
  for (const theme of ['light', 'dark']) {
    const page = await open(formPage, theme);
    const facts = await page.evaluate(() => {
      // Resolve a CSS value (a variable, a colour mix) to what the browser paints.
      const probe = document.createElement('i');
      document.body.append(probe);
      // No transition, or the value read is one on the way from the last probe.
      const resolve = (value, prop = 'color') => { probe.style.cssText = `transition:none;${prop}:${value}`; return getComputedStyle(probe)[prop]; };
      const accent = resolve('var(--accent-color)');
      const onAccent = resolve('var(--on-accent)');
      const errorText = resolve('var(--error-text)');
      const radius = resolve('var(--radius-md)', 'border-top-left-radius');
      const bodyFont = getComputedStyle(document.body).fontFamily;
      const fields = [...document.querySelectorAll('.wpcf7 input[type=text], .wpcf7 textarea, .wpforms-container input, .wpforms-container select, .fluentform input, .gform_wrapper input[type=tel]')];
      const buttons = [...document.querySelectorAll('.wpcf7-submit, .wpforms-submit, .ff-btn-submit, .gform_button')];
      const tips = [...document.querySelectorAll('.wpcf7-not-valid-tip, label.wpforms-error, .ff-el-is-error .error, .validation_message')];
      const notes = [...document.querySelectorAll('.wpcf7-response-output, .ff-message-success')];
      probe.remove();
      const width = (e) => e.getBoundingClientRect().width / e.parentElement.getBoundingClientRect().width;
      return {
        fields: fields.length,
        fieldsRight: fields.every((f) => { const c = getComputedStyle(f); return c.borderTopWidth === '1px' && c.borderTopLeftRadius === radius && c.fontFamily === bodyFont && c.paddingTop !== '0px'; }),
        oddFields: fields.filter((f) => { const c = getComputedStyle(f); return !(c.borderTopWidth === '1px' && c.borderTopLeftRadius === radius && c.fontFamily === bodyFont && c.paddingTop !== '0px'); }).map((f) => `${f.tagName}.${f.className}: ${getComputedStyle(f).borderTopWidth} ${getComputedStyle(f).borderTopLeftRadius} ${getComputedStyle(f).fontFamily.slice(0, 20)}`),
        oddButtons: buttons.filter((b) => { const c = getComputedStyle(b); return !(c.backgroundColor === accent && c.color === onAccent && c.borderTopLeftRadius === radius); }).map((b) => `${b.className}: ${getComputedStyle(b).backgroundColor} ${getComputedStyle(b).color} ${getComputedStyle(b).borderTopLeftRadius}`),
        probes: { radius, accent, onAccent, bodyFont: bodyFont.slice(0, 30) },
        oddTips: tips.filter((t) => getComputedStyle(t).color !== errorText).map((t) => `${t.className}: ${getComputedStyle(t).color}`),
        wide: fields.every((f) => f.getBoundingClientRect().width > 200),
        buttons: buttons.length,
        buttonsRight: buttons.every((b) => { const c = getComputedStyle(b); return c.backgroundColor === accent && c.color === onAccent && c.borderTopLeftRadius === radius; }),
        tips: tips.length,
        tipsRight: tips.every((t) => getComputedStyle(t).color === errorText),
        notes: notes.length,
        notesRight: notes.every((n) => getComputedStyle(n).borderInlineStartWidth === '4px'),
        widthRatio: width(fields[0]),
      };
    });
    if (process.env.DEBUG) console.log(JSON.stringify(facts));
    check(facts.fields === 6 && facts.fieldsRight && facts.wide, `${theme}: the fields of all four plugins have the theme's border, corners, font and padding`);
    check(facts.buttons === 4 && facts.buttonsRight, `${theme}: their submit buttons are accent-coloured with readable text`);
    check(facts.tips === 4 && facts.tipsRight && facts.notes === 2 && facts.notesRight, `${theme}: errors are in the error colour and answers sit in a bar`);
    await page.evaluate(axeSource);
    const result = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
    const bad = result.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
    check(bad.length === 0, `${theme}: axe finds nothing serious in the forms${bad.length ? ` (${bad.map((v) => `${v.id}: ${v.nodes[0].target.join(' ')}`)})` : ''}`);
    if (theme === 'light') {
      let focused = false;
      for (let i = 0; i < 60 && !focused; i++) {
        await page.keyboard.press('Tab');
        focused = await page.evaluate(() => document.activeElement?.matches('.wpcf7 input[type=text]') && parseFloat(getComputedStyle(document.activeElement).outlineWidth) >= 2 && getComputedStyle(document.activeElement).outlineStyle !== 'none');
      }
      check(focused, 'a plugin field shows a visible focus ring when reached with the keyboard');
    }
    await page.close();
  }

  // The canvas for page builders.
  const canvas = link(create('Canvas fixture', '<!-- wp:paragraph --><p>Only this text.</p><!-- /wp:paragraph -->', 'page-templates/canvas.php'));
  const page = await open(canvas, 'light');
  const state = await page.evaluate(() => ({
    header: !!document.querySelector('.site-header, .top-bar'),
    footer: !!document.querySelector('.site-footer'),
    breadcrumb: !!document.querySelector('.breadcrumb'),
    main: document.querySelector('main#content.canvas')?.textContent.includes('Only this text.'),
    heading: document.querySelector('main h1.screen-reader-text')?.textContent,
    scripts: document.querySelectorAll('script').length > 0,
    margin: getComputedStyle(document.querySelector('main')).paddingTop,
  }));
  check(!state.header && !state.footer && !state.breadcrumb && state.main && state.heading === 'Canvas fixture', 'the Canvas template shows only the content (with its title kept for screen readers)');
  check(state.scripts, 'and still prints the scripts and styles that plugins and builders add');
  await page.evaluate(axeSource);
  const axeCanvas = await page.evaluate(() => axe.run(document, { resultTypes: ['violations'] }));
  check(axeCanvas.violations.filter((v) => ['serious', 'critical'].includes(v.impact)).length === 0, 'the Canvas page has no serious axe problems');
  await page.close();
} finally {
  for (const id of ids) {
    try { wp('post', 'delete', id, '--force'); } catch { /* fine */ }
  }
  await browser.close();
}
if (failures.length) {
  console.log(`\nFAILED: ${failures.length}`);
  process.exit(1);
}
console.log('\nCompatibility check passed.');
