import { iconBasePath, readConfig } from './loader.js';
import { makeSortable } from './sortable-table.js';
import { buildFilterForm } from './filters.js';
import { bindLightbox, neverResolves } from './masonry.js';

/**
 * Components the theme can load. Add an entry here when a template or block uses a new one;
 * `npm run sync` then copies the Basix module and the icons it needs into the theme.
 */
export const components = {
    tooltip: {
        selector: '[data-tooltip]',
        load: () => import('../vendor/basix/js/tooltip.js'),
        init: ({ Tooltip }) => Tooltip.initializeAll(),
    },

    // Desktop submenus: one Basix Dropdown per top-level item that has children.
    dropdown: {
        selector: '.site-nav .dropdown-container',
        load: () => import('../vendor/basix/js/dropdown.js'),
        init: ({ Dropdown }, elements) => elements.forEach((el) => new Dropdown(`#${CSS.escape(el.id)}`)),
    },

    // Drill-down side menu (header layout "push").
    push: {
        selector: '.push-menu',
        load: () => import('../vendor/basix/js/push-menu.js'),
        init: ({ PushMenu }) => PushMenu.init({ iconBasePath }),
    },

    // Search overlay: the header button (a plain link to the search page without JavaScript) or the "/" key.
    search: {
        selector: '.site-search-trigger',
        load: () => import('../vendor/basix/js/modal.js'),
        init: ({ Modal }, triggers) => {
            const template = document.getElementById('basix-search-template');
            if (!template) {
                return;
            }
            const open = (event) => {
                event?.preventDefault();
                const opener = document.activeElement;
                new Modal({ content: template.innerHTML, iconBasePath }).show();

                const wrapper = document.querySelector('.modal-wrapper');
                const dialog = wrapper?.querySelector('.modal');
                dialog?.setAttribute('role', 'dialog');
                dialog?.setAttribute('aria-modal', 'true');
                dialog?.setAttribute('aria-label', template.dataset.title ?? 'Search');
                wrapper?.querySelector('input[type="search"]')?.focus();

                // Give focus back to whatever opened the overlay once it is gone.
                new MutationObserver((records, observer) => {
                    if (!document.body.contains(wrapper)) {
                        observer.disconnect();
                        opener?.focus?.();
                    }
                }).observe(document.body, { childList: true });
            };

            triggers.forEach((trigger) => trigger.addEventListener('click', open));
            document.addEventListener('keydown', (event) => {
                const target = event.target;
                const typing = target instanceof HTMLElement && (target.isContentEditable || /^(input|textarea|select)$/i.test(target.tagName));
                if (event.key === '/' && !typing && !event.ctrlKey && !event.metaKey && !event.altKey && !document.querySelector('.modal-wrapper')) {
                    open(event);
                }
            });
        },
    },

    // Documentation template: the Basix sidebar nav gives the mobile drawer, backdrop and swipe.
    docs: {
        load: () => import('../vendor/basix/js/sidebar-nav.js'),
        init: ({ SidebarNav }, [layout]) => new SidebarNav(layout, { iconBasePath }),
    },

    // Reading progress bar at the top of a post: CSS only (Basix progress), this just moves the bar.
    'reading-progress': {
        load: async () => ({}),
        init: (modules, [element]) => {
            const bar = element.querySelector('.progress-bar');
            const article = document.querySelector('.entry-content');
            if (!bar || !article) {
                return;
            }
            let frame = 0;
            const update = () => {
                frame = 0;
                const rect = article.getBoundingClientRect();
                const line = window.innerHeight * 0.4; // the point of the window we treat as "where you are reading"
                const atEnd = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2;
                const progress = atEnd ? 1 : Math.min(1, Math.max(0, (line - rect.top) / rect.height));
                bar.style.width = `${(progress * 100).toFixed(1)}%`;
            };
            const schedule = () => {
                if (!frame) {
                    frame = requestAnimationFrame(update);
                }
            };
            window.addEventListener('scroll', schedule, { passive: true });
            window.addEventListener('resize', schedule);
            update();
        },
    },

    // Share: a button with a popover on wide screens, a bottom sheet on small ones; the toast confirms "copy link".
    share: {
        load: async () => Object.assign({}, ...(await Promise.all([
            import('../vendor/basix/js/popover.js'),
            import('../vendor/basix/js/bottom-sheet.js'),
            import('../vendor/basix/js/toast.js'),
        ]))),
        init: ({ Popover, BottomSheet, Toast }, sections) => sections.forEach((section) => {
            const config = readConfig(section);
            const list = section.querySelector('.share-links');
            if (!list) {
                return;
            }
            const escape = (text) => text.replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);
            const entries = [...list.querySelectorAll('a')].map((a) => `<li><a href="${escape(a.href)}" target="_blank" rel="noopener noreferrer">${escape(a.textContent)}</a></li>`).join('');
            const menu = `<ul class="share-menu"><li><button type="button" data-share-copy>${escape(config.copy ?? 'Copy link')}</button></li>${entries}</ul>`;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'button share-button';
            button.innerHTML = `<svg class="icon-svg" aria-hidden="true"><use href="${iconBasePath}icons.svg#share"/></svg><span>${escape(config.share ?? 'Share')}</span>`;
            list.replaceWith(button);

            let popover = null;
            let sheet = null;
            const narrow = () => window.matchMedia('(max-width: 768px)').matches;

            button.addEventListener('click', () => {
                if (narrow()) {
                    popover?.close();
                    sheet = new BottomSheet({ content: menu, header: escape(config.share ?? 'Share'), iconBasePath });
                    sheet.show();
                } else if (!popover) {
                    popover = new Popover(button, { content: menu, placement: 'top', align: 'start' });
                    popover.open();
                }
            });

            document.addEventListener('click', async (event) => {
                if (!(event.target instanceof Element) || !event.target.closest('[data-share-copy]')) {
                    return;
                }
                try {
                    await navigator.clipboard.writeText(config.url);
                } catch {
                    const field = Object.assign(document.createElement('textarea'), { value: config.url });
                    document.body.append(field);
                    field.select();
                    document.execCommand('copy');
                    field.remove();
                }
                popover?.close();
                sheet?.hide();
                new Toast({ content: config.copied ?? 'Link copied', type: 'success', iconBasePath }).show(2500);
            });
        }),
    },

    // Click an image in a post to see it full size, with next and previous.
    lightbox: {
        selector: '.entry-content .wp-block-image img, .entry-content .wp-block-gallery:not(.is-style-masonry) img, .entry-media img',
        load: () => import('../vendor/basix/js/lightbox.js'),
        init: ({ Lightbox }, images) => {
            images.forEach((image) => {
                const link = image.closest('a');
                if (link && !/\.(jpe?g|png|gif|webp|avif)(\?.*)?$/i.test(link.href)) {
                    return; // the image links somewhere else, leave it alone
                }
                const target = link ?? image;
                target.dataset.lightbox = 'post';
                const caption = image.closest('figure')?.querySelector('figcaption')?.textContent.trim();
                if (caption) {
                    target.dataset.lightboxCaption = caption;
                }
            });
            Lightbox.bind('[data-lightbox="post"]', iconBasePath);
        },
    },

    // Code blocks get a language label and a copy button; JavaScript, HTML and CSS are also highlighted.
    // Set the language with an additional CSS class such as "language-css".
    code: {
        selector: '.entry-content pre.wp-block-code',
        load: () => import('../vendor/basix/js/code-viewer.js'),
        init: ({ CodeViewer }, blocks) => blocks.forEach((pre) => {
            const language = pre.className.match(/language-([a-z0-9+#-]+)/i)?.[1] ?? 'code';
            const holder = document.createElement('div');
            holder.className = 'code-block';
            pre.replaceWith(holder);
            new CodeViewer(holder, pre.textContent.replace(/\n$/, ''), language);
        }),
    },

    // Tables with the "Sortable" block style.
    'sortable-table': {
        selector: '.wp-block-table.is-style-sortable',
        load: async () => ({}),
        init: (modules, figures) => figures.forEach((figure) => {
            const table = figure.querySelector('table');
            if (table) {
                makeSortable(table);
            }
        }),
    },

    // Featured posts on the front page.
    featured: {
        load: () => import('../vendor/basix/js/carousel.js'),
        init: ({ Carousel }, [element]) => new Carousel(element, { ...readConfig(element), iconBasePath }),
    },

    // Filter button: the form opens in a modal (a bottom sheet on phones). The Basix pickers are only loaded on the first click.
    filters: {
        load: async () => ({}),
        init: (modules, [details]) => {
            const config = readConfig(details);
            const summary = details.querySelector('summary');
            const escape = (value) => String(value).replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);

            summary?.addEventListener('click', async (event) => {
                event.preventDefault(); // the <details> stays closed; this is its no-JavaScript fallback
                const [{ Modal }, { BottomSheet }, { GroupPicker }, { DatePicker }] = await Promise.all([
                    import('../vendor/basix/js/modal.js'),
                    import('../vendor/basix/js/bottom-sheet.js'),
                    import('../vendor/basix/js/group-picker.js'),
                    import('../vendor/basix/js/datepicker.js'),
                ]);
                const options = { content: '<div class="filter-host"></div>', header: escape(config.text.title), iconBasePath };
                if (window.matchMedia('(max-width: 768px)').matches) {
                    new BottomSheet({ ...options, snapHeight: 'full' }).show();
                } else {
                    new Modal(options).show();
                }
                document.querySelector('.filter-host')?.append(buildFilterForm(details, config, { GroupPicker, DatePicker, iconBasePath }));
            });
        },
    },

    // Calendar page: posts by publish date. The posts of the visible range are fetched from the REST API.
    calendar: {
        load: () => import('../vendor/basix/js/calendar.js'),
        init: async ({ Calendar }, [wrap]) => {
            const config = readConfig(wrap);
            const host = wrap.querySelector('.calendar-host');
            const status = wrap.querySelector('.calendar-status');
            const links = new Map();
            const pad = (n) => String(n).padStart(2, '0');
            const stamp = (date, end) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${end ? '23:59:59' : '00:00:00'}`;
            const decode = (html) => Object.assign(document.createElement('textarea'), { innerHTML: html }).value;

            // The REST API returns at most 100 posts a page, so keep asking until every page is read.
            const fetchPosts = async ({ from, to }) => {
                const posts = [];
                for (let page = 1, pages = 1; page <= pages; page++) {
                    const query = new URLSearchParams({ after: stamp(from), before: stamp(to, true), per_page: '100', page: String(page), orderby: 'date', order: 'asc', _fields: 'id,date,link,title,categories' });
                    const response = await fetch(`${config.restUrl}${config.restUrl.includes('?') ? '&' : '?'}${query}`);
                    if (!response.ok) {
                        throw new Error(`REST request failed (${response.status})`);
                    }
                    pages = parseInt(response.headers.get('X-WP-TotalPages') ?? '1', 10);
                    posts.push(...(await response.json()));
                }
                return posts.map((post) => {
                    links.set(String(post.id), post.link);
                    const date = new Date(post.date);
                    return { id: String(post.id), title: decode(post.title.rendered), start: date, end: date, allDay: true, badge: config.categories[post.categories?.[0]] };
                });
            };

            let latest = 0;
            const load = async (range) => {
                const request = ++latest;
                wrap.classList.add('is-loading');
                wrap.setAttribute('aria-busy', 'true');
                status.hidden = false;
                try {
                    const events = await fetchPosts(range);
                    if (request === latest) {
                        calendar.setEvents(events);
                    }
                } catch (error) {
                    console.error(error);
                    host.dataset.error = config.text.error;
                } finally {
                    if (request === latest) {
                        wrap.classList.remove('is-loading');
                        wrap.removeAttribute('aria-busy');
                        status.hidden = true;
                    }
                }
            };

            const calendar = new Calendar({
                container: host,
                view: 'month',
                iconBasePath,
                dateLocale: config.dateLocale,
                hour12: config.hour12,
                locale: {
                    monthNames: config.months,
                    dayNamesFull: config.daysFull,
                    dayNamesShort: config.daysShort,
                    firstDayOfWeek: config.firstDay,
                    ...config.text,
                },
                onChange: (date, view, range) => load(range),
                onEventClick: (event) => {
                    location.href = links.get(event.id) ?? '#';
                },
                onDayClick: (date) => {
                    location.href = config.dayUrl.replace('{y}', date.getFullYear()).replace('{m}', pad(date.getMonth() + 1)).replace('{d}', pad(date.getDate()));
                },
            });
            wrap.querySelector('.calendar-skeleton')?.remove();
            await load(calendar.getVisibleRange());
        },
    },

    // Photo gallery page: masonry with endless scrolling, fed from the media REST endpoint.
    'media-gallery': {
        load: async () => Object.assign({}, ...(await Promise.all([import('../vendor/basix/js/gallery.js'), import('../vendor/basix/js/lightbox.js')]))),
        init: ({ MasonryGallery, Lightbox }, [host]) => {
            const config = readConfig(host);
            const loader = document.querySelector('.gallery-wrap .loader');
            const images = [];
            const plain = (html) => Object.assign(document.createElement('div'), { innerHTML: html ?? '' }).textContent.trim();
            let page = 0;
            let pages = 1;

            const finish = (message) => {
                loader?.classList.add('hidden');
                if (message) {
                    host.insertAdjacentHTML('beforebegin', `<div class="alert alert-error" role="alert"></div>`);
                    host.previousElementSibling.textContent = message;
                }
                return neverResolves();
            };

            const fetchBatch = async () => {
                if (page >= pages) {
                    return finish();
                }
                try {
                    const query = new URLSearchParams({ media_type: 'image', per_page: String(config.perPage), page: String(++page), orderby: 'date', order: 'desc', _fields: 'id,source_url,title,caption,alt_text,media_details' });
                    if (config.parent) {
                        query.set('parent', String(config.parent));
                    }
                    const response = await fetch(`${config.restUrl}${config.restUrl.includes('?') ? '&' : '?'}${query}`);
                    if (!response.ok) {
                        throw new Error(`REST request failed (${response.status})`);
                    }
                    pages = parseInt(response.headers.get('X-WP-TotalPages') ?? '1', 10);
                    const batch = (await response.json()).map((item) => ({
                        src: item.media_details?.sizes?.large?.source_url ?? item.source_url,
                        full: item.source_url,
                        title: plain(item.title?.rendered),
                        desc: plain(item.caption?.rendered) || item.alt_text || '',
                    }));
                    images.push(...batch);
                    return batch;
                } catch (error) {
                    console.error(error);
                    return finish(config.error);
                }
            };

            new MasonryGallery(host.id, { fetchFunction: fetchBatch, loaderSelector: '.gallery-wrap .loader', minColumnWidth: 250, reload: 0, enableLightbox: false });
            bindLightbox(host, images, Lightbox, iconBasePath);
        },
    },

    // The "Masonry" style of the Gallery block: the same component, with the images already in the page.
    'masonry-block': {
        selector: '.wp-block-gallery.is-style-masonry',
        load: async () => Object.assign({}, ...(await Promise.all([import('../vendor/basix/js/gallery.js'), import('../vendor/basix/js/lightbox.js')]))),
        init: ({ MasonryGallery, Lightbox }, blocks) => blocks.forEach((block, index) => {
            const images = [...block.querySelectorAll('figure.wp-block-image')].map((figure) => {
                const img = figure.querySelector('img');
                const link = figure.querySelector('a[href]');
                return {
                    src: img?.currentSrc || img?.src || '',
                    full: link && /\.(jpe?g|png|gif|webp|avif)(\?.*)?$/i.test(link.href) ? link.href : undefined,
                    title: figure.querySelector('figcaption')?.textContent.trim() ?? '',
                    desc: '',
                };
            }).filter((image) => image.src);
            if (!images.length) {
                return;
            }
            const host = document.createElement('div');
            host.id = `masonry-block-${index}`;
            host.className = 'gallery-host';
            block.replaceChildren(host);

            let delivered = false;
            new MasonryGallery(host.id, {
                fetchFunction: () => {
                    if (delivered) {
                        return neverResolves();
                    }
                    delivered = true;
                    return Promise.resolve(images);
                },
                loaderSelector: '.not-present',
                minColumnWidth: 220,
                reload: 0,
                enableLightbox: false,
            });
            bindLightbox(host, images, Lightbox, iconBasePath);
        }),
    },

    flyout: {
        selector: '#site-flyout li',
        load: () => import('../vendor/basix/js/flyout-menu.js'),
        init: ({ FlyoutMenu }) => {
            new FlyoutMenu({
                triggerSelector: '.site-menu-trigger',
                menuSelector: '#site-flyout',
                overlaySelector: '#site-flyout-overlay',
                direction: 'right',
                title: document.title.split(' – ')[0],
                enableHeader: true,
                enableFooter: false,
            });
            // Hidden and static until now so the drawer doesn't slide in on page load.
            const drawer = document.getElementById('site-flyout');
            requestAnimationFrame(() => requestAnimationFrame(() => drawer.classList.add('is-ready')));
        },
    },

    commentForm: {
        selector: '#commentform',
        load: () => import('./form-validation.js'),
        init: () => {},
    },
};
