# Troubleshooting

**Event pages say "not found".** Open **Settings > Permalinks** and press **Save changes** once. The addresses are flushed when the plugin is activated, but some hosts cache them.

**The header does not stay at the top.** Check **Customize > Basix > Header > Keep the header at the top while scrolling**, and that the page does not use a header of its own.

**The transparent header is hard to read.** It assumes a dark picture behind it. Put a cover block with a dark overlay at the top, or use the solid header on that page.

**The calendar shows nothing.** The calendar loads its events from the REST API. A security plugin that blocks `/wp-json/` also blocks the calendar, the gallery and the filters. They show a message instead.

**Fonts look different from the screenshots.** Check **Customize > Basix > Typography**: a chosen font beats the look's own. Choose "follow the look's font".

**My accent colour is ignored after a starter import.** Importing a starter clears your accent, fonts and text size so the look can show; **Undo this import** brings them back.

**The newsletter pattern does not subscribe anyone.** It is a plain form. Replace it with your newsletter plugin's form.

**The code block's copy button does nothing.** Browsers allow copying only on pages served over HTTPS (and on localhost).

**Text in some buttons of the lightbox and the code viewer is English.** Those parts come from the Basix component library, which has fixed English labels.

**Calendar apps show events an hour off.** Check the site's time zone in **Settings > General** (choose a city, not a UTC offset) and the event's own time zone.

If something else goes wrong, switch the Basix Events plugin off and on again, and re-save permalinks; the theme works without the plugin, so that tells you which of the two is involved.
