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
