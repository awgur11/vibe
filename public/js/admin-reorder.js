jQuery(function ($) {
window.initializeAdminReorder = (root = document) => {
root.querySelectorAll('[data-reorder]').forEach((container) => {
    if (container.dataset.reorderInitialized) return;
    container.dataset.reorderInitialized = 'true';
    const list = container.querySelector('[data-reorder-list]');
    const status = container.querySelector('[data-reorder-status]');
    let busy = false;
    let previousOrder = [];
    const $list = $(list);
    const rows = () => Array.from(list.children).filter((row) => row.matches('[data-reorder-id]'));
    const restore = (previous) => previous.forEach((row) => list.append(row));
    const save = async (previous) => {
        const ids = rows().map((row) => Number(row.dataset.reorderId));
        if (ids.every((id, index) => id === Number(previous[index].dataset.reorderId))) return;
        busy = true;
        $list.sortable('disable');
        container.setAttribute('aria-busy', 'true');
        Array.from(container.querySelectorAll('[data-reorder-handle]')).filter((handle) => handle.closest('[data-reorder]') === container).forEach((handle) => { handle.disabled = true; });
        status.textContent = 'Зберігаємо порядок…';
        try {
            const response = await fetch(container.dataset.reorderUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': container.dataset.reorderToken,
                },
                body: JSON.stringify({ ...JSON.parse(container.dataset.reorderScope || '{}'), ids }),
            });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('save-failed');
            }
            await response.json();
            status.textContent = 'Порядок збережено.';
            container.dispatchEvent(new CustomEvent('reorder:saved', { detail: { ids } }));
        } catch {
            restore(previous);
            status.textContent = 'Не вдалося зберегти порядок. Попередній порядок відновлено. Оновіть сторінку або спробуйте ще раз.';
        } finally {
            busy = false;
            $list.sortable('enable');
            container.removeAttribute('aria-busy');
            Array.from(container.querySelectorAll('[data-reorder-handle]')).filter((handle) => handle.closest('[data-reorder]') === container).forEach((handle) => { handle.disabled = false; });
        }
    };
    $list.sortable({
        items: '> [data-reorder-id]',
        handle: '[data-reorder-handle]',
        cancel: 'input, textarea, select, option, a',
        axis: 'y',
        distance: 5,
        tolerance: 'pointer',
        placeholder: 'reorder-placeholder',
        forcePlaceholderSize: true,
        revert: 150,
        cursor: 'grabbing',
        scroll: true,
        helper: (event, item) => {
            const helper = item.clone().removeAttr('data-reorder-id');
            helper.children().each(function (index) {
                $(this).width(item.children().eq(index).width());
                if (this.tagName === 'TR') {
                    $(this).children().each(function (cellIndex) {
                        $(this).width(item.children().eq(index).children().eq(cellIndex).width());
                    });
                }
            });
            helper.width(item.outerWidth());
            return helper;
        },
        start: (event, ui) => {
            previousOrder = rows();
            ui.placeholder.height(ui.item.outerHeight());
            status.textContent = 'Перемістіть рядок у потрібне місце.';
        },
        update: () => save(previousOrder),
        stop: () => {
            if (!busy) status.textContent = '';
        },
    });
    list.addEventListener('keydown', async (event) => {
        const handle = event.target.closest('[data-reorder-handle]');
        if (!handle || handle.closest('[data-reorder]') !== container || busy || container.dataset.valuesLoading || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        const row = handle.closest('[data-reorder-id]');
        const previous = rows();
        const index = previous.indexOf(row);
        const target = previous[index + (event.key === 'ArrowUp' ? -1 : 1)];
        if (!target) return;
        list.insertBefore(row, event.key === 'ArrowUp' ? target : target.nextSibling);
        await save(previous);
        handle.focus();
    });
});
};
window.initializeAdminReorder();
});
