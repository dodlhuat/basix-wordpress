// Replaces the browser's native error bubbles with basix's .form-group.error + .form-hint.
const form = document.getElementById('commentform');

if (form) {
    form.noValidate = true;

    const messages = JSON.parse(document.getElementById('basix-i18n')?.textContent ?? '{}');
    const messageFor = (field) => {
        const v = field.validity;
        if (v.valueMissing) return messages.required ?? field.validationMessage;
        if (v.typeMismatch) return messages[field.type] ?? field.validationMessage;
        return field.validationMessage;
    };

    const group = (field) => field.closest('.form-group');

    const clear = (field) => {
        const g = group(field);
        if (!g) return;
        g.classList.remove('error');
        g.querySelector('.form-hint')?.remove();
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    };

    const show = (field) => {
        const g = group(field);
        if (!g) return;
        clear(field);
        g.classList.add('error');
        const hint = document.createElement('div');
        hint.className = 'form-hint';
        hint.id = `${field.id}-error`;
        hint.textContent = messageFor(field);
        g.append(hint);
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', hint.id);
    };

    const fields = () => [...form.querySelectorAll('input:not([type=checkbox]):not([type=hidden]):not([type=submit]), textarea')];

    form.addEventListener('submit', (e) => {
        const invalid = fields().filter((f) => !f.checkValidity());
        fields().forEach((f) => (invalid.includes(f) ? show(f) : clear(f)));
        if (invalid.length) {
            e.preventDefault();
            invalid[0].focus();
        }
    });

    form.addEventListener('input', (e) => {
        if (group(e.target)?.classList.contains('error') && e.target.checkValidity()) clear(e.target);
    });
}
