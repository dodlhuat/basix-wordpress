import { Theme } from '../vendor/basix/js/theme.js';
import { loadComponents } from './loader.js';
import { components } from './components.js';

// Dark/light mode is needed on every page, so it is the only Basix module loaded up front.
Theme.init();

// The Customizer can set a default colour mode. It applies until the visitor makes their own choice.
const defaultMode = document.documentElement.dataset.defaultTheme;
if (defaultMode && !Theme.hasSavedPreference()) {
    Theme.setTheme(defaultMode);
    try {
        localStorage.removeItem('theme');
    } catch {
        // Storage unavailable: the default simply isn't remembered.
    }
}

loadComponents(components);

// The header gets a shadow once the page scrolls underneath it.
const header = document.querySelector('.site-header');
if (header) {
    // Its height, for a transparent header that sits over the first section (see style.css).
    const setHeight = () => document.documentElement.style.setProperty('--basix-header-height', `${header.offsetHeight}px`);
    setHeight();
    if ('ResizeObserver' in window) {
        new ResizeObserver(setHeight).observe(header);
    }
    let frame = 0;
    const update = () => {
        frame = 0;
        header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', () => {
        if (!frame) {
            frame = requestAnimationFrame(update);
        }
    }, { passive: true });
    update();
}

// Without menu items there is no drawer to open.
if (!document.querySelector('#site-flyout li')) {
    document.querySelector('.site-menu-trigger')?.remove();
}

// Smooth scrolling is only needed on click, so it is imported then.
const scrollTo = async (target) => (await import('../vendor/basix/js/scroll.js')).Scroll.to(target);

document.querySelector('.back-to-top')?.addEventListener('click', () => scrollTo(document.body));

document.querySelectorAll('a[href^="#"]').forEach((a) => {
    // By id, not as a selector: a link like #3fgfhgfh (or any text after the #) must not raise an error.
    const id = a.getAttribute('href').slice(1);
    let target = null;
    if (id) {
        try {
            target = document.getElementById(decodeURIComponent(id));
        } catch {
            target = null; // a broken escape such as #%
        }
    }
    if (target) {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            scrollTo(target);
        });
    }
});

// A link around a picture without alt text has no name for a screen reader. Content should have alt text, but old
// posts and galleries often do not, so such a link is named after its caption, its title or the picture's file name.
document.querySelectorAll('.entry-content a, .entry-media a').forEach((link) => {
    const image = link.querySelector('img');
    if (!image || link.textContent.trim() || image.alt?.trim() || link.getAttribute('aria-label') || link.getAttribute('aria-labelledby') || link.title?.trim()) {
        return;
    }
    const caption = (link.closest('figure')?.querySelector('figcaption') ?? link.closest('.wp-caption')?.querySelector('.wp-caption-text'))?.textContent.trim();
    const file = decodeURIComponent((image.currentSrc || image.src).split('/').pop().split('?')[0].replace(/\.[a-z0-9]+$/i, '')).replace(/[-_]+/g, ' ').trim();
    const label = caption || image.title?.trim() || file;
    if (label) {
        link.setAttribute('aria-label', label);
    }
});
