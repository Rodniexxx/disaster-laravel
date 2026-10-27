export function initAvatars() {
    const personIcon = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128"><rect width="128" height="128" rx="64" fill="#1b303e"/><circle cx="64" cy="46" r="20" fill="#99ddcc"/><path d="M28 111v-9a36 36 0 0 1 72 0v9" fill="#99ddcc"/></svg>');
    const showFallback = function (image) {
        const fallback = image.dataset.fallback ? new URL(image.dataset.fallback, document.baseURI).href : personIcon;
        const next = image.src === fallback ? personIcon : fallback;
        if (image.src !== personIcon) image.src = next;
    };
    document.addEventListener('error', function (event) {
        const image = event.target;
        if (image instanceof HTMLImageElement && image.matches('[data-avatar]')) {
            showFallback(image);
        }
    }, true);
    // Cached failures can fire before this module's DOMContentLoaded handler.
    document.querySelectorAll('img[data-avatar]').forEach(function (image) {
        if (image.complete && image.naturalWidth === 0) showFallback(image);
    });
    document.querySelectorAll('[data-avatar-editor]').forEach(function (editor) {
        const input = editor.querySelector('[data-avatar-input]');
        const preview = editor.querySelector('[data-avatar-preview]');
        const remove = editor.querySelector('[data-avatar-remove]');
        const error = editor.querySelector('[data-avatar-error]');
        const clear = editor.querySelector('[data-avatar-clear]');
        let objectUrl;
        const update = function () {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = undefined;
            const file = input.files[0];
            clear.hidden = !file;
            let message = '';
            if (file && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) message = 'Choose a JPG, PNG or WebP image.';
            else if (file && file.size > 2 * 1024 * 1024) message = 'Choose an image no larger than 2 MB.';
            input.setCustomValidity(message);
            error.textContent = message;
            if (file && !message) {
                if (remove) remove.checked = false;
                objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
            } else {
                preview.src = remove?.checked ? editor.dataset.default : editor.dataset.current;
            }
        };
        input.addEventListener('change', update);
        clear.addEventListener('click', function () { input.value = ''; input.dispatchEvent(new Event('change', { bubbles: true })); });
        remove?.addEventListener('change', function () { if (remove.checked) input.value = ''; update(); });
        editor.closest('form')?.addEventListener('reset', function () { requestAnimationFrame(update); });
        window.addEventListener('pagehide', function () { if (objectUrl) URL.revokeObjectURL(objectUrl); });
        window.addEventListener('pageshow', function (event) { if (event.persisted) update(); });
    });
}
