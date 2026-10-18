(() => {
    const header = document.querySelector('[data-header]');
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.getElementById('hlavni-menu');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(open));
            nav.classList.toggle('is-open', open);
        });
        nav.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                toggle.setAttribute('aria-expanded', 'false');
                nav.classList.remove('is-open');
            }
        });
    }

    const stickyCta = document.querySelector('[data-sticky-cta]');
    const onScroll = () => {
        header?.classList.toggle('is-scrolled', window.scrollY > 8);
        stickyCta?.classList.toggle('is-visible', window.scrollY > 500);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
