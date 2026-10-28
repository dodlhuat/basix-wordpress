import { Theme } from '../vendor/basix/js/theme.js';
import { FlyoutMenu } from '../vendor/basix/js/flyout-menu.js';
import { Tooltip } from '../vendor/basix/js/tooltip.js';
import { Scroll } from '../vendor/basix/js/scroll.js';

Theme.init();
Tooltip.initializeAll();

if (document.querySelector('#site-flyout li')) {
    new FlyoutMenu({
        triggerSelector: '.site-menu-trigger',
        menuSelector: '#site-flyout',
        overlaySelector: '#site-flyout-overlay',
        direction: 'right',
        title: document.title.split(' – ')[0],
        enableHeader: true,
        enableFooter: false,
    });
    const drawer = document.getElementById('site-flyout');
    requestAnimationFrame(() => requestAnimationFrame(() => drawer.classList.add('is-ready')));
} else {
    document.querySelector('.site-menu-trigger')?.remove();
}

document.querySelector('.back-to-top')?.addEventListener('click', () => Scroll.to(document.body));

document.querySelectorAll('a[href^="#"]').forEach((a) => {
    const target = a.getAttribute('href').length > 1 && document.querySelector(a.getAttribute('href'));
    if (target) {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            Scroll.to(target);
        });
    }
});
