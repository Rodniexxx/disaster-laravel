import test from 'node:test';
import assert from 'node:assert/strict';
import { capitalizeUsername, initRegistrationUsernames } from '../../resources/js/registration-username.js';

class Input extends EventTarget {
    form = new EventTarget();
    selectionStart = 0;
    selectionEnd = 0;
    selectionDirection = 'none';
    value = '';

    setSelectionRange(start, end, direction = 'none') {
        this.selectionStart = start;
        this.selectionEnd = end;
        this.selectionDirection = direction;
    }

    edit(value, start = value.length, end = start, direction = 'none', isComposing = false) {
        this.value = value;
        this.setSelectionRange(start, end, direction);
        const event = new Event('input');
        event.isComposing = isComposing;
        this.dispatchEvent(event);
    }
}

function field() {
    const input = new Input();
    initRegistrationUsernames({ querySelectorAll: (selector) => {
        assert.equal(selector, '[data-registration-username]');
        return [input];
    } });
    return input;
}

test('capitalizes word initials while preserving other letters, spacing, and punctuation', () => {
    for (const [input, expected] of [
        ['rodnie calmada', 'Rodnie Calmada'],
        ['john michael borlagdan', 'John Michael Borlagdan'],
        ['joshua brondial', 'Joshua Brondial'],
        ['mARIA sANTOS', 'MARIA SANTOS'],
        ['rodnie  cALMADA', 'Rodnie  CALMADA'],
        ['élise pérez', 'Élise Pérez'],
        ["anna-marie o'neill", "Anna-marie O'neill"],
        ['john\u00a0doe', 'John\u00a0Doe'],
        ['', ''],
    ]) assert.equal(capitalizeUsername(input), expected);
});

test('handles typing, spaces, deleting, and editing in the middle without moving the caret', () => {
    const input = field();
    input.edit('r');
    assert.equal(input.value, 'R');
    assert.equal(input.selectionStart, 1);
    input.edit('Rodnie ');
    assert.equal(input.value, 'Rodnie ');
    input.edit('Rodnie c');
    assert.equal(input.value, 'Rodnie C');
    input.edit('rodnie Calmada', 1);
    assert.equal(input.value, 'Rodnie Calmada');
    assert.equal(input.selectionStart, 1);
    input.edit('odnie Calmada', 0);
    assert.equal(input.value, 'Odnie Calmada');
    assert.equal(input.selectionStart, 0);
    input.edit('');
    assert.equal(input.value, '');
});

test('preserves backward selections and maps offsets when uppercase expands a letter', () => {
    const input = field();
    input.edit('john michael', 2, 8, 'backward');
    assert.equal(input.value, 'John Michael');
    assert.equal(input.selectionStart, 2);
    assert.equal(input.selectionEnd, 8);
    assert.equal(input.selectionDirection, 'backward');
    input.edit('ßeta müller', 1, 7, 'backward');
    assert.equal(input.value, 'SSeta Müller');
    assert.equal(input.selectionStart, 2);
    assert.equal(input.selectionEnd, 8);
});

test('formats pasted text through the input event', () => {
    const input = field();
    input.edit('mARIA sANTOS');
    assert.equal(input.value, 'MARIA SANTOS');
    assert.equal(input.selectionStart, 12);
});

test('does not interrupt IME composition and formats when composition ends', () => {
    const input = field();
    input.dispatchEvent(new Event('compositionstart'));
    input.edit('élise pérez', 3, 3, 'none', true);
    assert.equal(input.value, 'élise pérez');
    input.dispatchEvent(new Event('compositionend'));
    assert.equal(input.value, 'Élise Pérez');
    assert.equal(input.selectionStart, 3);
});

test('formats an autofilled value on form submission', () => {
    const input = field();
    input.value = 'joshua brondial';
    input.form.dispatchEvent(new Event('submit'));
    assert.equal(input.value, 'Joshua Brondial');
});
