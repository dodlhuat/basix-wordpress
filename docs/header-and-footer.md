# Header and footer

All of these are under **Appearance > Customize > Basix**.

## Header

- **Menu layout**: inline links with dropdowns (a menu button on small screens), a menu button opening a side panel, or a menu button pushing the page aside with drill-down panels.
- **Keep the header at the top while scrolling**: on by default.
- **Header style**: solid, or *transparent over the first section*: the header sits over the first section of the page with light text until you scroll, then turns solid. Use it with a dark picture at the top, for example the Landing page template with a cover block. A single page can choose its own (see below).
- **Button text and address**: a call-to-action button in the header. It needs both. On phones it moves to the end of the opened menu.

## Top bar

**Customize > Basix > Top bar**: a thin line above the header with a short message, your social links menu and a language switcher. The switcher shows when Polylang or WPML is active; other plugins can supply one (see [Developing](developers.md#hooks-and-filters)).

## Mega menu

In **Appearance > Menus**, open Screen Options (top right) and tick **CSS Classes**. Give a top-level item the class `mega-menu`. Its children become columns with a heading, and their own children are the links below. On phones it stays a simple nested list.

## Header image

**Customize > Header Image**: the picture becomes the backdrop of the introduction on the front page, with a dark veil so the white text stays readable.

## Footer

**Customize > Basix > Footer**: the footer line (`{year}` and `{site}` are replaced), the back-to-top button and the layout: widget columns then the footer line, centred, or minimal (no widget columns).

## Choices for one page

Pages, posts and events have a **Page layout** box in the editor:

- **Header**: as in the Customizer, transparent over the first section, or no header at all (the top bar goes too).
- **Sidebar**: default (pages have none, posts follow the Customizer), right, left or none.
- **Footer**: as in the Customizer, or no footer widgets and menus.
- **Hide the title**: the title is hidden for sight but stays in the page for screen readers.
