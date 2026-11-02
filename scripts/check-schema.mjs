// Checks the structured data and social tags of the local site: the JSON-LD parses and has the fields search engines
// need, and the dates of events match what the events endpoint says. Needs the local site running (make up).
//   node scripts/check-schema.mjs
// Env: WP_URL (default http://localhost:8090).
const url = process.env.WP_URL ?? 'http://localhost:8090';
const failures = [];
const check = (ok, what) => {
  console.log(`${ok ? 'ok  ' : 'FAIL'} ${what}`);
  if (!ok) failures.push(what);
};

const load = async (path) => {
  const html = await (await fetch(`${url}${path}`)).text();
  const blocks = [...html.matchAll(/<script type="application\/ld\+json">(.*?)<\/script>/gs)];
  let nodes = [];
  let valid = true;
  for (const block of blocks) {
    try {
      const data = JSON.parse(block[1]);
      valid &&= data['@context'] === 'https://schema.org' && Array.isArray(data['@graph']);
      nodes = nodes.concat(data['@graph']);
    } catch {
      valid = false;
    }
  }
  const meta = {};
  for (const m of html.matchAll(/<meta (?:property|name)="((?:og|article|twitter):[^"]+)" content="([^"]*)">/g)) {
    (meta[m[1]] ??= []).push(m[2]);
  }
  return { html, nodes, meta, valid, count: blocks.length };
};
const ofType = (nodes, type) => nodes.filter((n) => n['@type'] === type);
const iso = (value) => !Number.isNaN(Date.parse(value));

// Front page: organisation and website with a search action.
let page = await load('/');
check(page.valid && page.count === 1, 'the front page has one valid JSON-LD block');
check(ofType(page.nodes, 'Organization').length === 1 && ofType(page.nodes, 'WebSite').length === 1, 'the front page names the organisation and the website');
const search = ofType(page.nodes, 'WebSite')[0]?.potentialAction;
check(search?.['@type'] === 'SearchAction' && search.target?.urlTemplate?.includes('{search_term_string}'), 'the website has a search action');
check(ofType(page.nodes, 'BreadcrumbList').length === 0, 'the front page has no breadcrumbs');

// A post: article, breadcrumbs, social tags.
page = await load('/everything-in-one-post/');
const article = ofType(page.nodes, 'Article')[0];
check(page.valid && Boolean(article), 'a post has an Article');
check(article && article.headline && iso(article.datePublished) && iso(article.dateModified) && article.author?.name && article.author?.url, 'the Article has headline, dates and author');
check(article?.publisher?.['@id'] && page.nodes.some((n) => n['@id'] === article.publisher['@id'] && n['@type'] === 'Organization'), 'its publisher is the organisation in the same graph');
check(Boolean(article?.image?.url) && article.image.width > 0, 'it has a picture with a size');
const crumbs = ofType(page.nodes, 'BreadcrumbList')[0]?.itemListElement ?? [];
check(crumbs.length >= 3 && crumbs.every((c, i) => c.position === i + 1 && c.name), 'the breadcrumbs are numbered in order');
check(crumbs.slice(0, -1).every((c) => /^https?:\/\//.test(c.item ?? '')) && !crumbs.at(-1)?.item, 'every step but the last has an address');
check(page.meta['og:type']?.[0] === 'article' && page.meta['og:title']?.[0] && page.meta['og:image']?.[0] && page.meta['article:published_time']?.[0], 'a post has Open Graph tags (type, title, picture, date)');
check(page.meta['twitter:card']?.[0] === 'summary_large_image', 'and a large Twitter card');

// A category page: breadcrumbs and no article.
page = await load('/category/design/');
check(ofType(page.nodes, 'BreadcrumbList').length === 1 && ofType(page.nodes, 'Article').length === 0, 'a category page has breadcrumbs and no Article');

// A page that does not exist has no breadcrumbs and still a valid block.
page = await load('/nope/');
check(page.valid && ofType(page.nodes, 'BreadcrumbList').length === 0, 'the 404 page has no breadcrumbs');

// Events: every date in the JSON-LD matches what the events endpoint gives for the same address.
const from = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
const to = new Date(Date.now() + 90 * 86400000).toISOString().slice(0, 10);
const rest = await (await fetch(`${url}/wp-json/basix/v1/events?from=${from}&to=${to}`)).json();
const byLink = new Map(rest.map((item) => [item.link, item]));
const paths = [...new Set(rest.map((item) => new URL(item.link).pathname))];
let compared = 0;
let wrong = [];
let sawRepeat = false;
let sawAllDay = false;
let sawZone = false;
for (const path of paths) {
  page = await load(path);
  const events = ofType(page.nodes, 'Event');
  if (!page.valid || events.length === 0) {
    wrong.push(`${path} has no valid Event`);
    continue;
  }
  for (const event of events) {
    const item = byLink.get(event.url);
    if (!item) continue; // a date outside the window we asked for
    compared++;
    const startOk = item.allDay ? event.startDate === item.start.slice(0, 10) : new Date(event.startDate).toISOString().replace('.000Z', 'Z') === item.startUtc;
    const endOk = item.allDay ? event.endDate === item.end.slice(0, 10) : new Date(event.endDate).toISOString().replace('.000Z', 'Z') === item.endUtc;
    if (!startOk || !endOk) wrong.push(`${event.url}: ${event.startDate} - ${event.endDate} vs ${item.startUtc ?? item.start}`);
    sawRepeat ||= item.repeats;
    sawAllDay ||= item.allDay;
    sawZone ||= !item.allDay && !/\+0?1:00$|\+0?2:00$/.test(event.startDate);
  }
  const first = events[0];
  const wantsPlace = rest.some((item) => new URL(item.link).pathname === path && item.location);
  if (!first.name || (wantsPlace && !first.location) || first.eventStatus !== 'https://schema.org/EventScheduled') wrong.push(`${path}: missing name, location or status`);
}
check(compared > 0, `event dates were compared (${compared} dates on ${paths.length} pages)`);
check(wrong.length === 0, `every Event date matches the events endpoint${wrong.length ? `: ${wrong.slice(0, 3).join('; ')}` : ''}`);
check(sawRepeat && sawAllDay && sawZone, 'the comparison covered a repeating, an all-day and a timed event with another time zone offset');

// A repeating event lists its coming dates as separate events.
const repeating = rest.find((item) => item.repeats);
if (repeating) {
  page = await load(new URL(repeating.link).pathname);
  const events = ofType(page.nodes, 'Event');
  check(events.length > 1 && new Set(events.map((e) => e.url)).size === events.length, 'a repeating event lists its coming dates as separate events');
}

if (failures.length) {
  console.log(`\nFAILED:\n${failures.join('\n')}`);
  process.exit(1);
}
console.log('\nSchema check passed.');
