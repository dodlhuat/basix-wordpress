import { Theme } from '../vendor/basix/js/theme.js';
import { loadComponents } from './loader.js';
import { components } from './components.js';

// Dark/light mode is needed on every page, so it is the only Basix module loaded up front.
Theme.init();
loadComponents(components);

// Without menu items there is no drawer to open.
if (!document.querySelector('#site-flyout li')) {
    document.querySelector('.site-menu-trigger')?.remove();
}

// Smooth scrolling is only needed on click, so it is imported then.
const scrollTo = async (target) => (await import('../vendor/basix/js/scroll.js')).Scroll.to(target);

document.querySelector('.back-to-top')?.addEventListener('click', () => scrollTo(document.body));

document.querySelectorAll('a[href^="#"]').forEach((a) => {
    const target = a.getAttribute('href').length > 1 && document.querySelector(a.getAttribute('href'));
    if (target) {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            scrollTo(target);
        });
    }
});
