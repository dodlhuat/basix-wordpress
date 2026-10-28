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
