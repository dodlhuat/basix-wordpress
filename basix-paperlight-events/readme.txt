=== Basix Paperlight Events ===
Contributors: dodlhuat
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: events, calendar, recurring events, schema, starter sites

Events with dates, times, time zones and repeats, an events calendar block and shortcode, a calendar feed, structured data and starter sites. Made for the Basix theme.

== Description ==

Basix Paperlight Events adds an Events section to your site:

* An Event post type with a start and end (date, optional time), a place, a time zone for each event and categories
* Repeating events: daily, weekly (on chosen weekdays), monthly (by date or by weekday, such as the second Tuesday), yearly; every N times; an end date or a number of times; and dates to skip
* An events list at /events/ (what is still to come, each repeating event once), category lists, and a list for any day (/events/?event_day=2026-11-05)
* An "Add to calendar" file for each event, and a subscribe feed of all coming events (/events/feed.ics, or ?category=slug), with correct time zones for Apple, Google and Outlook calendars
* A public list of events for a range of days at /wp-json/basix/v1/events
* The Events calendar block and the [basix_calendar] shortcode. With the Basix theme they show a month, week and agenda calendar; with another theme they show a plain list of the coming events
* Password protected and private events are never listed in the feed
* Structured data (schema.org JSON-LD for the site, breadcrumbs, articles and events), Open Graph and Twitter tags, and a meta description. They step aside for a well-known SEO plugin, except for events, which SEO plugins do not know
* Share links under posts (email, X, LinkedIn, Facebook, Bluesky, and copy link), with a switch under Customize > Basix > Posts
* With the Basix Paperlight theme: a setup screen (Appearance > Basix setup) with four ready-made starter sites (Blog, Magazine, Business, Events) that can be imported and undone, also from WP-CLI (wp basix starter)

The Basix theme styles the events and adds the calendar. With another theme the events work, and you style them yourself.

== Installation ==

1. Upload the plugin zip in Plugins > Add New > Upload Plugin, or copy the folder to wp-content/plugins.
2. Activate it. Events appears in the admin menu.
3. If the event addresses give "not found", open Settings > Permalinks and press Save once.

== Frequently Asked Questions ==

= Do I need the Basix theme? =

No. Events, repeats, time zones and the calendar feed work with any theme. The calendar views need the Basix theme; other themes get a list.

= What happens to my events if I switch the plugin off? =

They stay in the database and come back when you switch it on again. Removing the plugin does not delete them.

= Which time zone do events use? =

Each event can have its own; without one it uses the site's time zone. All-day events have no time zone.

== Changelog ==

= 1.0.0 =
* First release: events, repeats, time zones, categories, the day list, the calendar feed and the calendar block and shortcode.
* Structured data, social tags, the meta description, the share links and the starter sites moved here from the theme.
