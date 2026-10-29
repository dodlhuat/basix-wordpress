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
- Limits: the post type lives in the theme, so events are hidden (not deleted) if the site switches theme; no recurring events, no categories, no time zone per event (times are the site's local time); clicking a day does nothing for events; no ICS export.

### 10. Event extras (planned)
Order matters: each step builds on the one before. Every step ends with `make check`, new checks for what it adds, German strings, and a small commit per piece.

**0. Decide where events live.** Theme Check lists the post type, block and shortcode as plugin territory. If the theme should go on WordPress.org, move them first into a companion plugin (`basix-events`) so the steps below are not moved twice. For a sold theme they can stay.

**1. Event categories (small).**
- A hierarchical `basix-event-category` taxonomy (`/events/category/<name>/`), shown in the editor sidebar automatically.
- The events endpoint takes `categories=` and returns each event's categories; the calendar badge becomes the first category (the location stays in the pill's tooltip and on the event page).
- Block option and shortcode attribute `category` (slugs, comma separated); chips above the calendar filter what is already loaded, without another request. Event cards and the single page show the category badges.

**2. Time zones per event (medium, the base for 3 to 5).**
- New field `basix_event_timezone` (a select from `wp_timezone_choice()`; empty means the site's zone). Existing events keep working as they are.
- Store the wall-clock times as now, plus derived UTC start and end (`basix_event_start_utc`, `basix_event_end_utc`), written on save. Queries and sorting use the UTC fields; a version option backfills old events.
- All-day events stay "floating" (no zone, no conversion).
- Display rule: the calendar grid shows the site's time zone. An event in another zone is placed by its real moment, and its own local time with the zone name is shown on its page, in its tooltip and in the list ("6:30 pm – 8:00 pm CET").
- Edge cases to test: daylight saving gaps and repeats, events that cross midnight after conversion, zones with half-hour offsets.

**3. Recurring events (large).**
- A rule on the event, stored as one JSON meta field: frequency (daily, weekly, monthly by date, monthly by weekday such as "second Tuesday", yearly), every N, weekdays for weekly, an end (never, until a date, after N times) and a list of skipped dates. This is a subset of the standard iCalendar rule, so it can be exported unchanged.
- Occurrences are computed when read, not saved as posts: `basix_event_occurrences($event, $from, $to)` expands the rule in the event's own zone, so 6:30 pm stays 6:30 pm across daylight saving. It is capped (about 500 per event and range) so a bad rule cannot loop forever.
- The events endpoint returns occurrences with unique ids (`<id>-<date>`) and links to the event with `?date=`; the event page shows the rule in words ("Every week on Tuesday, until 20 Dec") and the next dates.
- The archive lists each recurring event once, by its next date; a derived `basix_event_next` field is refreshed on save and by a daily cron job.
- Meta box: a "Repeat" section with a live summary sentence. Editing one single occurrence is out of scope (skip a date instead).
- Tests: a PHP check script with fixed cases (month end, 31st, leap day, nth weekday, until and count, skipped dates, multi-day events that repeat, daylight saving).

**4. Clicking a day (small, after 3).**
- A day opens `/events/?event_day=2026-11-05`: the archive template listing that day's occurrences (recurring ones included), with a "No events on this day" state, and working without JavaScript.
- The calendar's day click and Enter key go there. Alternative to consider: add a `goToDate()` method to the Basix calendar itself (the library is yours) and open the week view instead.

**5. ICS export (medium, after 2 and 3).**
- An "Add to calendar" button on each event page (`?ics=1`), and a subscribe feed `/events/feed.ics` (all upcoming events; `?category=` for one category) with a `webcal://` link in the events archive.
- Output follows the standard: CRLF lines folded at 75 bytes, escaped text, stable `UID`, `SEQUENCE` from the modified time, all-day events with exclusive end dates, `TZID` with generated `VTIMEZONE` blocks (so Outlook accepts it), `RRULE` and `EXDATE` for repeats, `CATEGORIES`, `URL`, `LOCATION`.
- Password-protected and private events are left out. The feed is cached and cleared when an event is saved, with ETag support.
- Test: parse the output with an independent iCalendar parser in a check script, and compare occurrences with the PHP expansion.

**Risks.** Recurrence and daylight saving are where bugs hide, so they get fixed-case tests before any UI. Meta queries for recurring events can get slow with thousands of events (fine for a typical site; the derived fields keep it bounded). Calendar apps differ in how strictly they read ICS, so a few real imports (Apple, Google, Outlook) are needed by hand at the end.

### Prototype (after 1.5)
- **Cmd-K quick switcher:** Modal + Virtual dropdown over a lightweight title/URL index. Decide after seeing it.

## Skipped
Context menu, Chat bubbles, Editor (WYSIWYG), File uploader, Color picker, and Time-span picker (only useful as a contact or booking form field).

## Risks
- Settings scope creep (keep the Customizer small).
- Core-block styling must be tested against real editor content.
- Basix class names may change between versions; pin the version in `package.json`.
