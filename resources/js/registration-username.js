export function capitalizeUsername(value) {
    return value.replace(/(^|[\p{Z}\t\n\r\f\x0B])(\p{L})/gu, (_, space, letter) => space + letter.toUpperCase());
}

export function initUsernameCapitalization(root = document) {
    root.querySelectorAll('[data-registration-username], [data-profile-username]').forEach((input) => {
        let composing = false;
        const format = () => {
            const original = input.value;
            const formatted = capitalizeUsername(original);
            if (formatted === original) return;

            const start = input.selectionStart;
            const end = input.selectionEnd;
            const direction = input.selectionDirection;
            input.value = formatted;
            if (start !== null && end !== null) {
                // Uppercase can expand a character (e.g. ß → SS). Map both
                // selection offsets through the same transform as the value.
                input.setSelectionRange(
                    capitalizeUsername(original.slice(0, start)).length,
                    capitalizeUsername(original.slice(0, end)).length,
                    direction,
                );
            }
        };

        input.addEventListener('compositionstart', () => { composing = true; });
        input.addEventListener('compositionend', () => { composing = false; format(); });
        input.addEventListener('input', (event) => {
            if (!composing && !event.isComposing) format();
        });
        input.form?.addEventListener('submit', format);
        format();
    });
}
