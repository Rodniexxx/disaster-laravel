export function initGameShowcases() {
    document.querySelectorAll('[data-game-showcase]').forEach(function (gallery) {
        const slides = Array.from(gallery.querySelectorAll('[data-game-scene]'));
        if (slides.length < 2) return;
        let current = 0, timer, visible = true;
        const syncPlayback = function () {
            window.clearTimeout(timer);
            if (visible && !document.hidden) {
                timer = window.setTimeout(advanceScene, 5000);
            }
        };
        const advanceScene = async function () {
            const next = (current + 1) % slides.length;
            try { await slides[next].querySelector('img').decode(); } catch { syncPlayback(); return; }
            if (!visible || document.hidden) { syncPlayback(); return; }
            current = next;
            slides.forEach(function (slide, index) {
                slide.classList.toggle('is-current', index === current);
                slide.setAttribute('aria-hidden', String(index !== current));
            });
            gallery.querySelectorAll('[data-scene-category]').forEach(function (link) {
                link.classList.toggle('is-featured', link.dataset.sceneCategory === slides[current].dataset.category);
            });
            syncPlayback();
        };
        document.addEventListener('visibilitychange', syncPlayback);
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                syncPlayback();
            }, {threshold: 0.15}).observe(gallery);
        }
        syncPlayback();
    });
}
