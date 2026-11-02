# Languages, right-to-left and plugins

## Translation

Basix is translation-ready and ships a German translation; the plugin has its own. Change the site language in **Settings > General**, or translate with Loco Translate or Poedit from `languages/basix.pot`. The demo posts of the starter sites are English.

Polylang and WPML read the `wpml-config.xml` files of the theme and the plugin: events and their categories are translatable, event dates and the per-page layout choices are copied between translations, and the place of an event, the footer line, the top bar message and the header button are translated.

## Right-to-left languages

Arabic, Hebrew, Persian, Urdu and others work: the stylesheets are mirrored (`style-rtl.css` and the like load by themselves), the sidebar, menus and buttons switch sides, and for these scripts slanted type and letter spacing are turned off because they break joined letters.

The slider and the calendar keep their own left-to-right arrangement; the text inside them follows its language. The menu drawer opens from the right in both directions.

## Forms

Forms from **Contact Form 7**, **WPForms**, **Fluent Forms** and **Gravity Forms** are styled like the theme's own fields and buttons, with readable errors and answers, in every look and in dark mode. No settings are needed.

## Page builders

- The **Canvas** page template shows only the content, with no header, footer, breadcrumb or title, for Elementor, Beaver Builder, Bricks, Divi and the like.
- **Elementor's theme builder**: the header, footer, single (pages, posts, events and the 404 page) and archive (lists and search) locations are honoured. Where a template exists in Elementor, the theme leaves its own markup out.
- Other builders can use the `basix_builder_location` filter (see [Developing](developers.md#hooks-and-filters)).

## SEO plugins

Without an SEO plugin Basix writes a meta description, Open Graph and Twitter tags and schema.org data (organisation, website, breadcrumbs, articles, events). When Yoast SEO, Rank Math, All in One SEO, SEOPress, Slim SEO or The SEO Framework is active, Basix leaves those to it and only adds the event data, which those plugins do not know.
