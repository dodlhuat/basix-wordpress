# Roadmap

Goal: a premium-feeling WordPress theme built from the Basix component library, distributed through the WordPress.org theme directory.

The long version of every milestone (results, deviations, limits) is in [docs/development-notes.md](docs/development-notes.md). How things work now is in [docs/](docs/README.md).

## Decisions

- **Distribution:** the WordPress.org theme directory (GPL theme; Basix is MIT; the fonts are OFL). No licence keys or update server.
- **Editor:** hybrid. PHP templates that use Basix markup, plus `theme.json`, styled core blocks and block patterns. Whether to add block templates for the Site Editor is open (see below).
- **Events:** the companion plugin `basix-paperlight-events/`. The directory does not let a theme register a post type, a taxonomy, a block or a shortcode, so those live in the plugin; the theme works without it.
- **WooCommerce:** out of scope; a known gap.
- **Principle:** only Basix components and tokens; theme CSS stays layout glue.

## Status

| # | Milestone | State |
| --- | --- | --- |
| 1 | Compliance baseline: licence, headers, escaping, Theme Check, PHPCS | done |
| 1.5 | Asset pipeline: only the Basix parts the theme uses are copied in | done |
| 2 | Editor foundation: `theme.json`, shared editor styles, patterns, block styles | done |
| 3 | Settings and menus: Customizer panel, three header layouts, three menu locations | done |
| 4 | Layout and templates: footer widgets, page templates, search modal | done |
| 5 | Post features: reading time, contents, share, related posts, author box, lightbox, featured slider | done |
| 6 | Discovery and media: filters, calendar page, photo gallery, loading states | done |
| 7 | Premium polish: scroll reveals, card layouts, hero variants, image styles | done |
| 8 | Release quality: axe and Lighthouse audits, German translation, readme, screenshot | done |
| 9 | Events: post type, calendar block and shortcode, public events list | done |
| 10 | Event extras: categories, time zones, repeats, day list, calendar feed | done |
| 11 | Premium breadth: five looks, forty patterns, structured data, setup screen with four starter sites, header and footer options, right-to-left, forms, page builders, the plugin split and the directory checks | done |
| 12 | Optional improvements: live accent preview, real Arabic text in the checks, Elementor single and archive locations, user and developer documentation | done |

`make check` runs about twenty checks (see [docs/developers.md](docs/developers.md#the-checks)) and passes.

## Open

Needs a person:

- Submit `dist/basix-paperlight-1.0.0.zip` to the theme directory, following [the checklist](docs/submission-checklist.md) (the review is manual; the `accessibility-ready` tag is left out for now). Decide whether `basix-paperlight-events` is also submitted as a plugin.
- Open the main pages in Firefox, Safari and Edge by hand (the checks only use Chrome), and optionally try the WordPress nightly with the Beta Tester plugin.
- Try the integrations by hand once: Contact Form 7, WPForms, Fluent Forms, Gravity Forms, Polylang, WPML and Elementor. The checks use their markup or stand-ins, not the plugins.
- Import the calendar feed into Apple, Google and Outlook calendars (the checks use an independent parser only).
- Have a native speaker read the German text, and check Lighthouse on real hosting (it ran against a local server without compression or caching).
- Run the output through Google's Rich Results Test once the demo site is online.

Open decisions and ideas:

- **Site Editor:** block templates and template parts would let people edit headers, footers and templates in the Site Editor. The biggest structural gap against block themes; decide with real feedback.
- **Cmd-K quick switcher:** a Modal with a Virtual dropdown over a small title and URL index. Undecided; decide after seeing it.
- Other builders' own hooks (Beaver Themer, Bricks) beyond the `basix_builder_location` filter.
- WooCommerce styling, if it ever becomes worth it.
- Not done on purpose: a background image option (it would fight the looks' contrast guarantees), `wp-block-styles` support, and a calendar that mirrors in right-to-left languages.

## Skipped

Context menu, Chat bubbles, Editor (WYSIWYG), File uploader, Color picker, and Time-span picker (only useful as a contact or booking form field).

## Risks

- Settings scope creep: keep the Customizer small.
- Core-block styling must be tested against real editor content.
- Basix class names may change between versions; the version is pinned in `package.json`.
- Many looks and patterns multiply what has to be tested; each goes through the editor, axe and front-end checks before it ships.
