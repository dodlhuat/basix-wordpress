# Events

Events come from the companion plugin **Basix Events** (the WordPress.org theme directory does not let a theme add a post type). Activate it under Plugins. The theme then styles the events, shows the calendar and adds search engine data.

## Adding an event

**Events > Add new.** In the box under the editor:

- **Starts on**, **Start time**, **Ends on**, **End time**. Leave the times empty for an all-day event. Leave the end date empty for an event on one day.
- **Time zone**: leave it on the site's time zone, or choose the event's own. The calendar shows times in the site's zone; the event page also shows the event's own time with the zone name.
- **Location**.
- **Repeat**: every day, week (choose the weekdays), month (the same date, or the same weekday such as the second Tuesday), or year; every N times; never ending, ending on a date or after a number of times; and a list of dates to skip. The sentence under the fields says what you chose in words. A repeating event stays at the same clock time across daylight saving changes.

Use **Events > Categories** to group events. Password protected and private events never appear in lists or in the calendar feed.

## Where events show

- `/events/`: what is still to come, each repeating event once with its next date. `/events/category/<name>/` for one category.
- `/events/?event_day=2026-11-05`: everything on one day. Clicking a day in the calendar opens it.
- Each event has its own page with the rule in words, the next dates and an **Add to calendar** button.
- The events list has links to **subscribe** (`webcal://`) and to download the calendar file.

## The calendar

Add the **Events calendar** block, or the shortcode:

```
[basix_calendar view="month" category="meetups"]
```

`view` is `month`, `week` or `agenda`. `category` takes event category slugs separated by commas (without it, the calendar shows category chips). `source="posts"` shows blog posts by publish date instead. There is also a **Calendar** page template. With another theme the block and shortcode show a plain list of coming events.

## The calendar feed

`/events/feed.ics` lists all coming events for Apple, Google and Outlook calendars; `?category=slug` for one category; `?ics=1` on an event page gives that event alone. The feed follows the standard (time zones, repeats, skipped dates, categories) and is cached until an event changes.

## For developers

- The public list: `/wp-json/basix/v1/events?from=2026-11-01&to=2026-11-30&category=meetups` returns every occurrence in the range, with local and UTC times.
- Event pages carry schema.org `Event` data, one for each coming date of a repeating event.
- The plugin has its own translation and configuration for WPML and Polylang.
