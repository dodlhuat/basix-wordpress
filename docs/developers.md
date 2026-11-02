# Developing the theme

```sh
make up        # npm install, start WordPress at http://localhost:8090 (admin / admin), activate the theme and the plugin
make seed      # demo pages, posts, events and the Pattern library page
make check     # PHPCS and every check (see below)
make zip       # dist/basix-<version>.zip and dist/basix-events-<version>.zip
```

Needs Docker, Node 20 or newer, Composer and Chrome. `npm run sync` copies the parts of the Basix library the theme uses into `theme/assets/vendor/basix` (generated, not committed), and `npm run rtl` makes the mirrored stylesheets.

## Structure

```
theme/                   the theme
  functions.php          setup; loads inc/
  inc/looks.php          looks, fonts, the CSS they write
  inc/customizer.php     Customizer settings and the colour maths
  inc/header.php         header, footer and per-page options, builder locations
  inc/schema.php         structured data and social tags
  inc/setup/             the setup screen, starter sites import and undo, WP-CLI command
  starters/              the four starter site definitions
  styles/                the five looks
  patterns/              the block patterns (PHP files with a header comment)
  template-parts/        pieces of templates
  assets/                css, js, fonts, images
basix-events/            the companion plugin (events, repeats, feed, calendar block and shortcode)
scripts/                 setup, seeding, build, and every check
```

Design rule: only Basix components and tokens. Theme CSS stays layout glue, so colours, radius, spacing and type come from tokens and follow every look and dark mode.

## Hooks and filters

| Name | Kind | What it does |
| --- | --- | --- |
| `basix_meta_description` | filter | The page's meta description (return an empty string for none) |
| `basix_schema_graph` | filter | The list of schema.org nodes written in the page head |
| `basix_open_graph` | filter | The Open Graph and Twitter tags, as property => content |
| `basix_language_switcher` | filter | The HTML of the top bar's language switcher |
| `basix_builder_location` | filter | Whether a page builder drew a part of the page (`header`, `footer`, `single`, `archive`); when it does, the theme leaves its own out |
| `basix_hero_attributes` | filter | Extra attributes on the front page introduction |
| `basix_starter_imported` | action | After a starter site was imported (slug, record) |
| `basix_starter_undone` | action | After an import was undone (record) |

## Adding a look

A look is a file in `theme/styles/<slug>.json`: a theme.json style variation with the design data under `settings.custom.basix`:

```json
{
  "version": 3,
  "title": "Calm",
  "settings": { "custom": { "basix": {
    "order": 3,
    "description": "Soft greens, rounded corners and friendly Inter type.",
    "light": { "background": "#F6FAF7", "surface": "#E6EFE9", "text": "#1B2A22", "textSecondary": "#475C50", "divider": "#CCDBD1", "accent": "#1F7A55" },
    "dark":  { "background": "#0F1713", "surface": "#16211B", "text": "#E6F0EA", "textSecondary": "#A9BDB0", "divider": "#2B3C32", "accent": "#24805D" },
    "radius": "round",
    "fonts": { "heading": "inter", "body": "inter" }
  } } }
}
```

`radius` is `none`, `sharp`, `default` or `round`. Fonts are keys of `basix_fonts()` in `inc/looks.php`. Add a title and description to `basix_look_texts()` to make them translatable. `make check` tests the contrast of every pair in both modes (`scripts/php/check-looks.php`) and scans pages in the new look with axe (`scripts/check-looks.mjs`); a pair below 4.5:1 fails.

## Adding a starter site

A file in `theme/starters/<slug>.php` that returns an array: `title`, `description`, `look`, `mods` (Customizer values), `front` (`posts` or a page key), optional `blog` (the posts page), `sticky` (how many sample posts to make sticky), `pages` (title, `content` as a string or a function, optional `template`), `menus` (`primary` and `footer`, as lists of page keys, `home` and `events-archive`), `posts` (keys of the sample post library in `inc/setup/content.php`), `events` and `widgets`. Add the slug to the list in `basix_starters()`. `basix_sc_pattern( $slug, $texts )` returns a pattern with some of its texts replaced by demo copy.

## Adding a pattern

A PHP file in `theme/patterns/` with a header comment (`Title`, `Slug: basix/<name>`, `Description`, `Categories`, `Keywords`). Write the blocks as the editor saves them; `scripts/check-editor.mjs` inserts every pattern in the real editor and fails on invalid blocks. Wrap texts in `esc_html_x( 'Text', 'Context pattern', 'basix' )`.

## The checks

`make check` runs `scripts/check.sh`, which sets the site to English, runs everything, and puts the language back:

| Check | What it covers |
| --- | --- |
| `php/check-events.php` | event dates, time zones, repeats, the calendar feed text, structured data of events |
| `php/check-looks.php`, `check-looks.mjs` | contrast of every look, fonts, the written CSS; every look in a browser with axe |
| `check-accent-math.mjs` | the live preview's colour maths against the PHP |
| `php/check-starters.php`, `check-starters.mjs` | import and undo of every starter, the setup screen, every starter scanned with axe |
| `php/check-header.php`, `check-header.mjs` | header and footer options, per-page choices, the mega menu, live preview |
| `php/check-compat.php`, `check-compat.mjs`, `check-rtl.mjs` | configuration files, builder locations in every template, plugin forms, the canvas, Arabic |
| `check-editor.mjs`, `check-customizer.mjs` | every pattern valid in the editor; the Customizer and its preview |
| `check-ics.mjs`, `check-schema.mjs` | the calendar feed with an independent parser; the structured data against the events list |
| `check-frontend.mjs`, `check-a11y.mjs` | the front end in a browser; axe on every main page in four setups |
| `check-without-plugin.mjs` | the theme with the events plugin switched off |
| `check-docs.mjs` | the links and names in these documents |

Single checks: `npm run check:<name>` (see `package.json`), and `docker compose run --rm -T wpcli wp eval-file /php/<file>`.
