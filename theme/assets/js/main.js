import { Theme } from '../vendor/basix/js/theme.js';

Theme.init();

document.querySelector('.site-nav-toggle')?.addEventListener('click', (e) => {
    const nav = document.getElementById('site-nav');
    const open = nav.classList.toggle('is-open');
    e.currentTarget.setAttribute('aria-expanded', String(open));
});
