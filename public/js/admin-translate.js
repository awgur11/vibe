document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-translate-fields]');
    if (!button || button.disabled) return;

    const block = button.closest('.multilingual-block');
    const fields = [...block.querySelectorAll('.form-input-value[data-locale]')]
        .filter((field) => !field.disabled && !field.readOnly);
    fields.forEach((field) => field.summernoteInstance?.updateSourceElement());
    const source = fields.find((field) => field.value.trim() !== '');
    const status = block.querySelector('[data-translation-status]');
    if (!source) {
        status.textContent = button.dataset.emptyMessage;
        fields[0]?.focus();
        return;
    }

    const text = source.value;
    const sourceLocale = source.dataset.locale;
    const targets = fields.filter((field) => field !== source);
    const label = button.textContent;
    button.disabled = true;
    button.textContent = button.dataset.loadingMessage;
    block.setAttribute('aria-busy', 'true');
    status.textContent = button.dataset.loadingMessage;

    try {
        const results = await Promise.allSettled(targets.map(async (field) => {
            const originalValue = field.value;
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': button.form.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({text, source_locale: sourceLocale, target_locale: field.dataset.locale}),
            });
            if (!response.ok) throw new Error('Translation failed');
            const data = await response.json();
            if (typeof data.translation !== 'string') throw new Error('Invalid translation');
            field.summernoteInstance?.updateSourceElement();
            if (field.value !== originalValue) return;
            if (field.maxLength >= 0 && [...data.translation].length > field.maxLength) {
                throw new Error('Translation exceeds field length');
            }
            field.value = data.translation;
            field.summernoteInstance?.setData(data.translation);
            field.dispatchEvent(new Event('input', {bubbles: true}));
            field.dispatchEvent(new Event('change', {bubbles: true}));
        }));
        const failedLocales = targets.filter((field, index) => results[index].status === 'rejected')
            .map((field) => field.dataset.locale);
        status.textContent = failedLocales.length
            ? `${button.dataset.errorMessage} ${failedLocales.join(', ')}`
            : button.dataset.successMessage;
    } finally {
        button.disabled = false;
        button.textContent = label;
        block.removeAttribute('aria-busy');
    }
});
