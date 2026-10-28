/**
 * Loads Basix components only on pages that contain them.
 *
 * A registry entry says how to find a component on the page, how to import it and how to start it:
 *
 *   name: {
 *     selector: '[data-basix="name"]',  // optional, this is the default
 *     visible: true,                    // optional: wait until the first match nears the viewport
 *     load: () => import('../vendor/basix/js/name.js'),
 *     init: (module, elements) => { ... },
 *   }
 *
 * Markup written by PHP uses data-basix="name" and an optional JSON config in data-config
 * (see basix_component_attrs() in functions.php); read it with readConfig().
 */

/** Where Basix components find their icon sprite (they all accept an iconBasePath option). */
export const iconBasePath = new URL('../vendor/basix/svg-icons/', import.meta.url).href;

/** Parses the JSON in an element's data-config attribute; invalid or missing config gives {}. */
export function readConfig(element) {
    try {
        return JSON.parse(element.dataset.config ?? '{}');
    } catch {
        return {};
    }
}

function whenVisible(element) {
    return new Promise((resolve) => {
        if (!('IntersectionObserver' in window)) {
            resolve();
            return;
        }
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                resolve();
            }
        }, { rootMargin: '200px' });
        observer.observe(element);
    });
}

/** Starts every registered component that has at least one element on the page. */
export function loadComponents(registry) {
    return Promise.allSettled(Object.entries(registry).map(async ([name, entry]) => {
        const elements = [...document.querySelectorAll(entry.selector ?? `[data-basix="${name}"]`)];
        if (!elements.length) {
            return;
        }
        if (entry.visible) {
            await whenVisible(elements[0]);
        }
        try {
            entry.init(await entry.load(), elements);
        } catch (error) {
            console.error(`Basix component "${name}" failed to load`, error);
        }
    }));
}
