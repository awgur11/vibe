document.addEventListener('change', async (event) => {
    const checkbox = event.target.closest('[data-publication-toggle]');
    if (!checkbox) return;
    const previous = !checkbox.checked;
    checkbox.disabled = true;
    checkbox.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(checkbox.dataset.url, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': checkbox.dataset.token,
            },
            body: JSON.stringify({ public: checkbox.checked }),
        });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('save-failed');
        }
        const data = await response.json();
        if (typeof data.public !== 'boolean') throw new Error('invalid-response');
        checkbox.checked = data.public;
        window.adminToast(data.public ? 'Опубліковано.' : 'Знято з публікації.');
    } catch {
        checkbox.checked = previous;
        window.adminToast('Не вдалося зберегти. Спробуйте ще раз.', 'error');
    } finally {
        checkbox.disabled = false;
        checkbox.removeAttribute('aria-busy');
    }
});
