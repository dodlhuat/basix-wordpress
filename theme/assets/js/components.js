import { iconBasePath } from './loader.js';

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
