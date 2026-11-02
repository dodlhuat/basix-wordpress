# Looks and typography

## Looks

A look is a set of colours for light and dark mode, a corner roundness and a pair of fonts. Choose one in **Customize > Basix > Look** or on the [setup screen](getting-started.md#the-setup-screen).

| Look | Character |
| --- | --- |
| Basix | The shipped look: calm blue, Outfit type |
| Editorial | Warm paper, terracotta accent and serif type, for long reading |
| Bold | High contrast, violet accent and a geometric heading font |
| Calm | Soft greens, rounded corners and friendly Inter type |
| Paper | Black on white, square corners and a serif heading font |

Every colour pair of every look, in both modes, is checked for readable contrast, and every page is scanned with an accessibility tool in every look.

## Typography

Under **Customize > Basix > Typography**:

- **Heading font** and **Text font**: "follow the look's font", Outfit, Inter, Space Grotesk, Source Serif 4, Fraunces, or the system font (nothing to download). The fonts are stored in the theme; nothing is loaded from another site.
- **Text size**: small, medium or large. It scales the whole page, spacing and buttons included.

## Accent colour

**Customize > Basix > Colours** has the accent colour for light mode and for dark mode, and the default colour mode (follow the visitor's device, always light, or always dark). Leave the accent as it is to use the look's own. When you change it, the preview shows the result at once, and the text colours that must be readable on it (on buttons, and for links and badges) are worked out for you.

Visitors can switch between light and dark with the button in the header; their choice is remembered.

## Corners

Corners follow the look (none, sharp, default, round). There is no separate setting; choose another look, or change the look's `radius` in its file (see [Developing the theme](developers.md#adding-a-look)).
