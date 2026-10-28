import './bootstrap';
import { initGameShowcases } from './game-showcase';
import { initPlayerProgress } from './player-progress';
import { initAvatars } from './avatars';
import { initPlayerProfiles } from './player-profiles';

document.addEventListener('DOMContentLoaded', function () {
    initPlayerProgress();
    initAvatars();
    initPlayerProfiles();
    const siteHeader = document.querySelector('.site-header');
    if (siteHeader) {
        let scrollFramePending = false;
        const updateHeaderGlass = function () {
            siteHeader.classList.toggle('is-scrolled', window.scrollY > 16);
            scrollFramePending = false;
        };
        updateHeaderGlass();
        window.addEventListener('pageshow', updateHeaderGlass);
        window.addEventListener('scroll', function () {
            if (scrollFramePending) return;
            scrollFramePending = true;
            window.requestAnimationFrame(updateHeaderGlass);
        }, { passive: true });
    }

    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    const navExplore = document.querySelector('.nav-explore');

    if (navToggle && navLinks) {
        const setNavState = function (open) {
            navLinks.classList.toggle('open', open);
            navToggle.classList.toggle('is-active', open);
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (!open && navExplore) navExplore.open = false;
        };

        navToggle.addEventListener('click', function () {
            setNavState(!navLinks.classList.contains('open'));
        });

        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setNavState(false);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && navExplore && navExplore.open) {
                navExplore.open = false;
                navExplore.querySelector('summary').focus();
                return;
            }
            if (event.key === 'Escape' && navLinks.classList.contains('open')) {
                setNavState(false);
                navToggle.focus();
            }
        });

        window.matchMedia('(max-width: 1170px)').addEventListener('change', function () {
            setNavState(false);
        });

        document.addEventListener('click', function (event) {
            if (navExplore && navExplore.open && !navExplore.contains(event.target)) {
                navExplore.open = false;
            }
            if (!navLinks.classList.contains('open')) return;
            if (navLinks.contains(event.target) || navToggle.contains(event.target)) return;
            setNavState(false);
        });
    }

    const adminSidebar = document.getElementById('admin-sidebar');
    const adminToggle = document.querySelector('.admin-menu-toggle');
    const adminBackdrop = document.querySelector('[data-admin-backdrop]');

    if (adminSidebar && adminToggle) {
        const setAdminState = function (open) {
            adminSidebar.classList.toggle('open', open);
            if (adminBackdrop) {
                adminBackdrop.classList.toggle('open', open);
                adminBackdrop.hidden = !open;
            }
            adminToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('admin-nav-open', open);
        };

        adminToggle.addEventListener('click', function () {
            setAdminState(!adminSidebar.classList.contains('open'));
        });

        if (adminBackdrop) {
            adminBackdrop.addEventListener('click', function () {
                setAdminState(false);
            });
        }

        const adminClose = adminSidebar.querySelector('.admin-sidebar-close');
        if (adminClose) {
            adminClose.addEventListener('click', function () {
                setAdminState(false);
            });
        }

        adminSidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setAdminState(false);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && adminSidebar.classList.contains('open')) {
                setAdminState(false);
                adminToggle.focus();
            }
        });
    }

    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const field = button.closest('.password-field');
            const input = field ? field.querySelector('input') : null;
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.classList.toggle('is-visible', isPassword);
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

    // Opt-in guard that warns before leaving a page with unsaved form data.
    const guardedForms = document.querySelectorAll('form[data-unsaved-guard]');
    const guardedFormsDirty = function () {
        return Array.prototype.some.call(guardedForms, function (form) {
            return form.dataset.dirty === 'true';
        });
    };
    const clearGuardedDirtyState = function () {
        guardedForms.forEach(function (form) {
            form.dataset.dirty = 'false';
        });
    };

    guardedForms.forEach(function (form) {
        const markDirty = function () {
            form.dataset.dirty = 'true';
        };

        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);
        form.addEventListener('submit', function () {
            form.dataset.dirty = 'false';
        });
    });

    if (guardedForms.length) {
        window.addEventListener('beforeunload', function (event) {
            if (!guardedFormsDirty()) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });
    }

    // Back button: use real browser history when it stays inside the app,
    // otherwise fall back to the page's section-aware destination.
    const hasInternalHistory = function () {
        if (window.history.length <= 1) {
            return false;
        }

        const referrer = document.referrer;

        return referrer !== '' && referrer.indexOf(window.location.origin) === 0;
    };

    document.querySelectorAll('[data-back-button]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (guardedFormsDirty()) {
                const leave = window.confirm('You have unsaved changes.\n\nAre you sure you want to leave this page?');

                if (!leave) {
                    event.preventDefault();
                    return;
                }

                clearGuardedDirtyState();
            }

            if (hasInternalHistory()) {
                event.preventDefault();
                window.history.back();
            }
        });
    });


    initGameShowcases();

    // Linked scenario rows open the matching guide, including direct hash links.
    const openLinkedGuide = function () {
        const target = document.getElementById(window.location.hash.slice(1));
        if (target && target.matches('details.safety-card')) target.open = true;
    };
    openLinkedGuide();
    window.addEventListener('hashchange', openLinkedGuide);

    // Progressive entrances: content remains visible with no script or reduced motion.
    if (document.body.classList.contains('home-page') &&
        !window.matchMedia('(prefers-reduced-motion: reduce)').matches &&
        'IntersectionObserver' in window) {
        const entrances = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('taste-enter');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.08 });
        document.querySelectorAll('.home-page .section-heading, .scenario-row, .player-entry > div, .safety-card, .contact-card, .references, .about-game-image, .about-lead, .announcement').forEach(function (element) {
            const siblings = Array.from(element.parentElement.children);
            element.style.setProperty('--reveal-delay', Math.min(siblings.indexOf(element), 3) * 75 + 'ms');
            entrances.observe(element);
        });
    }
});
