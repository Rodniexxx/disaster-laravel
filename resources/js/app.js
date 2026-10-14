import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });

        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('open');
            });
        });
    }

    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const field = button.closest('.password-field');
            const input = field ? field.querySelector('input') : null;
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Hide' : 'Show';
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            input.focus();
        });
    });

    document.querySelectorAll('[data-submit-state]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;

            const loadingText = button.getAttribute('data-loading-text') || 'Loading...';
            const isAlreadyLoading = button.dataset.loading === 'true';

            if (isAlreadyLoading) {
                return;
            }

            button.dataset.loading = 'true';
            button.disabled = true;
            const label = button.querySelector('span');
            if (label) {
                label.textContent = loadingText;
            }
        });
    });

    const hero = document.querySelector('.hero');
    if (!hero) return;

    const spotlight = hero.querySelector('.hero-reveal');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const setSpotlightPosition = function (x, y) {
        if (!spotlight) return;

        const rect = hero.getBoundingClientRect();
        const localX = Math.min(Math.max(x - rect.left, 0), rect.width);
        const localY = Math.min(Math.max(y - rect.top, 0), rect.height);

        hero.style.setProperty('--spotlight-x', localX + 'px');
        hero.style.setProperty('--spotlight-y', localY + 'px');
    };

    const defaultSpotlight = function () {
        const rect = hero.getBoundingClientRect();
        setSpotlightPosition(rect.left + rect.width * 0.5, rect.top + rect.height * 0.5);
    };

    if (!prefersReducedMotion) {
        hero.addEventListener('pointermove', function (event) {
            setSpotlightPosition(event.clientX, event.clientY);
        });

        hero.addEventListener('pointerleave', defaultSpotlight);
        hero.addEventListener('touchmove', function (event) {
            const touch = event.touches && event.touches[0];
            if (!touch) return;
            setSpotlightPosition(touch.clientX, touch.clientY);
        }, { passive: true });
    }

    defaultSpotlight();

    const revealItems = document.querySelectorAll('.reveal-item');
    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        revealItems.forEach(function (item) {
            revealObserver.observe(item);
        });
    } else {
        revealItems.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }

    const titleWords = document.querySelectorAll('.title-word');
    titleWords.forEach(function (word, index) {
        word.style.setProperty('--index', index);
    });

    if (prefersReducedMotion) {
        revealItems.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }
});
