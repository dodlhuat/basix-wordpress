# Submission checklist: the WordPress.org theme directory

A list to work through before and after you upload. What I could check here is ticked; the rest needs you, a WordPress.org account, or a real site.

**Where we are (2026-11-23):** version 1.0.0 was reviewed and closed as not approved. Version 1.0.1 fixes every point of that review (see section 0) and is ready to upload as a new submission.

## 0. Review round 1 (1.0.0, closed as not approved, 2026-11)

The reviewer's points, what changed in 1.0.1, and what checks it now:

| Reviewer's point | What changed | Checked by |
| --- | --- | --- |
| Custom theme options panels are not allowed; use the Customizer | The setup screen (Appearance > Basix setup), its welcome notice, the starter-site importer and the WP-CLI command moved to the plugin (`basix-paperlight-events/inc/setup/`, `starters/`). The theme's options are in the Customizer only. | `check-without-plugin.mjs` (no setup screen, no `wp basix starter`), `check-starters.mjs` (with the plugin) |
| Focus must stay in the search modal while it is open | Shared focus trap (`theme/assets/js/focus-trap.js`); the close icon can be reached with the keyboard; focus goes back to the search button | `check-frontend.mjs` |
| Mobile menu: Tab past the last item goes to close, Shift+Tab on the first item goes to close, Shift+Tab on close goes to the last item | Focus trap on the flyout drawer, focus starts on its close button, Escape closes it, focus returns to the menu button. Same for the push-layout menu (its toggle is now reachable with the keyboard) | `check-frontend.mjs` |
| The admin bar overlaps the header on mobile | Basix's `body { position: relative }` pulled the phone admin bar (absolute) 46px down onto the header; `body.admin-bar` is static now. The sticky header, sidebars and reading progress stop below the fixed bar from 601px up, and the drawers cover it on phones | Measured logged in at 390, 700 and 1280px, scrolled and not |
| Long titles are cut off instead of wrapping | Headings, card titles, post navigation, widgets and breadcrumbs wrap, even one long word | `check-frontend.mjs` |
| Schema is plugin territory | `inc/schema.php` moved to the plugin | `check-without-plugin.mjs` (no JSON-LD without the plugin) |
| SEO meta description is plugin territory | `inc/seo.php` moved to the plugin | `check-without-plugin.mjs` (no meta description or Open Graph tags) |
| Untranslatable strings in `stats.php` lines 13, 23, 33 | The numbers are wrapped in `esc_html_x()`; all patterns and templates were scanned, so the Steps numbers and the 404 heading too | Scan of `patterns/`, `template-parts/` and the templates |
| "+ possibly more issues" | Checked against the handbook's requirements: share buttons moved to the plugin (named as plugin territory); the readable original of the minified Basix stylesheet is included; focus is also trapped in the filter dialog, the docs drawer, the share sheet and the lightbox | `check-frontend.mjs`, `check-without-plugin.mjs` |

## 0b. Resubmitting

- [x] Version 1.0.1 in `theme/style.css` and `theme/readme.txt`, with a changelog entry.
- [x] `make check` passes (2026-11-23, all checks; the browser checks now exit by themselves, so the run can no longer hang).
- [x] Theme Check: no required items.
- [x] `main` pushed; `dist/basix-paperlight-1.0.1.zip` and `dist/basix-paperlight-events-1.0.0.zip` built from it. The theme zip has no schema, SEO, setup, starter or share files.
- [ ] Run the Theme Sniffer (https://github.com/WPTRT/theme-sniffer) on the 1.0.1 zip, as the reviewer suggested.
- [ ] By hand, the way the reviewer tests: with the keyboard only, open the search and press Tab and Shift+Tab many times; open the mobile menu at phone width and go round it in both directions; compare with Twenty Twenty. Logged in on a phone, look at the header under the admin bar.
- [ ] Upload `dist/basix-paperlight-1.0.1.zip` at https://wordpress.org/themes/upload/. The closed ticket lost its place, so this opens a new ticket at the end of the queue.
- [ ] In the new ticket, list each point of the first review and how it was fixed (the table in section 0), and say that schema, SEO, share links and the starter sites are now in the optional companion plugin and the theme works without it.

## 1. Before you build the zip

- [ ] The working tree is clean and `main` is pushed (`git status`).
- [ ] `make check` passes. It runs PHPCS and about twenty checks, in English, and puts the dev site's language back.
- [ ] The version is the one you want in all places: `Version` in `theme/style.css`, `Stable tag` in `theme/readme.txt`, and a line in the readme's changelog. (Same for the plugin: `basix-paperlight-events/basix-paperlight-events.php` and `basix-paperlight-events/readme.txt`.)
- [ ] `Tested up to` in both readmes is the newest WordPress you have tried (now 7.1).
- [ ] `make zip` rebuilds `dist/basix-paperlight-<version>.zip` and `dist/basix-paperlight-events-<version>.zip`. Always rebuild after the last commit: the zip in `dist/` is not updated by other commands.

## 2. What the directory checks, and where we stand

Checked here:

- [x] **Theme Check** (`docker compose run --rm -T wpcli wp theme-check run basix-paperlight`): no required items. Left are two information lines and two recommendations (`custom-background` and `wp-block-styles`, left out on purpose: a background image would fight the looks' contrast guarantees, and the theme styles core blocks itself).
- [x] **Plugin territory:** the theme registers no post type, taxonomy, block or shortcode, writes no structured data, meta description or share buttons, and has no admin screen of its own. Events, schema, SEO tags, share links and the setup screen with the starter sites live in `basix-paperlight-events/` (moved there after the first review, 2026-11), and `scripts/check-without-plugin.mjs` proves the theme works without it and writes none of them.
- [x] **Coding standards:** PHPCS with the WordPress rules passes for the theme and the plugin (escaping, sanitising, nonces, prefixes, text domains).
- [x] **Screenshot:** `theme/screenshot.png` is 1200 x 900.
- [x] **Licence:** the theme is GPL v2 or later (`theme/license.txt`, headers in `style.css` and `readme.txt`). Bundled third-party parts are listed in `theme/readme.txt` with their licences: the Basix library (MIT), five typefaces (SIL OFL 1.1, with their licence texts in `theme/assets/fonts`) and Material icons (Apache 2.0).
- [x] **Tags** in `style.css` come from the allowed list (Theme Check accepts them).
- [x] **Translation-ready:** one text domain (`basix-paperlight`, the theme's slug); every pattern and template string goes through gettext, a `.pot` file and a German translation.
- [x] **No forbidden code:** no `eval`, `base64_decode` or `document.write` in the theme's own code; no update URI; no admin menu or options page of its own (theme options are in the Customizer only).
- [x] **Accessibility:** axe finds nothing serious or critical on the main pages in light and dark, on a desktop and a phone, in every look.
- [x] **Keyboard:** the search and filter dialogs, the mobile menus, the docs drawer, the share sheet and the lightbox keep focus inside while open (Tab on the last item goes to the close button, Shift+Tab on the close button goes to the last item), Escape closes them, and focus goes back to the button that opened them.

Things a reviewer may ask about (so you are ready):

- [x] **Minified files.** `theme/assets/vendor/basix/css/style.min.css` (and its mirrored copy) ships with its readable original `style.css` (and `style-rtl.css`), written by `npm run sync` and `npm run rtl`; the readme points to the SCSS sources on GitHub.
- [ ] **Apache 2.0 icons with a GPL v2 theme.** Apache 2.0 is compatible with GPL v3 and so with "GPL v2 or later"; this is normally accepted. If a reviewer disagrees, state the "or later" and the licences in the readme (already done).
- [x] **The `accessibility-ready` tag** is left out for the first submission, so it gets the normal review. Add it to `style.css` and `readme.txt` in a later version update if you want the separate accessibility review.
- [ ] **The plugin.** A theme may not require a plugin. Basix does not: the readme only says that events, structured data, share links and the starter sites come with the optional plugin. Do not add a notice that nags for it.
- [ ] **Recommending a plugin that is not on WordPress.org.** The handbook says themes "may only recommend plugins that are hosted on WordPress.org". `theme/readme.txt` names Basix Paperlight Events, which is not in the plugin directory yet. Either submit the plugin (section 4) before or alongside the theme, or be ready to take the plugin out of the theme readme if the reviewer asks.
- [ ] **Social links in the top bar.** "Follow" buttons are named as plugin territory next to share buttons. The theme's social links are a nav menu location (as in the default themes), not buttons it builds itself, which is normally accepted. If a reviewer objects, they can move to the plugin like the share links.
- [x] **The "Page layout" meta box** (header, sidebar, title and footer per page) is design-related; only non-design meta boxes are plugin territory.

## 2b. The theme handbook's testing page, item by item

From https://developer.wordpress.org/themes/releasing-your-theme/testing/. Status as of 2026-11-04.

| Item on the page | Status |
| --- | --- |
| Set up a development environment | Done: Docker, WordPress 7.1.4, PHP 8.3 |
| Import the Theme Unit Test data | Done: 123 items imported; 329 addresses visited (posts, pages, categories, tags, authors, pagination, search, date archives). All load, none prints a PHP message, none sticks out sideways at 390 or 1280 px, no script error, no broken picture. Re-run any time with `scripts/audit-unit-data.mjs` (see the developer guide). One axe finding is left, from the test content itself: an author's red paragraph with light grey text at 4.43:1 (the theme does not override colours an author chose). |
| Very long site title and tagline | Done. The header shows at most two lines of the name; the front page shows it whole. Long post and page titles wrap inside the content column, including one long word (fixed in 1.0.1 after the review). |
| Five posts per page (pagination) | Done |
| Threaded comments three levels deep, and comments in pages of five | Done (the test data goes ten levels deep) |
| No large image size (`$content_width`) | Done; the theme sets `$content_width` |
| Change the permalink structure several times | Done: plain, day and name, `/index.php/%postname%/`, post id, category and name; home, posts, a page, category, author, search, the events list and feed, pagination and a date archive all load |
| WordPress Beta Tester plugin (next version) | Not done: tested on the latest release (7.1.4) only. Try the nightly before submitting if you want to. |
| Theme Check plugin | Done: no required items (see section 2) |
| Developer plugin | Not used |
| Debug Bar plugin | Not used. Instead the dev site runs with `WP_DEBUG` on and every response is scanned for PHP messages. |
| Log Deprecated Notices plugin | Done: installed, all 330 addresses visited, nothing logged |
| Browser testing | Partly: Chrome (Chromium) in light, dark, right-to-left, on a phone and a desktop, with axe. **Not done: Firefox, Safari, Edge.** Firefox 157 is installed on the dev Mac but could not be launched from the test tooling; try the main pages by hand in Firefox and Safari. |
| HTML5 validation (validator.w3.org/nu) | Done on twelve pages (home, a post, a page, a category, search, 404, the events list and an event, the calendar, the Pattern library, the landing demo, the block showcase): no errors and no warnings after two small fixes. The only errors found are in the test data's own content (obsolete `acronym`, `big`, `strike` and `tt` tags it contains on purpose). |
| CSS validation (jigsaw.w3.org) | `forms.css` is valid. The others report only modern CSS the validator does not know yet: container queries, view transitions, `line-clamp`, the `mask` shorthand, and a vendor-prefixed `-webkit-box`. These are deliberate and work in current browsers. |

## 3. Upload

1. Create an account at wordpress.org and turn on two-factor authentication (it is required to publish).
2. Go to https://wordpress.org/themes/upload/ and upload `dist/basix-paperlight-<version>.zip`. The upload runs Theme Check automatically; fix anything it lists as required.
3. The theme enters the review queue. A reviewer opens a ticket in the themes Trac and writes about what to change, usually within days to a few weeks. Answer in that ticket and upload a new zip when asked. Each round is a new version number: keep it patch-level (1.0.1, 1.0.2). If the ticket is closed as not approved (as happened with 1.0.0), the next upload starts a new ticket at the end of the queue, so fix everything before uploading again.
4. When it is approved, you get access to a Subversion repository for the theme. New versions go there (`trunk`, then a tag per version); the directory page and updates follow.

## 4. The plugin (optional)

The plugin can stay a separate download from your own site, or be listed in the plugin directory with its own review. Since 1.0.1 it also holds the structured data, the meta description, the share links and the setup screen with the starter sites, and the theme readme recommends it, so listing it in the directory is worth doing (see the plugin item in section 2).

- [ ] Run the **Plugin Check** plugin on `basix-paperlight-events` (install it on the dev site; it is the directory's own tool).
- [ ] The plugin's readme (`basix-paperlight-events/readme.txt`) is in the directory's format and its tags and description are right.
- [ ] Optional: a plugin icon and banner (`assets` folder in the plugin's repository, not in the zip).
- [ ] Upload at https://wordpress.org/plugins/developers/add/ (the same account).

## 5. By hand, on a real site

These cannot be checked here and are worth an hour before you submit:

- [ ] Open the main pages in Firefox and Safari (and Edge if you can): the header and menu, the slider, the calendar, a post with a picture, dark mode, and a phone width.
- [ ] Install the built zips on a site that is not the dev site, with the default theme's content, and activate the theme, then the plugin.
- [ ] With the plugin active, walk through the setup screen: import a starter site, look at it, undo it.
- [ ] Try each plugin once: Contact Form 7, WPForms, Fluent Forms, Gravity Forms (forms), Polylang or WPML (the language switcher in the top bar and event translations), Elementor (the Canvas template and the theme builder's locations).
- [ ] Import `/events/feed.ics` into Apple Calendar, Google Calendar and Outlook; check a repeating event and an event in another time zone.
- [ ] Look at the site on a phone, in dark mode, and with the keyboard only: the search, the filter dialog, the mobile menu, the docs drawer, the share sheet and the lightbox must keep focus inside until closed.
- [ ] Logged in, on a phone: the admin bar sits above the header, and the menu button and the drawer's close button can be tapped.
- [ ] Ask a native German speaker to read the German texts.
- [ ] Run Lighthouse on the live demo (`node scripts/audit-lighthouse.mjs`); expect lower numbers than the local ones if the host has no compression or caching.
- [ ] Run a page through Google's Rich Results Test (an event page and a post) once a public demo exists.

## 6. After approval

- [ ] Keep a `CHANGELOG` section in `theme/readme.txt` for every version.
- [ ] Release with semantic versions: a fix is 1.0.1, a new option or pattern 1.1.0, something that changes how existing sites look 2.0.0.
- [ ] Answer support questions in the theme's directory forum; reviewers and users find you there.
- [ ] Watch for new WordPress releases: run `make check` against them and raise `Tested up to` when it passes.
