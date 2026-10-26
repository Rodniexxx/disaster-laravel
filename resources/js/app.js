import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (navToggle && navLinks) {
        const setNavState = function (open) {
            navLinks.classList.toggle('open', open);
            navToggle.classList.toggle('is-active', open);
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
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
            if (event.key === 'Escape' && navLinks.classList.contains('open')) {
                setNavState(false);
                navToggle.focus();
            }
        });

        window.matchMedia('(max-width: 1170px)').addEventListener('change', function () {
            setNavState(false);
        });

        document.addEventListener('click', function (event) {
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

    // Shared page sections must reveal even on pages without a landing hero.
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealItems = document.querySelectorAll('.reveal, .reveal-item');
    if ('IntersectionObserver' in window && !prefersReducedMotion) {
        const revealEntries = function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        };
        // A tall form can never reach a 15% intersection on a short screen.
        const revealObserver = new IntersectionObserver(revealEntries, { threshold: 0 });
        const itemObserver = new IntersectionObserver(revealEntries, { threshold: 0.15 });

        revealItems.forEach(function (item) {
            (item.classList.contains('reveal-item') ? itemObserver : revealObserver).observe(item);
        });
    } else {
        revealItems.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }

    const hero = document.querySelector('.hero');
    if (!hero) return;

    const spotlight = hero.querySelector('.hero-reveal');

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

    const titleWords = document.querySelectorAll('.title-word');
    titleWords.forEach(function (word, index) {
        word.style.setProperty('--index', index);
    });

    // Rotate the featured scenario, the page indicator and the training module
    // highlight through every available disaster category.
    const carousel = document.querySelector('[data-scenario-carousel]');
    const carouselDataNode = document.querySelector('[data-scenario-data]');

    if (carousel && carouselDataNode) {
        let carouselItems = [];

        try {
            carouselItems = JSON.parse(carouselDataNode.textContent) || [];
        } catch (error) {
            carouselItems = [];
        }

        if (carouselItems.length > 1) {
            const total = carouselItems.length;
            const pad = function (value) {
                return String(value).padStart(2, '0');
            };
            const numberTarget = carousel.querySelector('[data-carousel-number]');
            const labelTarget = carousel.querySelector('[data-carousel-label]');
            const titleTarget = carousel.querySelector('[data-carousel-title]');
            const descriptionTarget = carousel.querySelector('[data-carousel-description]');
            const targets = [numberTarget, labelTarget, titleTarget, descriptionTarget].filter(Boolean);
            const moduleRows = carousel.querySelectorAll('[data-carousel-row]');
            const canAnimate = targets.length > 0
                && typeof targets[0].animate === 'function';
            // Keep the movement for most users, but fall back to a plain
            // (still smooth) crossfade when reduced motion is requested.
            const motion = !prefersReducedMotion;
            const exitFrames = motion
                ? [
                    { opacity: 1, transform: 'translateY(0)' },
                    { opacity: 0, transform: 'translateY(-8px)' },
                ]
                : [{ opacity: 1 }, { opacity: 0 }];
            const enterFrames = motion
                ? [
                    { opacity: 0, transform: 'translateY(10px)' },
                    { opacity: 1, transform: 'translateY(0)' },
                ]
                : [{ opacity: 0 }, { opacity: 1 }];
            const exitDuration = motion ? 240 : 180;
            const enterDuration = motion ? 380 : 260;
            const enterStagger = motion ? 45 : 0;
            const enterTotal = enterDuration + (targets.length - 1) * enterStagger;
            let activeIndex = 0;
            let rotationTimer = null;
            let isSwapping = false;

            // Swap the landing hero background based on the active scenario slide.
            const heroBgTarget = document.querySelector('[data-carousel-bg]');
            const heroPhotoLayer = heroBgTarget ? heroBgTarget.querySelector('.hero-photo') : null;
            let currentHeroImage = '';

            if (carouselItems[0] && carouselItems[0].image) {
                currentHeroImage = carouselItems[0].image;
            }

            carouselItems.forEach(function (item) {
                if (item && item.image) {
                    var preload = new Image();
                    preload.src = item.image;
                }
            });

            const applyHeroImage = function (item) {
                if (!heroBgTarget || !item || !item.image || item.image === currentHeroImage) {
                    return;
                }

                currentHeroImage = item.image;
                var imageUrl = 'url("' + item.image.replace(/"/g, '\\"') + '")';

                if (!heroPhotoLayer || !motion) {
                    heroBgTarget.style.setProperty('--hero-image', imageUrl);
                    return;
                }

                heroPhotoLayer.style.opacity = '0';
                window.setTimeout(function () {
                    heroBgTarget.style.setProperty('--hero-image', imageUrl);
                    heroPhotoLayer.style.opacity = '';
                }, 280);
            };

            const applyContent = function (index) {
                const item = carouselItems[index];
                if (!item) return;

                if (numberTarget) numberTarget.textContent = pad(index + 1) + ' / ' + pad(total);
                if (labelTarget) labelTarget.textContent = item.title;
                if (titleTarget) titleTarget.textContent = item.title;
                if (descriptionTarget) descriptionTarget.textContent = item.description;

                moduleRows.forEach(function (row, rowIndex) {
                    row.classList.toggle('is-active', rowIndex === index);
                });

                applyHeroImage(item);
            };

            const clearAnimations = function () {
                targets.forEach(function (el) {
                    el.getAnimations().forEach(function (animation) {
                        animation.cancel();
                    });
                });
            };

            const showSlide = function (index) {
                if (!canAnimate) {
                    clearAnimations();
                    applyContent(index);
                    return;
                }

                isSwapping = true;
                clearAnimations();

                // Fade the current content out so nothing pops.
                targets.forEach(function (el) {
                    el.animate(exitFrames, {
                        duration: exitDuration,
                        easing: 'ease-in',
                        fill: 'forwards',
                    });
                });

                // Swap the text at the midway point, then bring it back in.
                window.setTimeout(function () {
                    applyContent(index);

                    // Drop the exit effect once it has fully faded so the
                    // entrance animation is the only thing driving the style.
                    clearAnimations();

                    targets.forEach(function (el, position) {
                        el.animate(enterFrames, {
                            duration: enterDuration,
                            delay: position * enterStagger,
                            easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                            fill: 'both',
                        });
                    });
                }, exitDuration);

                window.setTimeout(function () {
                    isSwapping = false;
                }, exitDuration + enterTotal);
            };

            const advanceSlide = function () {
                if (isSwapping) return;
                activeIndex = (activeIndex + 1) % total;
                showSlide(activeIndex);
            };

            const startRotation = function () {
                if (rotationTimer !== null) return;
                rotationTimer = window.setInterval(advanceSlide, 4000);
            };

            const stopRotation = function () {
                if (rotationTimer === null) return;
                window.clearInterval(rotationTimer);
                rotationTimer = null;
            };

            applyContent(0);

            // Always auto-advance the slides with a smooth crossfade.
            startRotation();
            carousel.addEventListener('mouseenter', stopRotation);
            carousel.addEventListener('mouseleave', startRotation);
            carousel.addEventListener('focusin', stopRotation);
            carousel.addEventListener('focusout', startRotation);
        }
    }

    if (prefersReducedMotion) {
        revealItems.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }
});
