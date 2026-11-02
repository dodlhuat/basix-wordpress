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

### 7. Premium polish (done)
- Scroll reveals (respect `prefers-reduced-motion`), card variants (featured, list, grid), more hero variants, hover and focus polish, image treatments.
- Result: a separate `assets/css/polish.css` for the finishing touches (native controls and scrollbars follow the theme through `color-scheme`, a selection colour, balanced headings and steady numbers, a header shadow once the page scrolls, the card picture zooming and the title underlining on hover, a short cross-fade between pages with the header staying put). **Scroll reveals** for things below the fold, with a stagger per row, nothing hidden at load and nothing at all for reduced motion; late-loading pictures fade in. **Post list layouts** in the Customizer: grid, list (picture beside the text) or magazine (a large first post); they respond to the width of the list through container queries, so a sidebar does not break them. **Hero variants:** patterns "Hero with picture", "Hero on a picture" and "Hero, minimal" (ten patterns now), and a front page introduction choice (card, minimal, none). **Image treatments:** Framed, Soft shadow and Circle block styles for the Image block. Keyboard focus was already good (Basix draws an accent ring on everything); a check now keeps it that way.
- Browser support: page transitions need a browser with cross-document view transitions (others simply load the page); the list and magazine layouts need container queries and `:has()`, otherwise the cards stay in the grid. Reveals are not applied to content that JavaScript adds later, such as calendar and gallery items.

### 8. Release quality (done)
- Translation file, `screenshot.png`, readme, starter content.
- Lighthouse, axe and keyboard audits; full pass in light, dark and mobile.
- Result: **axe** finds no serious or critical problems on 12 pages in light, dark, desktop and phone (`make check` runs it). **Lighthouse** (mobile and desktop): performance 95-100, accessibility 96-100, best practices 100, SEO 100, layout shift about 0 (`node scripts/audit-lighthouse.mjs`, not part of `make check`). Fixes along the way: accent text colour that meets contrast in both modes, landmark and link names, focusable code and table regions, carousel and calendar labels, a meta description fallback, steady layout and 24px touch targets. **Translation:** `languages/basix.pot` plus a German translation (`de_DE`); the front end and the scripts' strings were checked in German. **Starter content** (pages, menus, widgets, front page settings) shows on fresh sites in the Customizer. Version is 1.0.0, tags are updated, there is a root `README.md`, and a new `screenshot.png`. The built zip installs and activates on a fresh WordPress 7.1 and passes Theme Check without required items or warnings.
- Limits: the German translation should be read by a native speaker. Lighthouse ran against a local Apache without compression or caching, so real scores depend on hosting. Social (Open Graph) tags are left to SEO plugins. On a narrow phone the carousel arrows can overlap the category badge of a featured slide.

### 9. Events (done)
- An **Event** post type (Events in the admin menu) with start and end date, optional times and a location; no times means an all-day event. The calendar block, the `[basix_calendar]` shortcode and the Calendar page template show events by default, with month, week and agenda views and multi-day events. `source="posts"` (or the block's Source option) shows blog posts by publish date instead. Events have their own page (`/event/<name>/`), and `/events/` lists what is still to come. Events are read from a public endpoint, `/wp-json/basix/v1/events?from=&to=`.
- Limits: the post type lives in the theme, so events are hidden (not deleted) if the site switches theme; no recurring events, no categories, no time zone per event (times are the site's local time; categories, time zones and repeats came later, see section 10); clicking a day opens that day's events (added in section 10); no ICS export (added in section 10).

### 10. Event extras (done, but the real imports below are still to do by hand)
Order matters: each step builds on the one before. Every step ends with `make check`, new checks for what it adds, German strings, and a small commit per piece.

**0. Decide where events live (decided: they stay in the theme).** Theme Check lists the post type, block and shortcode as plugin territory. If the theme should go on WordPress.org, move them first into a companion plugin (`basix-events`) so the steps below are not moved twice. For a sold theme they can stay.

**1. Event categories (small, done).**
- A hierarchical `basix-event-category` taxonomy (`/events/category/<name>/`), shown in the editor sidebar automatically.
- The events endpoint takes `categories=` and returns each event's categories; the calendar badge becomes the first category (the location stays on the event page; the calendar component has no tooltip text to put it in).
- Block option and shortcode attribute `category` (slugs, comma separated); chips above the calendar filter what is already loaded, without another request. Event cards and the single page show the category badges.

**2. Time zones per event (medium, the base for 3 to 5, done; `scripts/php/check-events.php` tests it and runs in `make check`).**
- New field `basix_event_timezone` (a select from `wp_timezone_choice()`; empty means the site's zone). Existing events keep working as they are.
- Store the wall-clock times as now, plus derived UTC start and end (`basix_event_start_utc`, `basix_event_end_utc`), written on save. Queries and sorting use the UTC fields; a version option backfills old events.
- All-day events stay "floating" (no zone, no conversion).
- Display rule: the calendar grid shows the site's time zone. An event in another zone is placed by its real moment, and its own local time with the zone name is shown on its page, in its tooltip and in the list ("6:30 pm – 8:00 pm CET").
- Edge cases to test: daylight saving gaps and repeats, events that cross midnight after conversion, zones with half-hour offsets.

**3. Recurring events (large, done; the rule tests are in `scripts/php/check-events.php`).**
- A rule on the event, stored as one JSON meta field: frequency (daily, weekly, monthly by date, monthly by weekday such as "second Tuesday", yearly), every N, weekdays for weekly, an end (never, until a date, after N times) and a list of skipped dates. This is a subset of the standard iCalendar rule, so it can be exported unchanged.
- Occurrences are computed when read, not saved as posts: `basix_event_occurrences($event, $from, $to)` expands the rule in the event's own zone, so 6:30 pm stays 6:30 pm across daylight saving. It is capped (about 500 per event and range) so a bad rule cannot loop forever.
- The events endpoint returns occurrences with unique ids (`<id>-<date>`) and links to the event with `?date=`; the event page shows the rule in words ("Every week on Tuesday, until 20 Dec") and the next dates.
- The archive lists each recurring event once, by its next date; a derived `basix_event_next` field is refreshed on save and by a daily cron job.
- Meta box: a "Repeat" section with a live summary sentence. Editing one single occurrence is out of scope (skip a date instead).
- Tests: a PHP check script with fixed cases (month end, 31st, leap day, nth weekday, until and count, skipped dates, multi-day events that repeat, daylight saving).

**4. Clicking a day (small, after 3, done; `scripts/check-frontend.mjs` clicks a day and presses Enter).** The first option below was used; the day page is not indexed (`noindex`) and links to the day before and after.
- A day opens `/events/?event_day=2026-11-05`: the archive template listing that day's occurrences (recurring ones included), with a "No events on this day" state, and working without JavaScript.
- The calendar's day click and Enter key go there. Alternative to consider: add a `goToDate()` method to the Basix calendar itself (the library is yours) and open the week view instead.

**5. ICS export (medium, after 2 and 3, done; `scripts/php/check-events.php` tests the text, `scripts/check-ics.mjs` compares the feed with the site's own dates using the independent ical.js parser, and both run in `make check`).**
- An "Add to calendar" button on each event page (`?ics=1`), and a subscribe feed `/events/feed.ics` (all upcoming events; `?category=` for one category) with a `webcal://` link in the events archive.
- Output follows the standard: CRLF lines folded at 75 bytes, escaped text, stable `UID`, `SEQUENCE` from the modified time, all-day events with exclusive end dates, `TZID` with generated `VTIMEZONE` blocks (so Outlook accepts it), `RRULE` and `EXDATE` for repeats, `CATEGORIES`, `URL`, `LOCATION`.
- Password-protected and private events are left out. The feed is cached and cleared when an event is saved, with ETag support.
- Test: parse the output with an independent iCalendar parser in a check script, and compare occurrences with the PHP expansion.
- Deviations: a weekly rule that leaves out the weekday of its start date becomes two events in the file (ICS only counts dates the rule hits). The zone blocks list each daylight saving change up to 2037, where PHP's rules end. Not done: the Apple, Google and Outlook imports (see Risks).

**Risks.** Recurrence and daylight saving are where bugs hide, so they get fixed-case tests before any UI. Meta queries for recurring events can get slow with thousands of events (fine for a typical site; the derived fields keep it bounded). Calendar apps differ in how strictly they read ICS, so a few real imports (Apple, Google, Outlook) are needed by hand at the end.

### 11. Premium breadth (planned)
Where section 7 added polish, this adds what premium buyers expect to find: looks to choose from, more sections to build with, a guided start, flexible headers, structured data and a way to sell it. Order matters: each step builds on the one before. Every step ends with `make check`, new checks for what it adds, German strings, and a small commit per piece. Sources for the list: premium theme roundups (demos, builders, patterns, schema, support) and a look at what our theme lacks.

**Already strong, so not on this list:** accessibility (axe, keyboard), performance (Lighthouse 95-100), dark mode, filters, gallery, and events with repeats, time zones and ICS.

**1. Style variations and design controls (small to medium, done; `scripts/php/check-looks.php` and `scripts/check-looks.mjs` test it, both in `make check`).**
- `theme.json` style variations in `/styles` (four or five looks: colours and type together, for example a calm default, a bold dark one, a warm editorial one). They show in the Site Editor's Styles panel and in the Customizer's Basix panel as a "Look" choice, so a classic-theme user can pick one too.
- A font choice (a few self-hosted families, OFL licensed, no calls to a font CDN) and a type scale (small, medium, large) in the Customizer.
- Check: every look passes the axe contrast check in light and dark; a script switches each look and loads the main pages.
- Result: five looks in `/styles` (Basix, Editorial, Bold, Calm, Paper). Each is a valid theme.json variation file with the Basix design data under `settings.custom.basix`: colours for light and dark (background, surface, text, secondary text, divider, accent), a corner scale (none, sharp, default, round) and a heading and a body font. `inc/looks.php` reads them and writes the CSS (colour variables per mode, scoped like the accent colours were; radius tokens; fonts; text size), also in the block editor, and preloads the fonts in use. Customizer: a **Look** section (first in the panel) and a **Typography** section (heading font, text font, text size small, medium or large); the accent colours follow the look until one is chosen. Fonts: Inter, Space Grotesk, Source Serif 4 and Fraunces as self-hosted variable Latin files in `assets/fonts` with their OFL texts, plus Outfit and a system font that needs no download; only the fonts in use are declared. Primary buttons now use the readable "on accent" colour (Basix used the page colour, which failed on some accents). The contrast check computes every text, secondary text, button and accent text pair for every look and mode (it caught a Calm accent at 4.3 to 1, now fixed); the browser check switches each look and scans six pages in light and dark with axe, and confirms the background, fonts and that the fonts loaded.
- Limits: the Site Editor's Styles panel only exists for block themes, so on this classic theme the Customizer is where a look is chosen; the files are ready for it if step 6 goes to block templates. The text size scales everything, since Basix sizes are in rem. A look does not change patterns or layout, only colour, corners and type.

**2. Pattern and block library (medium, grows over time; first round done, see the result below).**
- From 10 to about 40 patterns, in groups: pricing, testimonials, team, logo strip, counters, contact, newsletter, portfolio grid, blog sections, call to action bands, footers. Each uses only Basix components and tokens, and each is valid in the editor (`check-editor.mjs` already inserts every pattern).
- Pattern categories and a "Basix" category in the inserter, with a preview image per pattern.
- A few styled blocks where a pattern is not enough (tabs, counters), if the Basix components allow it.
- Result of the first round: 40 patterns (10 before, 30 new) in 14 inserter categories ("Basix: Features", "Basix: Pricing" and so on, all also in "Basix"): features (grid, beside a picture, numbered, process, services list), pricing (three plans, two plans, one offer), testimonials (one quote, three cards, with a portrait), team (three, four), logos (strip, grid), numbers (four, beside a text), call to action (text and button, band, newsletter sign-up), contact (details, with a button), portfolio and pictures (grid, feature, three pictures), blog (latest posts as cards or a list, from the Query Loop), questions, about (picture and text, values). Every pattern is valid in the editor (`check-editor.mjs` inserts all 40), uses only Basix components and tokens, so it follows every look, and has placeholder pictures in `assets/images`. A seeded **Pattern library** page shows all but the heroes in one page, and axe scans it in `make check`.
- Deviations: the inserter already shows a live preview of each pattern, so there are no separate preview images. The newsletter pattern holds a plain form that does nothing; it must be replaced by the form of a newsletter plugin (the pattern description says so). Footers are not patterns, because this theme's footer is a set of widget areas. Blocks beyond patterns (tabs, counters) are not started; Basix has no tabs or counter component that suits a block yet.

**3. Structured data (small, can go early, done; `scripts/check-schema.mjs` and `scripts/php/check-events.php` test it, both in `make check`).**
- JSON-LD for articles (`Article`, author, dates, image), breadcrumbs and the organisation; `Event` for events (start and end with time zone, location, repeats as separate dates, category) so they can show as rich results.
- Open Graph and Twitter tags as a fallback, skipped when a known SEO plugin is active (the existing check in `seo.php`).
- Check: a script validates the JSON-LD shape and that the dates match the events endpoint.
- Result: `inc/schema.php` writes one JSON-LD block (`Organization` with the social menu as `sameAs` and the logo, `WebSite` with a search action on the front page, `BreadcrumbList` on every page but the front page and the 404, `Article` on posts, `Event` on events, one per coming date for a repeating event). Open Graph and Twitter tags are written for every page (pictures from the featured image or the logo). With a known SEO plugin active only the `Event` nodes are written, since plugins do not know events. Password protected pages get nothing. Filters: `basix_schema_graph`, `basix_open_graph`. The breadcrumbs are data only; visible breadcrumbs are not part of this step.

**4. Guided start and demo import (medium, after 1 and 2).**
- A welcome screen after activation (Appearance > Basix): pick a look, pick a starter site, set the logo and menus, and links to the docs.
- Three or four starter sites (blog, magazine, business, events) as content packs, applied without a plugin: pages, menus, widgets, front page settings, images and Customizer values. Reuses the starter content and the seed scripts. "Undo" removes only what the import created.
- Check: import each starter site into a fresh site in the check script and run the frontend and axe checks on it.

**5. Header and footer flexibility (medium).**
- Options: transparent header over a hero, sticky or not, a call-to-action button, an optional top bar (text, social links, language), and footer layouts (columns, centred, minimal).
- Per-page controls in a box in the editor: hide the title, choose the sidebar, choose the header (default, transparent, none).
- A mega menu for the primary menu (a menu item class that turns its submenu into columns on wide screens; plain nested list on phones and without JavaScript).
- Also pending from section 3: live (postMessage) preview for the Customizer, a header image and background options.

**6. Compatibility (medium).**
- RTL: `rtl.css` or logical properties throughout, and a check that renders the pages with `dir="rtl"`.
- Multilingual: a `wpml-config.xml` (and Polylang strings) for options and custom fields.
- Forms: styling for Contact Form 7, WPForms and Fluent Forms output with the Basix form components.
- Page builders: a canvas template for Elementor and similar, with no header or footer.
- Open question: Site Editor support. We are a classic theme with `theme.json` (decided in section 2). Block templates and template parts would let people edit headers, footers and templates in the Site Editor. It is the biggest structural gap against block themes and a large piece of work, so decide with step 4 done and real feedback in hand.

**7. WooCommerce (large, undecided).** Out of scope today (see decisions at the top). It is the biggest buyer segment, so decide before the theme is on sale. A first step could be shop, product and cart styling with Basix components and no builder features.

**8. Selling infrastructure (medium, after 1 to 5).**
- Licence key and update delivery (a service such as Freemius, or our own update server through `Update URI`), a changelog feed and a version policy.
- Documentation site (setup, every option, patterns, hooks and filters, child theme), a few short videos, and a live demo site with each starter site.
- A support channel and a refund and licence text.
- A product page with the screenshots and the demos (this is also where the Lighthouse numbers on real hosting go).

**Smaller leftovers to fold in along the way:** the carousel arrows overlapping the category badge on narrow phones, a native speaker reading the German, and the fixed English texts in some Basix components.

**Risks.** Many looks and patterns multiply what has to be tested, so each one goes through the same editor, axe and frontend checks before it ships. Demo import touches a site's content, so it needs the "undo" and a clear warning. Licensing and updates are a business decision as much as a technical one, and a theme that is sold has to keep the GPL.

### Prototype (after 1.5)
- **Cmd-K quick switcher:** Modal + Virtual dropdown over a lightweight title/URL index. Decide after seeing it.

## Skipped
Context menu, Chat bubbles, Editor (WYSIWYG), File uploader, Color picker, and Time-span picker (only useful as a contact or booking form field).

## Risks
- Settings scope creep (keep the Customizer small).
- Core-block styling must be tested against real editor content.
- Basix class names may change between versions; pin the version in `package.json`.
