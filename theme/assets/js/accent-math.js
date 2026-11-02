/*
 * The colour maths behind the accent colour: which text colour is readable on it, and which accent text colour is
 * readable on the page and on tinted surfaces. It is the same as basix_readable_on() and basix_accent_text() in
 * inc/customizer.php, so the live preview in the Customizer shows what the site will show;
 * scripts/check-accent-math.mjs compares the two.
 */
(function (root, factory) {
    'use strict';
    var api = factory();
    root.basixAccent = api;
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }
}(typeof globalThis !== 'undefined' ? globalThis : self, function () {
    'use strict';

    function rgb(hex) {
        hex = String(hex).replace('#', '');
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        return [0, 2, 4].map(function (i) { return parseInt(hex.slice(i, i + 2), 16); });
    }

    function luminance(hex) {
        var c = rgb(hex).map(function (v) {
            v /= 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    }

    function contrast(a, b) {
        var la = luminance(a), lb = luminance(b);
        return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
    }

    function mix(a, b, weightA) {
        var ca = rgb(a), cb = rgb(b), hex = '#';
        for (var i = 0; i < 3; i++) {
            // Rounded in two steps, like basix_mix() in PHP, so a value that should be exactly .5 goes the same way.
            var v = ca[i] * weightA + cb[i] * (1 - weightA);
            v = Math.floor(Math.floor(v * 1000000 + 0.5) / 1000000 + 0.5);
            hex += (v < 16 ? '0' : '') + v.toString(16);
        }
        return hex;
    }

    function readableOn(hex) {
        return contrast(hex, '#ffffff') >= contrast(hex, '#1a1b1f') ? '#ffffff' : '#1a1b1f';
    }

    function accentText(accent, dark, page, surface) {
        page = page || (dark ? '#111113' : '#f9f9fb');
        surface = surface || (dark ? '#19191b' : '#e7e8ec');
        var surfaces = [page, surface, mix(accent, surface, 0.2), mix(accent, page, 0.12)];
        var towards = dark ? '#ffffff' : '#000000';
        for (var step = 0; step <= 20; step++) {
            var candidate = mix(accent, towards, 1 - step * 0.05);
            var lowest = Math.min.apply(null, surfaces.map(function (s) { return contrast(candidate, s); }));
            if (lowest >= 4.6) {
                return candidate;
            }
        }
        return towards;
    }

    /** The CSS that applies one accent colour in one mode, scoped like the server's (see basix_mode_vars()). */
    function css(accent, mode, page, surface) {
        var vars = '--accent-color:' + accent + ';--on-accent:' + readableOn(accent) + ';--accent-text:' + accentText(accent, mode === 'dark', page, surface) + ';';
        if (mode === 'dark') {
            return ':root[data-theme="dark"]{' + vars + '}@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){' + vars + '}}';
        }
        return ':root[data-theme="light"]{' + vars + '}@media (prefers-color-scheme: light){:root:not([data-theme="dark"]){' + vars + '}}';
    }

    return { contrast: contrast, mix: mix, readableOn: readableOn, accentText: accentText, css: css };
}));
