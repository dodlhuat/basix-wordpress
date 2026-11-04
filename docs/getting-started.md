# Getting started

## Install

1. Build the zips with `make zip`, or take the files from a release: `basix-paperlight-<version>.zip` is the theme and `basix-paperlight-events-<version>.zip` is the optional events plugin.
2. In the admin, go to **Appearance > Themes > Add New > Upload Theme**, choose the theme zip and activate it.
3. For events, go to **Plugins > Add New > Upload Plugin**, choose the plugin zip and activate it. The theme works without it; see [Events](events.md).

Basix needs WordPress 6.5 or newer and PHP 8.1 or newer.

## The setup screen

After you switch the theme on, a notice points to **Appearance > Basix setup**. It has two choices and a button:

1. **Pick a look.** Five looks set the colours (light and dark), the roundness of corners and the fonts together. Choose "the starter site's own look" to take what the starter suggests.
2. **Pick a starter site**, or "No content, only the look" to keep your site as it is.
3. Press **Set up my site**.

Starter sites:

| Starter | What it adds |
| --- | --- |
| Blog | six sample posts with pictures, About and Contact pages, a sidebar |
| Magazine | nine sample posts, three of them in a slider of featured stories, About and Contact |
| Business | a landing page (hero, features, numbers, testimonials, prices), Services, Pricing, About, News and Contact, three news posts |
| Events | sample events (one repeating every week), an agenda and a calendar page, About and Contact. Needs the Basix Events plugin; without it this starter is not offered. |

Every starter also sets a few Customizer values (layout, introduction, sidebar), a primary and a footer menu, and fills empty footer areas with an About text, the latest posts and the tags.

**Nothing you made is deleted.** An import only adds. The setup screen shows **Undo this import** while an import is active: it removes exactly what the import added and puts the settings back. Changes you made to the added pages and posts are lost with them. Importing another starter undoes the first one. The demo texts are English.

You can do the same from the command line:

```sh
wp basix starter list
wp basix starter import blog --look=calm
wp basix starter undo
```

## Logo, name and menus

- **Appearance > Customize > Site Identity** for the site name, tagline and logo.
- **Appearance > Menus** for the menus. Basix has three places: the primary menu (three levels deep), a footer menu and a social links menu.
- **Appearance > Customize > Basix** has every theme option: Look, Typography, Header, Top bar, Colours, Layout, Front page, Footer and Posts.
- **Appearance > Widgets** for the sidebar and the three footer columns (only columns with widgets are shown).

## Writing posts

Posts get a reading time, a table of contents, a reading progress bar, share buttons, an author box when the author has a biography, related posts, and a lightbox on pictures. Each can be switched off under **Customize > Basix > Posts**. Sticky posts form the slider on the front page.
