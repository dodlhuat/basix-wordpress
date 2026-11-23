/**
 * Keeps keyboard focus inside an open overlay (search, filters, the mobile menu): Tab on the last control goes to
 * the first and Shift+Tab on the first goes to the last. Releasing the trap gives focus back to what opened it.
 *
 * Only focus that is inside the overlay (or nowhere) is steered, so a picker that a component puts at the end of
 * the page, such as the date picker's calendar, keeps working.
 */

const FOCUSABLE = [
    'a[href]',
    'area[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'iframe',
    'summary',
    '[contenteditable="true"]',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

/** The controls in a container (or a list of them, in that order) that can take focus right now. */
export function focusables(container) {
    const parts = Array.isArray(container) ? container : [container];
    return parts.flatMap((part) => (part.matches(FOCUSABLE) ? [part] : [...part.querySelectorAll(FOCUSABLE)])).filter((element) => {
        if (element.closest('[inert], [hidden]') || element.getClientRects().length === 0) {
            return false;
        }
        const style = getComputedStyle(element);
        return style.visibility !== 'hidden' && style.display !== 'none';
    });
}

/**
 * Traps focus in a container until release() is called.
 *
 * @param {HTMLElement|HTMLElement[]} container The overlay, or the parts it is made of in tab order (such as a menu
 *   button and the menu it opens).
 * @param {{ initial?: HTMLElement|null, restore?: HTMLElement|null }} options What to focus first (default: the
 *   first control), and what gets focus back afterwards (default: whatever had it when the trap was set).
 * @returns {{ release: (restoreFocus?: boolean) => void }}
 */
export function trapFocus(container, { initial = null, restore = document.activeElement } = {}) {
    const parts = Array.isArray(container) ? container : [container];
    const inside = (element) => parts.some((part) => part.contains(element));
    const onKeydown = (event) => {
        if (event.key !== 'Tab' || !parts[0].isConnected) {
            return;
        }
        const active = document.activeElement;
        if (active && active !== document.body && !inside(active)) {
            return;
        }
        const items = focusables(parts);
        if (!items.length) {
            event.preventDefault();
            return;
        }
        const first = items[0];
        const last = items[items.length - 1];
        const index = items.indexOf(active);
        if (index === -1) {
            event.preventDefault();
            (event.shiftKey ? last : first).focus();
        } else if (parts.length > 1) {
            // Parts may sit apart in the page, so every step is taken here, in the parts' order.
            event.preventDefault();
            items[(index + (event.shiftKey ? -1 : 1) + items.length) % items.length].focus();
        } else if (event.shiftKey && active === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        }
    };
    document.addEventListener('keydown', onKeydown, true);

    const target = initial ?? focusables(container)[0];
    target?.focus({ preventScroll: true });

    let released = false;
    return {
        release(restoreFocus = true) {
            if (released) {
                return;
            }
            released = true;
            document.removeEventListener('keydown', onKeydown, true);
            if (restoreFocus && restore instanceof HTMLElement && restore.isConnected) {
                restore.focus({ preventScroll: true });
            }
        },
    };
}

/**
 * Turns the close icon of a Basix modal or bottom sheet (an SVG that only reacts to clicks) into a control that
 * can be reached and used with the keyboard.
 */
export function makeCloseReachable(wrapper, label) {
    const close = wrapper?.querySelector('.close');
    if (!close || close.hasAttribute('tabindex')) {
        return;
    }
    close.removeAttribute('aria-hidden');
    close.setAttribute('role', 'button');
    close.setAttribute('tabindex', '0');
    close.setAttribute('aria-label', label);
    close.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            close.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        }
    });
}

/**
 * Traps focus in every lightbox and bottom sheet a Basix component adds to the page (the share sheet, the lightbox of
 * posts and galleries), and gives focus back to what opened it when it is gone. Overlays that set up their own trap
 * mark their wrapper with data-focus-trap and are left alone.
 *
 * @param {string} closeLabel Name for a close icon that is not a button yet.
 */
export function trapOverlays(closeLabel) {
    const selector = '.lightbox-wrapper, .bottom-sheet-wrapper, .modal-wrapper';
    new MutationObserver((records) => {
        for (const record of records) {
            for (const node of record.addedNodes) {
                if (!(node instanceof HTMLElement) || !node.matches(selector) || node.dataset.focusTrap) {
                    continue;
                }
                node.dataset.focusTrap = 'auto';
                const dialog = node.querySelector('.bottom-sheet, .modal') ?? node;
                dialog.setAttribute('role', 'dialog');
                dialog.setAttribute('aria-modal', 'true');
                makeCloseReachable(node, closeLabel);
                const trap = trapFocus(dialog);
                whenRemoved(node, () => trap.release());
            }
        }
    }).observe(document.body, { childList: true });
}

/** Calls back once an element has been taken out of the page. */
export function whenRemoved(element, callback) {
    if (!element) {
        return;
    }
    new MutationObserver((records, observer) => {
        if (!element.isConnected) {
            observer.disconnect();
            callback();
        }
    }).observe(document.body, { childList: true });
}
