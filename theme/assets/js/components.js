import { iconBasePath, readConfig } from './loader.js';
import { makeSortable } from './sortable-table.js';

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
        selector: '.entry-content .wp-block-image img, .entry-content .wp-block-gallery img, .entry-media img',
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
