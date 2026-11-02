# Basix for WordPress

A fast, accessible blog and magazine theme built from the [Basix](https://github.com/dodlhuat/basix) component library. Light and dark mode, header layouts, a featured slider, calendar, gallery and filter components, ten block patterns, and a German translation.

The theme lives in `theme/`. Everything else in this repository is the development setup.

## Requirements

- WordPress 6.5 or newer, PHP 8.1 or newer (tested with WordPress 7.1)
- For development: Docker, Node 20+, Composer, and Chrome (for the browser checks)

## Install the theme

Build the zip and upload it under Appearance > Themes > Add New > Upload Theme:

```sh
make zip        # writes dist/basix-<version>.zip
```

On a new site, open the Customizer to preview the starter content (pages, menus and widgets).

## Develop

```sh
make up         # npm install, start WordPress at http://localhost:8090 (admin / admin), activate the theme
make seed       # fill the site with demo pages, posts and images
make check      # PHPCS plus the editor, Customizer, front-end and accessibility checks
make zip        # build the distributable zip
make down       # stop the containers (make reset also deletes the data)
```

Other commands:

- `npm run sync` copies the Basix files the theme needs from `node_modules` into `theme/assets/vendor/basix` (also runs on `npm install`).
- `node scripts/audit-lighthouse.mjs` runs Lighthouse on key pages.
- `make lint` runs only PHPCS.
- `docker compose run --rm -T wpcli wp theme-check run basix` runs Theme Check.

## Structure

```
theme/
  functions.php, inc/        setup, Customizer, menus, filters, SEO, starter content
  templates and template-parts/
  page-templates/            front page, docs, gallery, calendar, landing
  patterns/                  ten block patterns
  assets/                    css, js, images; vendor/basix is generated and gitignored
  languages/                 basix.pot and the German translation
scripts/                     setup, seeding, asset sync, zip build, browser checks
ROADMAP.md                   plan and status per milestone
```

## Principles

Only Basix components and tokens are used. Theme CSS stays layout glue, so colours, radius, spacing and type come from Basix tokens and follow light and dark mode.

## License

GPL v2 or later. Bundled: Basix (MIT), Outfit, Inter, Space Grotesk, Source Serif 4 and Fraunces typefaces (SIL OFL 1.1), Material icons (Apache 2.0). See `theme/readme.txt`.
