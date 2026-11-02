# Submission checklist: the WordPress.org theme directory

A list to work through before and after you upload. What I could check here is ticked; the rest needs you, a WordPress.org account, or a real site.

## 1. Before you build the zip

- [ ] The working tree is clean and `main` is pushed (`git status`).
- [ ] `make check` passes. It runs PHPCS and about twenty checks, in English, and puts the dev site's language back.
- [ ] The version is the one you want in all places: `Version` in `theme/style.css`, `Stable tag` in `theme/readme.txt`, and a line in the readme's changelog. (Same for the plugin: `basix-events/basix-events.php` and `basix-events/readme.txt`.)
- [ ] `Tested up to` in both readmes is the newest WordPress you have tried (now 7.1).
- [ ] `make zip` rebuilds `dist/basix-<version>.zip` and `dist/basix-events-<version>.zip`. Always rebuild after the last commit: the zip in `dist/` is not updated by other commands.

## 2. What the directory checks, and where we stand

Checked here:

- [x] **Theme Check** (`docker compose run --rm -T wpcli wp theme-check run basix`): no required items. Left are three information lines and two recommendations (`custom-background` and `wp-block-styles`, left out on purpose: a background image would fight the looks' contrast guarantees, and the theme styles core blocks itself).
- [x] **Plugin territory:** the theme registers no post type, taxonomy, block or shortcode. Events live in `basix-events/`, and `scripts/check-without-plugin.mjs` proves the theme works without it.
- [x] **Coding standards:** PHPCS with the WordPress rules passes for the theme and the plugin (escaping, sanitising, nonces, prefixes, text domains).
- [x] **Screenshot:** `theme/screenshot.png` is 1200 x 900.
- [x] **Licence:** the theme is GPL v2 or later (`theme/license.txt`, headers in `style.css` and `readme.txt`). Bundled third-party parts are listed in `theme/readme.txt` with their licences: the Basix library (MIT), five typefaces (SIL OFL 1.1, with their licence texts in `theme/assets/fonts`) and Material icons (Apache 2.0).
- [x] **Tags** in `style.css` come from the allowed list (Theme Check accepts them).
- [x] **Translation-ready:** one text domain (`basix`, the theme's slug), a `.pot` file and a German translation.
- [x] **No forbidden code:** no `eval`, `base64_decode` or `document.write` in the theme's own code; no update URI; no top-level admin menu (the setup screen is under Appearance).
- [x] **Accessibility:** axe finds nothing serious or critical on the main pages in light and dark, on a desktop and a phone, in every look.

Things a reviewer may ask about (so you are ready):

- [ ] **Minified files.** `theme/assets/vendor/basix/css/style.min.css` (and its mirrored copy) is the Basix library's minified stylesheet. The directory wants the source or a link to it: the readme names the project (https://github.com/dodlhuat/basix), and the files are produced by `npm run sync` from the package `@dodlhuat/basix`. If asked, point to that, or add the unminified file.
- [ ] **The setup screen's content import.** It is started by the user, adds only content it can remove again, and is under Appearance. Reviewers sometimes object to themes that create posts and pages. If that happens, the easy answers are: keep the look picker and move the starter import into a plugin, or restrict the import to the Customizer's own "starter content" feature.
- [ ] **Apache 2.0 icons with a GPL v2 theme.** Apache 2.0 is compatible with GPL v3 and so with "GPL v2 or later"; this is normally accepted. If a reviewer disagrees, state the "or later" and the licences in the readme (already done).
- [ ] **The `accessibility-ready` tag.** Using it starts a separate, stricter accessibility review. If you want a faster first approval, remove the tag from `style.css` and `readme.txt` and add it again later in a version update.
- [ ] **The plugin.** A theme may not require a plugin. Basix does not: the readme only says that events need the optional plugin. Do not add a notice that nags for it.

## 3. Upload

1. Create an account at wordpress.org and turn on two-factor authentication (it is required to publish).
2. Go to https://wordpress.org/themes/upload/ and upload `dist/basix-<version>.zip`. The upload runs Theme Check automatically; fix anything it lists as required.
3. The theme enters the review queue. A reviewer opens a ticket in the themes Trac and writes about what to change, usually within days to a few weeks. Answer in that ticket and upload a new zip when asked. Each round is a new version number: keep it patch-level (1.0.1, 1.0.2).
4. When it is approved, you get access to a Subversion repository for the theme. New versions go there (`trunk`, then a tag per version); the directory page and updates follow.

## 4. The plugin (optional)

The plugin can stay a separate download from your own site, or be listed in the plugin directory with its own review.

- [ ] Run the **Plugin Check** plugin on `basix-events` (install it on the dev site; it is the directory's own tool).
- [ ] The plugin's readme (`basix-events/readme.txt`) is in the directory's format and its tags and description are right.
- [ ] Optional: a plugin icon and banner (`assets` folder in the plugin's repository, not in the zip).
- [ ] Upload at https://wordpress.org/plugins/developers/add/ (the same account).

## 5. By hand, on a real site

These cannot be checked here and are worth an hour before you submit:

- [ ] Install the built zips on a site that is not the dev site, with the default theme's content, and activate the theme, then the plugin.
- [ ] Walk through the setup screen: import a starter site, look at it, undo it.
- [ ] Try each plugin once: Contact Form 7, WPForms, Fluent Forms, Gravity Forms (forms), Polylang or WPML (the language switcher in the top bar and event translations), Elementor (the Canvas template and the theme builder's locations).
- [ ] Import `/events/feed.ics` into Apple Calendar, Google Calendar and Outlook; check a repeating event and an event in another time zone.
- [ ] Look at the site on a phone, in dark mode, and with the keyboard only.
- [ ] Ask a native German speaker to read the German texts.
- [ ] Run Lighthouse on the live demo (`node scripts/audit-lighthouse.mjs`); expect lower numbers than the local ones if the host has no compression or caching.
- [ ] Run a page through Google's Rich Results Test (an event page and a post) once a public demo exists.

## 6. After approval

- [ ] Keep a `CHANGELOG` section in `theme/readme.txt` for every version.
- [ ] Release with semantic versions: a fix is 1.0.1, a new option or pattern 1.1.0, something that changes how existing sites look 2.0.0.
- [ ] Answer support questions in the theme's directory forum; reviewers and users find you there.
- [ ] Watch for new WordPress releases: run `make check` against them and raise `Tested up to` when it passes.
