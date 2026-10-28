/**
 * Helpers shared by the two ways the Basix masonry gallery is used: the Photo gallery page (images from the
 * REST API) and the "Masonry" style of the Gallery block (images already in the page).
 */

/**
 * A promise that never settles. The masonry gallery asks its fetch function for more images as long as it
 * is on screen, and has no notion of "no more images", so after the last batch we simply never answer.
 */
export const neverResolves = () => new Promise(() => {});

/**
 * Opens the theme's lightbox when a masonry item is clicked. The gallery has a lightbox of its own, but it
 * cannot be told where the icons live, so it is turned off and this one is used instead.
 */
export function bindLightbox(host, images, Lightbox, iconBasePath) {
    host.addEventListener('click', (event) => {
        const item = event.target instanceof Element ? event.target.closest('.masonry-item') : null;
        if (!item || item.classList.contains('masonry-item--skeleton')) {
            return;
        }
        const shown = item.querySelector('img')?.getAttribute('src');
        const index = Math.max(0, images.findIndex((image) => image.src === shown));
        new Lightbox({
            images: images.map((image) => ({ src: image.full ?? image.src, alt: image.title, caption: image.desc })),
            startIndex: index,
            iconBasePath,
        }).show();
    });
}

/**
 * Makes gallery tiles usable without a mouse: focusable, named, opened with Enter or Space. The component
 * also uses an h3 for every title, which skips a heading level, so it becomes a plain paragraph.
 */
export function makeItemsAccessible(host, strings) {
    const fix = () => host.querySelectorAll('.masonry-item:not(.masonry-item--skeleton):not([data-accessible])').forEach((item) => {
        item.dataset.accessible = 'true';
        item.tabIndex = 0;
        item.setAttribute('role', 'button');
        // The name has to contain what the tile shows (its title and description), so use exactly that.
        const shown = [...item.querySelectorAll('.masonry-item-title, .masonry-item-desc')].map((e) => e.textContent.trim()).filter(Boolean).join(' ');
        item.setAttribute('aria-label', shown || item.querySelector('img')?.alt || strings.openImage || 'Open image');
        const heading = item.querySelector('h3.masonry-item-title');
        if (heading) {
            const paragraph = Object.assign(document.createElement('p'), { className: heading.className, textContent: heading.textContent });
            heading.replaceWith(paragraph);
        }
        item.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                item.click();
            }
        });
    });
    new MutationObserver(fix).observe(host, { childList: true, subtree: true });
    fix();
}
