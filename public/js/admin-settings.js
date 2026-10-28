document.querySelectorAll('[data-setting-form]').forEach((form) => {
    const error = form.querySelector('[data-setting-error]');
    const controls = () => Array.from(form.querySelectorAll('input, textarea, select, button'));
    const capture = () => controls().map((element) => ({ element, value: element.value, checked: element.checked }));
    let saved = capture();
    let busy = false;
    let objectUrl = null;
    if (form.hasAttribute('data-setting-phones')) {
        const list = form.querySelector('[data-phone-list]');
        const addButton = form.querySelector('[data-phone-add]');
        const updateLimit = () => { addButton.disabled = list.children.length >= 20; };
        form.addEventListener('click', (event) => {
            if (busy) return;
            if (event.target.closest('[data-phone-add]') && list.children.length < 20) {
                list.append(form.querySelector('[data-phone-template]').content.cloneNode(true));
                list.lastElementChild.querySelector('input').focus();
            }
            if (event.target.closest('[data-phone-remove]')) {
                event.target.closest('[data-phone-row]').remove();
            }
            updateLimit();
        });
        updateLimit();
    }
    const renderPreview = (url) => {
        const image = form.querySelector('[data-setting-preview]');
        if (!image) return;
        image.hidden = !url;
        form.querySelector('[data-setting-placeholder]').hidden = Boolean(url);
        if (url) image.src = url;
        else image.removeAttribute('src');
    };
    const restore = () => {
        saved.forEach(({ element, value, checked }) => {
            if (element.type === 'file') element.value = '';
            else element.value = value;
            if (['checkbox', 'radio'].includes(element.type)) element.checked = checked;
        });
        renderPreview(form.dataset.previewUrl);
    };
    const save = async (submitter = null) => {
        if (busy || form.getAttribute('aria-busy') === 'true') return;
        if (!submitter?.formNoValidate && !form.reportValidity()) return;
        const data = new FormData(form);
        if (submitter?.name) data.set(submitter.name, submitter.value);
        if (data.has('remove_preview')) {
            data.delete('preview');
            data.delete('preview_url');
        }
        const fileInput = form.querySelector('[name="preview"]');
        const urlInput = form.querySelector('[name="preview_url"]');
        if (form.hasAttribute('data-setting-image') && !fileInput.files.length && !urlInput.value.trim() && !data.has('remove_preview')) return;
        if (fileInput?.files[0] && !data.has('remove_preview')) {
            objectUrl = URL.createObjectURL(fileInput.files[0]);
            renderPreview(objectUrl);
        }
        const disabledStates = controls().map((element) => [element, element.disabled]);
        disabledStates.forEach(([element]) => { element.disabled = true; });
        busy = true;
        error.textContent = '';
        form.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': data.get('_token') },
                body: data,
            });
            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Не вдалося зберегти налаштування. Оновіть сторінку та спробуйте ще раз.');
            }
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'Не вдалося зберегти налаштування.'));
            }
            if (form.hasAttribute('data-setting-image')) {
                form.dataset.previewUrl = result.preview_url || '';
                renderPreview(form.dataset.previewUrl);
                form.querySelector('[data-setting-remove]').hidden = !result.preview_url;
                fileInput.value = '';
                urlInput.value = '';
            }
            saved = capture();
            window.adminToast(result.message || 'Налаштування збережено.');
        } catch (exception) {
            if (form.hasAttribute('data-setting-image') || form.querySelector('[data-setting-autosave]')) restore();
            error.textContent = exception.message;
            window.adminToast(exception.message, 'error');
        } finally {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            disabledStates.forEach(([element, disabled]) => { element.disabled = disabled; });
            busy = false;
            form.removeAttribute('aria-busy');
        }
    };
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        save(event.submitter);
    });
    form.addEventListener('change', (event) => {
        if (!event.target.matches('[data-setting-autosave]')) return;
        if (event.target.type === 'file' && event.target.files.length) {
            form.querySelector('[name="preview_url"]').value = '';
        } else if (event.target.name === 'preview_url' && event.target.value.trim()) {
            form.querySelector('[name="preview"]').value = '';
        }
        save();
    });
});
