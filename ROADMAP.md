# Roadmap

Goal: a distributable, premium-feeling WordPress theme built from the Basix component library.

Decisions so far
- **Audience:** sell / distribute (GPL theme, Basix is MIT, Outfit font is OFL).
- **Editor:** hybrid. Keep PHP templates that use Basix markup, add `theme.json`, styled core blocks and block patterns.
- **WooCommerce:** out of scope for now.
- **Principle:** only Basix components and tokens; theme CSS stays layout glue.

## Milestones

### 1. Compliance baseline (done)
- Theme Check (WordPress.org and Envato rules) and PHPCS clean.
- Known findings: inline `<script>` in `header.php`, `date('Y')` instead of `wp_date`, unescaped `echo` in `basix_pagination`.
- Missing support: `automatic-feed-links`, `$content_width`, `comment-reply` script, `style.css` header tags, `license.txt` (credits for Basix, Outfit, sample images).
- Build script that syncs Basix and produces a clean theme zip (no `node_modules`, no dev files): `make zip`.
- Result: PHPCS clean (`make lint`), Theme Check has no required items or warnings. Remaining recommendations (block patterns/styles, editor style, custom logo, `responsive-embeds`, `align-wide`, `wp-block-styles`) belong to milestones 2 and 3.

### 1.5. Asset pipeline (done)
- **Trimmed icon sprite.** Basix `icons.svg` is 3.07 MB (6,446 icons) and is currently fetched on every page. Generate a sprite with only the used icons.
- **Component loader.** PHP prints `data-basix="<component>"` plus a JSON config; `main.js` lazy-loads that component only on pages that use it.
- Ship only the Basix JS files that are used.
- Every JS-built component gets a no-JS fallback (for example a data table behind a chart). This is a convention for the milestones below; the loader does not enforce it.
- Result: `npm run sync` scans the theme for the Basix modules and icons it uses and copies only those. Icon sprite 3 MB to 1.3 KB, JS 36 modules to 7, release zip 1.1 MB to 352 KB.
- Adding a component later: add an entry to `theme/assets/js/components.js` and print `basix_component_attrs()` in the template; sync picks up the module and its icons by itself. Icons chosen dynamically go in `scripts/basix-assets.json`.

### 2. Editor foundation (done)
- `theme.json` mirroring Basix tokens (palette, type), `add_editor_style`, wide and full alignment, `wp-block-styles`, responsive embeds.
- Style core blocks with Basix: buttons, quote, pullquote, table, code, image, gallery, columns, cover, separator, details, embeds.
- First block patterns: hero, features, FAQ (Accordion), steps (Stepper), timeline, CTA, stats (Chart).
- Result: `theme.json` (Basix palette, Outfit, 46rem content / 72rem wide), `assets/css/content.css` shared by the site and the editor, wide and full alignment, `editor-styles`, responsive embeds, block styles (Card, Bordered card, Lead text), seven patterns in `/patterns`, a seeded "Block showcase" page. `make check` runs PHPCS and opens the editor in headless Chrome to confirm every pattern and the showcase page are valid blocks.
- Differences from the plan: the FAQ uses the core Details block styled like the Basix accordion (the Basix accordion needs paired ids that core blocks cannot produce); Steps are numbered cards using `.stepper-indicator` (the connectors come from JS); Stats are big numbers, and a real Chart block waits for milestone 6; `wp-block-styles` is deliberately off because its default colours fight Basix.
- Known gap: in the editor, the code block has no tinted background and table headers are larger than on the site.

### 3. Settings and menus (done)
- Customizer: logo, accent colour (overrides `--accent-color`), default light/dark/system, sidebar left/right/none, footer text, social links.
- Menus: Dropdown submenus on desktop, nested drawer on mobile, footer and social menu locations.
- **Header layouts** (Customizer choice): inline + Dropdown, Flyout, Push-menu drill-down.
- Result: Customizer panel "Basix" (header layout, default colour mode, accent colours for light and dark, sidebar left/right/none, footer text, back-to-top) plus the core logo control. Menu locations: primary, footer, social. Primary menu supports three levels in all three layouts; a parent item that has children stops being a link and its own page becomes the first entry of its submenu (skipped for "#" placeholders). `make check` now also opens the Customizer in headless Chrome.
- Notes: the push layout moves the hamburger to the left so it stays on screen while the page is pushed aside. Social links are text, because Basix ships no brand icons. Icons whose names are built at runtime (`menu`, `menu_open`) are listed in `scripts/basix-assets.json`.
- Not done: live (postMessage) preview for the Customizer settings, which currently refresh the preview; a header image and background options.

### 4. Layout and templates (done)
- Footer widget columns, full-width and landing templates.
- Search overlay (Modal).
- Docs template: Sidebar nav + Tree of child pages.
- Result: three footer widget columns (only filled ones show), page templates "Full width", "Landing page" and "Documentation" in `/page-templates`, a header search button opening a Basix Modal (also on the "/" key; a plain link without JavaScript), a docs section and a landing demo in the seed content. `make check` now also drives the public site in headless Chrome (`scripts/check-frontend.mjs`).
- Deviation: the docs navigation does not use the Basix Tree. The Tree renders clickable divs in a file-explorer style (monospace labels, folder and file icons), so its entries are not links: no middle-click, nothing for search engines to follow. The template uses the Basix sidebar-nav shell (mobile drawer, backdrop, swipe) with a nested list of real links instead; the current branch is opened on the server.
- The grid patterns (features, steps, stats) are now wide-aligned so they use the full container width.

### 5. Post features (done)
- Reading time, table of contents, share (Popover / Bottom sheet, Toast for "link copied"), related posts, author box, reading-progress bar (Progress).
- Lightbox on content images (`Lightbox.bind`), Code viewer for code blocks, Table block styling plus opt-in sortable table.
- Featured-posts section (Carousel).
- Result: reading time (on cards and posts), a table of contents built from h2 and h3 as a Basix accordion with anchors on the headings, a reading-progress bar (Basix progress), share (a button with a Basix popover on wide screens and a bottom sheet on phones, "Copy link" with a toast; plain links without JavaScript), author box (shown when the author has a biography), related posts (shared tags first, then categories), a lightbox on content images with captions, the code viewer on code blocks (language from an additional CSS class such as `language-css`, a copy button on every block), a "Sortable" block style for tables, and a featured slider from sticky posts on the front page. Every feature has a switch in the Customizer under Basix > Posts. `make check` covers all of them in a real browser.
- Deviations: the sortable table uses a small own script (`sortable-table.js`) with Basix's sortable heading styles, not the Basix Table component, which adds its own search box and paging with fixed English labels. Sticky posts are removed from the latest-posts list so they are not shown twice.
- Known limits: some Basix components have fixed English text that the theme cannot translate (the code viewer's "Copy" and "copied!", the lightbox button labels). The code viewer's copy button needs HTTPS or localhost, because browsers only allow clipboard access there. The code viewer highlights JavaScript, HTML and CSS only; other languages get the label and the copy button.

### 6. Discovery and media (done)
- **Filters:** clickable category chips on the blog index, hierarchical filter (Group picker), date range (Date picker).
- **Calendar page:** posts by publish date, fetched from the REST API by visible range.
- **Gallery / portfolio:** masonry infinite-scroll template fed from the media REST endpoint, plus a core Gallery block upgrade.
- Skeleton / placeholder loading states for all AJAX features.
- Result: **Filters** above every post list (blog, archives, search): category chips that are plain links, a Filter button that opens a modal (a bottom sheet on phones) with the Basix group picker for categories and the Basix range date picker, active filters as removable chips; without JavaScript the same filter is a plain `<details>` form. Query variables `categories`, `date_from`, `date_to`, all validated. **Calendar** page template (Basix calendar: month, week, agenda) that loads the posts of the visible range from the REST API, in the site's language. **Photo gallery** page template (Basix masonry gallery with endless scrolling from the media REST endpoint, using the images uploaded to the page, or the whole library) and a **Masonry** style for the Gallery block. **Loading states:** the gallery's own Basix placeholder cards, and for the calendar an animated Basix progress bar plus skeleton cells.
- Basix workarounds: the masonry gallery has no notion of "no more images" and would ask again every frame, so the last fetch never answers (`masonry.js`); its own lightbox cannot be given an icon path, so it is switched off and the theme's lightbox is used.
- Known limits: the group picker has two levels, so deeper categories are flattened below their top-level category. The filter bar is part of the shared post list, so a static front page's "latest posts" strip does not have it. The gallery shows every image in the library when none are uploaded to its page. Calendar and gallery need the REST API; if a security plugin blocks it, they show an error message.

### 7. Premium polish
- Scroll reveals (respect `prefers-reduced-motion`), card variants (featured, list, grid), more hero variants, hover and focus polish, image treatments.

### 8. Release quality
- Translation file, `screenshot.png`, readme, starter content.
- Lighthouse, axe and keyboard audits; full pass in light, dark and mobile.

### Prototype (after 1.5)
- **Cmd-K quick switcher:** Modal + Virtual dropdown over a lightweight title/URL index. Decide after seeing it.

## Skipped
Context menu, Chat bubbles, Editor (WYSIWYG), File uploader, Color picker, and Time-span picker (only useful as a contact or booking form field).

## Risks
- Settings scope creep (keep the Customizer small).
- Core-block styling must be tested against real editor content.
- Basix class names may change between versions; pin the version in `package.json`.
