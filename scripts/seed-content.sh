#!/usr/bin/env bash
# Replaces the default WordPress content with sample posts, pages, images and a menu.
# Local development only: it deletes existing posts and pages. Pass --force to re-run.
set -euo pipefail
cd "$(dirname "$0")/.."

wp() { docker compose run --rm -T wpcli wp "$@"; }
days_ago() { python3 -I -c "import datetime,sys;print((datetime.datetime.now(datetime.UTC)-datetime.timedelta(days=int(sys.argv[1]))).strftime('%Y-%m-%d %H:%M:%S'))" "$1"; }

if [ "$(wp option get basix_seeded 2>/dev/null || true)" = "1" ] && [ "${1:-}" != "--force" ]; then
  echo "Already seeded. Use --force to replace the content again."
  exit 0
fi

echo "Clearing existing content..."
wp post delete $(wp post list --post_type=post,page --post_status=any --format=ids) --force >/dev/null 2>&1 || true
wp media regenerate --yes >/dev/null 2>&1 || true

wp user update 1 --display_name="Basix Editor" >/dev/null
wp option update blogdescription "Notes on design systems, interfaces and the web." >/dev/null
for c in Design Engineering Notes; do wp term create category "$c" >/dev/null 2>&1 || true; done

# post <title> <category> <tags> <image> <alt> <days-ago>   (body from stdin)
post() {
  local id
  id=$(wp post create - --post_type=post --post_status=publish --post_title="$1" \
    --post_author=1 --post_category="$2" --tags_input="$3" --post_date="$(days_ago "$6")" --porcelain)
  wp media import "/sample-images/$4.jpg" --post_id="$id" --featured_image --title="$1" --alt="$5" --porcelain >/dev/null
  echo "$id"
}

FIRST=$(post "Design tokens are the contract" Design "tokens,css" aurora "Blue and teal gradient" 1 <<'BODY'
<p>A design system lives or dies by how well it survives a rebrand. When every colour, radius and spacing value is a named token, changing the accent colour is a one-line edit instead of a week of search and replace.</p>
<h2>Two layers are enough</h2>
<p>Raw palettes tempt teams into three or four layers of indirection. In practice a semantic layer (<code>--accent-color</code>, <code>--secondary-text</code>) on top of component defaults covers almost every case, and it keeps the system readable.</p>
<ul><li>Name tokens after intent, not appearance.</li><li>Give every colour token a dark-mode value up front.</li><li>Let components fall back to the global token.</li></ul>
<blockquote><p>If you can't explain a token in one sentence, it probably shouldn't exist.</p></blockquote>
<p>Start small, ship the tokens first, and let the components grow around them.</p>
BODY
)

post "Dark mode without the flash" Engineering "dark-mode,javascript" sunset "Purple and orange gradient" 3 <<'BODY' >/dev/null
<p>The most common dark-mode bug isn't ugly colours, it's the flash: the page paints light, then JavaScript flips it. The fix is small and has to run before the first paint.</p>
<h2>Read the preference early</h2>
<p>A tiny inline script in the document head reads the saved choice and sets <code>data-theme</code> before any content renders. The rest of the logic can stay in a deferred module.</p>
<h2>Respect the system</h2>
<p>When nothing is saved, follow <code>prefers-color-scheme</code> and keep following it until the visitor makes an explicit choice.</p>
<ol><li>Inline script sets the attribute.</li><li>CSS reads custom properties only.</li><li>The toggle stores the new value.</li></ol>
BODY

post "Spacing is a rhythm, not a number" Design "spacing,layout" meadow "Green and yellow gradient" 6 <<'BODY' >/dev/null
<p>Good layouts feel calm because their gaps repeat. Pick a base unit, derive everything else from it, and resist one-off values; the eye notices when a margin is simply a little off.</p>
<h2>Fewer sizes, better pages</h2>
<p>Five spacing steps are enough for most interfaces. More choices usually mean more inconsistency, not more expression.</p>
<p>When a layout still looks wrong, the answer is rarely a new value. It is usually a grouping problem: related things should sit closer together than unrelated ones.</p>
BODY

post "Shipping a WordPress theme with npm" Engineering "wordpress,npm" ember "Orange and red gradient" 9 <<'BODY' >/dev/null
<p>WordPress hosts rarely run Node, so a theme has to ship its built files. The trick is to keep npm as a development convenience and copy exactly what the theme needs into a vendor folder.</p>
<h2>Sync, don't bundle</h2>
<p>A small script copies the stylesheet, the scripts and the fonts out of the package. The theme then works anywhere PHP does, and updating the library is one command.</p>
<ul><li>Keep the vendor folder out of git.</li><li>Preserve the directory layout so relative URLs keep working.</li><li>Load scripts as ES modules.</li></ul>
BODY

post "Accessible by default" Notes "accessibility" lagoon "Teal and blue gradient" 12 <<'BODY' >/dev/null
<p>Accessibility is cheapest at the start. Semantic markup, visible focus states and sufficient contrast cost almost nothing when they are the default, and a lot when they are retrofitted.</p>
<h2>A short checklist</h2>
<ul><li>Every control reachable by keyboard.</li><li>A skip link at the top of the page.</li><li>Headings in a sensible order.</li><li>Decorative images hidden from screen readers.</li></ul>
<p>None of this is glamorous, and all of it is noticed by the people who need it.</p>
BODY

post "Small components, big leverage" Design "components" orchid "Pink and violet gradient" 16 <<'BODY' >/dev/null
<p>A badge, a chip and a button look like trivia next to a full page layout. But a dozen well-made small components do most of the work in a real interface, and their quality is what people feel.</p>
<h2>Details that add up</h2>
<p>Consistent corner radii, a calm hover state and a clear focus ring make an interface feel considered even when the layout is plain.</p>
<blockquote><p>Polish is the sum of boring decisions made carefully.</p></blockquote>
BODY

post "Notes on writing readable CSS" Notes "css" slate "Soft lavender gradient" 21 <<'BODY' >/dev/null
<p>Readable CSS is mostly about restraint. Use custom properties for anything that repeats, keep selectors shallow and let the cascade do the work it was designed for.</p>
<h2>Habits worth keeping</h2>
<ul><li>Prefer classes over deep selectors.</li><li>Group rules by component, not by property.</li><li>Delete code the moment it is unused.</li></ul>
<p>The best stylesheet is the one you can skim in a minute and still understand.</p>
BODY

post "Choosing a type scale" Design "typography" citrus "Warm yellow and green gradient" 28 <<'BODY' >/dev/null
<p>Typography carries most of a page. A modest scale, a variable font and generous line height will beat an elaborate scheme almost every time.</p>
<h2>Keep it modest</h2>
<p>Four or five sizes are plenty. Use weight and colour for emphasis before reaching for another size, and keep line length between 60 and 75 characters for body text.</p>
<p>Fluid sizes with <code>clamp()</code> remove most breakpoints from headings, which keeps the stylesheet short.</p>
BODY

# password-protected post (password: secret)
PROTECTED=$(wp post create - --post_type=post --post_status=publish --post_title="Members only" \
  --post_author=1 --post_password=secret --post_category=Notes --post_date="$(days_ago 30)" --porcelain <<'BODY'
<p>You found the secret content. This post exists to show how the theme styles the password form and its error state.</p>
BODY
)
wp media import /sample-images/slate.jpg --post_id="$PROTECTED" --featured_image --title="Members only" --alt="Soft lavender gradient" --porcelain >/dev/null

# pages
ABOUT=$(wp post create - --post_type=page --post_status=publish --post_author=1 --post_title="About" --porcelain <<'BODY'
<p>This is a small publication about design systems, interface craft and the web platform. It is also a demo site for a WordPress theme built entirely from the Basix component library.</p>
<h2>What you'll find here</h2>
<ul><li>Short essays on design and engineering.</li><li>Notes from building real interfaces.</li><li>Practical, copy-able techniques.</li></ul>
<p>Everything on this site is sample content, written to show how the theme handles headings, lists, quotes and images.</p>
BODY
)
wp media import /sample-images/lagoon.jpg --post_id="$ABOUT" --featured_image --title="About" --alt="Teal and blue gradient" --porcelain >/dev/null
CONTACT=$(wp post create - --post_type=page --post_status=publish --post_author=1 --post_title="Contact" --porcelain <<'BODY'
<p>Questions, ideas or corrections are always welcome. Leave a comment on any post, or get in touch through the form below once you've connected a mail plugin.</p>
BODY
)

# block showcase page (patterns and the core blocks the theme styles)
SHOWCASE_ID=$(wp eval-file /seed/showcase.php)

# subcategories, so the category filter has a hierarchy to show
term_id() { wp term get category "$1" --by=slug --field=term_id; }
wp term create category Typography --slug=typography --parent="$(term_id design)" >/dev/null 2>&1 || true
wp term create category Performance --slug=performance --parent="$(term_id engineering)" >/dev/null 2>&1 || true
set_categories() { wp post term set "$(wp post list --post_type=post --name="$1" --field=ID)" category "${@:2}" >/dev/null; }
set_categories choosing-a-type-scale design typography
set_categories shipping-a-wordpress-theme-with-npm engineering performance

# a long demo post, an author biography and sticky posts for the featured slider
LONG_ID=$(wp eval-file /seed/long-post.php | tail -1)
wp user update 1 --description="Writes about design systems, interface craft and the web platform. Likes small components and boring decisions made carefully." >/dev/null
wp option update sticky_posts "[$LONG_ID,$FIRST]" --format=json >/dev/null

# a documentation section and a landing page built from the patterns
DOCS_ID=$(wp eval-file /seed/docs.php | tail -1)
LANDING_ID=$(wp eval-file /seed/landing.php | tail -1)
PAGE_IDS=$(wp eval-file /seed/pages.php | tail -2)
CALENDAR_ID=$(echo "$PAGE_IDS" | head -1)
GALLERY_ID=$(echo "$PAGE_IDS" | tail -1)
wp option update timezone_string Europe/Vienna >/dev/null
wp eval-file /seed/events.php >/dev/null

# footer widgets (three columns)
for sb in footer-1 footer-2 footer-3; do wp widget reset "$sb" >/dev/null 2>&1 || true; done
wp widget add text footer-1 --title="About" --text="A small publication about design systems, interfaces and the web." >/dev/null
wp widget add recent-posts footer-2 --title="Latest posts" --number=3 >/dev/null
wp widget add tag_cloud footer-3 --title="Tags" >/dev/null

# menus: a nested primary menu (three levels), a footer menu and social links
menu_item() { wp menu item "$@" --porcelain; }
for m in Main Footer Social; do wp menu delete "$m" >/dev/null 2>&1 || true; wp menu create "$m" >/dev/null; done

menu_item add-custom Main Home / >/dev/null
TOPICS=$(menu_item add-custom Main Topics '#')
DESIGN_CAT=$(wp term get category Design --by=slug --field=term_id)
ENG_CAT=$(wp term get category Engineering --by=slug --field=term_id)
NOTES_CAT=$(wp term get category Notes --by=slug --field=term_id)
DESIGN_ITEM=$(menu_item add-term Main category "$DESIGN_CAT" --parent-id="$TOPICS")
menu_item add-term Main post_tag "$(wp term get post_tag tokens --by=slug --field=term_id)" --parent-id="$DESIGN_ITEM" >/dev/null
menu_item add-term Main post_tag "$(wp term get post_tag css --by=slug --field=term_id)" --parent-id="$DESIGN_ITEM" >/dev/null
menu_item add-term Main category "$ENG_CAT" --parent-id="$TOPICS" >/dev/null
menu_item add-term Main category "$NOTES_CAT" --parent-id="$TOPICS" >/dev/null
ABOUT_ITEM=$(menu_item add-post Main "$ABOUT")
menu_item add-post Main "$SHOWCASE_ID" --parent-id="$ABOUT_ITEM" >/dev/null
EXPLORE=$(menu_item add-custom Main Explore '#')
menu_item add-post Main "$CALENDAR_ID" --parent-id="$EXPLORE" >/dev/null
menu_item add-post Main "$GALLERY_ID" --parent-id="$EXPLORE" >/dev/null
menu_item add-post Main "$DOCS_ID" --parent-id="$EXPLORE" >/dev/null
menu_item add-post Main "$LANDING_ID" --parent-id="$EXPLORE" >/dev/null
menu_item add-post Main "$CONTACT" >/dev/null
wp menu location assign Main primary >/dev/null

menu_item add-post Footer "$ABOUT" >/dev/null
menu_item add-post Footer "$CONTACT" >/dev/null
wp menu location assign Footer footer >/dev/null

menu_item add-custom Social Mastodon https://mastodon.social/ >/dev/null
menu_item add-custom Social GitHub https://github.com/dodlhuat >/dev/null
menu_item add-custom Social RSS "$(wp option get home)/feed/" >/dev/null
wp menu location assign Social social >/dev/null

# comments on the first post
wp comment create --comment_post_ID="$FIRST" --comment_author="Maya" --comment_author_email="maya@example.com" --comment_content="Two layers is exactly what worked for us. Three just confused everyone." --comment_approved=1 >/dev/null
wp comment create --comment_post_ID="$FIRST" --comment_author="Jonas" --comment_author_email="jonas@example.com" --comment_content="Do you document tokens in the same repo as the components?" --comment_approved=1 >/dev/null

wp option update basix_seeded 1 >/dev/null
echo "Done: 9 posts (one password-protected, password: secret), 2 pages, a menu and sample comments."
