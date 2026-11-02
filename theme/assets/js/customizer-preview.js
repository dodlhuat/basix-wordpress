/* Live preview in the Customizer: the settings that can change without reloading the preview. */
(function (api) {
    'use strict';
    var data = window.basixPreview || { year: '', site: '' };
    var $ = function (selector) { return Array.prototype.slice.call(document.querySelectorAll(selector)); };

    // The header button: its text and address both have to be there, as on the real site.
    var cta = { label: '', url: '' };
    var showCta = function () {
        var on = cta.label !== '' && cta.url !== '';
        $('.header-cta').forEach(function (a) { a.textContent = cta.label; a.setAttribute('href', cta.url); a.hidden = !on; });
        $('.menu-cta').forEach(function (p) { p.hidden = !on; var a = p.querySelector('a'); if (a) { a.textContent = cta.label; a.setAttribute('href', cta.url); } });
    };
    api('basix_header_cta_label', function (value) { cta.label = value.get(); value.bind(function (v) { cta.label = String(v).trim(); showCta(); }); });
    api('basix_header_cta_url', function (value) { cta.url = value.get(); value.bind(function (v) { cta.url = String(v).trim(); showCta(); }); });

    // The top bar message.
    api('basix_topbar_text', function (value) {
        value.bind(function (v) {
            $('.top-bar-text').forEach(function (p) { p.textContent = v; p.hidden = String(v).trim() === ''; });
        });
    });

    // A header that stays at the top, or scrolls away.
    api('basix_header_sticky', function (value) {
        value.bind(function (on) {
            document.body.classList.toggle('header-sticky', !!on);
            document.body.classList.toggle('header-static', !on);
        });
    });

    // The accent colours: worked out here the same way as on the server (accent-math.js), in a style element that
    // comes after the theme's own, so it wins.
    var accents = { light: '', dark: '' };
    var styleEl = null;
    var showAccents = function () {
        var css = '';
        ['light', 'dark'].forEach(function (mode) {
            if (/^#[0-9a-f]{6}$/i.test(accents[mode])) {
                css += window.basixAccent.css(accents[mode].toLowerCase(), mode, data.page && data.page[mode], data.surface && data.surface[mode]);
            }
        });
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'basix-accent-preview';
            document.head.appendChild(styleEl);
        }
        styleEl.textContent = css;
    };
    ['light', 'dark'].forEach(function (mode) {
        api('basix_accent_' + mode, function (value) {
            accents[mode] = String(value.get() || '');
            value.bind(function (v) { accents[mode] = String(v || ''); showAccents(); });
        });
    });

    // The footer line, with {year} and {site} filled in. It is shown as plain text here; links appear once the
    // preview is reloaded.
    api('basix_footer_text', function (value) {
        value.bind(function (v) {
            var text = String(v).trim() === '' ? '© {year} {site}' : String(v);
            text = text.replace(/\{year\}/g, data.year).replace(/\{site\}/g, data.site).replace(/<[^>]*>/g, '');
            $('.site-footer-text').forEach(function (p) { p.textContent = text; });
        });
    });
}(wp.customize));
