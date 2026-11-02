// Reads the events feed with an independent iCalendar parser (ical.js) and compares what it expands to with the
// dates the site itself gives (the public events endpoint). Needs the local site running (make up).
//   node scripts/check-ics.mjs
// Env: WP_URL (default http://localhost:8090).
import ICAL from 'ical.js';

const url = process.env.WP_URL ?? 'http://localhost:8090';
const failures = [];
const check = (ok, what) => {
  console.log(`${ok ? 'ok  ' : 'FAIL'} ${what}`);
  if (!ok) failures.push(what);
};
const day = (date) => date.toISOString().slice(0, 10);
const plusDays = (n) => new Date(Date.now() + n * 86400000);

// The feed: right headers, and the same text again answers 304.
const feedUrl = `${url}/events/feed.ics`;
const response = await fetch(feedUrl);
const text = await response.text();
check(response.status === 200 && (response.headers.get('content-type') ?? '').startsWith('text/calendar'), 'the feed is served as text/calendar');
const again = await fetch(feedUrl, { headers: { 'If-None-Match': response.headers.get('etag') ?? '' } });
check(again.status === 304, 'asking again with the ETag answers 304');
check(text.includes('\r\n') && !/[^\r]\n/.test(text), 'every line ends with CRLF');
check(text.split('\r\n').every((line) => new TextEncoder().encode(line).length <= 75), 'no line is longer than 75 bytes');

// Parse with the independent library and expand every event inside a window.
const calendar = new ICAL.Component(ICAL.parse(text));
for (const zone of calendar.getAllSubcomponents('vtimezone')) {
  ICAL.TimezoneService.register(zone);
}
const from = day(plusDays(1));
const to = day(plusDays(55));
const inWindow = (date) => date >= from && date <= to;
const fromIcs = new Set();
const events = calendar.getAllSubcomponents('vevent');
check(events.length > 0, `the feed has events (${events.length})`);
for (const vevent of events) {
  const event = new ICAL.Event(vevent);
  const id = /^basix-event-(\d+)/.exec(event.uid)?.[1];
  const add = (start) => {
    const key = start.isDate ? start.toString() : start.toJSDate().toISOString().replace('.000Z', 'Z');
    if (inWindow(key.slice(0, 10))) fromIcs.add(`${id}|${key.length === 10 ? key : key.replace(/Z$/, '')}`);
  };
  if (event.isRecurring()) {
    const it = event.iterator();
    for (let next = it.next(), n = 0; next && n < 5000; next = it.next(), n++) {
      if (day(next.toJSDate()) > to && next.toString() > `${to}T23:59:59`) break;
      add(next);
    }
  } else {
    add(event.startDate);
  }
}

// What the site says for the same days.
const rest = await (await fetch(`${url}/wp-json/basix/v1/events?from=${from}&to=${to}`)).json();
const fromSite = new Set();
for (const item of rest) {
  const id = String(item.id).split('-')[0];
  const key = item.allDay ? item.start.slice(0, 10) : item.startUtc.replace(/Z$/, '');
  if (inWindow(key.slice(0, 10))) fromSite.add(`${id}|${key}`);
}
const missing = [...fromSite].filter((k) => !fromIcs.has(k));
const extra = [...fromIcs].filter((k) => !fromSite.has(k));
check(fromSite.size > 0, `the site lists events in the next weeks (${fromSite.size} dates)`);
check(missing.length === 0, `every date the site lists is in the feed${missing.length ? `: missing ${missing.slice(0, 5)}` : ''}`);
check(extra.length === 0, `the feed has no other dates${extra.length ? `: extra ${extra.slice(0, 5)}` : ''}`);

// One event as a download.
const one = rest.find((item) => item.link);
const file = await fetch(one.link.split('?')[0] + '?ics=1');
const fileText = await file.text();
check(file.status === 200 && (file.headers.get('content-disposition') ?? '').startsWith('attachment'), 'an event page gives its own calendar file as a download');
check(new ICAL.Component(ICAL.parse(fileText)).getAllSubcomponents('vevent').length >= 1, 'and that file parses');

if (failures.length) {
  console.log(`\nFAILED:\n${failures.join('\n')}`);
  process.exit(1);
}
console.log('\nICS check passed.');
