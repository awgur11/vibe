document.querySelectorAll('[data-product-gallery]').forEach(gallery => {
    const drop = gallery.querySelector('[data-gallery-drop]');
    const fileInput = gallery.querySelector('[data-gallery-files]');
    const urlInput = gallery.querySelector('[data-gallery-url]');
    const list = gallery.querySelector('[data-gallery-list]');
    let queue = Promise.resolve();
    let pending = 0;
    let dragDepth = 0;
    let sortable = null;
    let previousOrder = [];

    function changePending(amount) {
        pending += amount;
        if (sortable) sortable.sortable('option', 'disabled', pending > 0);
        gallery.querySelectorAll('[data-gallery-delete]').forEach(button => button.disabled = pending > 0);
        gallery.querySelectorAll('[data-gallery-rotate]').forEach(button => button.disabled = pending > 0 || !button.dataset.rotateUrl);
        gallery.querySelectorAll('[data-gallery-move]').forEach(button => button.disabled = pending > 0 || !button.closest('[data-gallery-picture]').dataset.pictureId);
    }

    async function request(url, options) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': gallery.dataset.token, ...options.headers },
        });
        const data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;
        if (!response.ok || !data) {
            const errors = data?.errors ? Object.values(data.errors).flat().join(' ') : null;
            throw new Error(errors || data?.message || gallery.dataset.errorMessage);
        }
        return data;
    }

    function makeButton(attribute, label, symbol, extraClass = '') {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `button ${extraClass || 'secondary'}`;
        button.setAttribute(attribute, '');
        button.title = label;
        button.setAttribute('aria-label', label);
        button.textContent = symbol;
        button.disabled = true;
        return button;
    }

    function createTile(label, preview) {
        const tile = document.createElement('div');
        tile.className = 'product-gallery-picture';
        tile.dataset.galleryPicture = '';
        tile.setAttribute('aria-busy', 'true');
        const link = document.createElement('a');
        link.target = '_blank';
        link.rel = 'noopener';
        link.draggable = false;
        const image = document.createElement('img');
        image.alt = label;
        image.width = 160;
        image.height = 120;
        image.draggable = false;
        if (preview) {
            image.src = preview;
            link.href = preview;
        }
        link.append(image);
        const controls = document.createElement('div');
        controls.className = 'product-gallery-controls';
        controls.append(
            makeButton('data-gallery-move', gallery.dataset.moveMessage, '↕', 'secondary product-gallery-move'),
            makeButton('data-gallery-rotate', gallery.dataset.rotateMessage, '↻'),
            makeButton('data-gallery-delete', gallery.dataset.deleteMessage, '×', 'danger product-gallery-delete'),
        );
        tile.append(link, controls);
        list.append(tile);
        return tile;
    }

    function updatePicture(tile, data) {
        tile.dataset.pictureId = data.id;
        tile.querySelector('img').src = data.thumbnail_url;
        tile.querySelector('a').href = data.url;
        tile.querySelector('[data-gallery-delete]').dataset.destroyUrl = data.destroy_url;
        tile.querySelector('[data-gallery-rotate]').dataset.rotateUrl = data.rotate_url;
        tile.classList.remove('is-error');
        if (sortable) sortable.sortable('refresh');
    }

    function enqueue(source) {
        const isFile = source instanceof File;
        const preview = isFile ? URL.createObjectURL(source) : null;
        let tile = isFile ? createTile(source.name, preview) : null;
        changePending(1);
        queue = queue.then(async () => {
            try {
                const maximumBytes = Number(gallery.dataset.maxBytes);
                if (isFile && maximumBytes > 0 && source.size > maximumBytes) throw new Error(gallery.dataset.sizeMessage);
                if (isFile && source.type && !/^image\/(jpeg|png|gif|webp)$/.test(source.type)) throw new Error(gallery.dataset.invalidMessage);
                const body = new FormData();
                body.append(isFile ? 'image' : 'preview_url', source);
                const data = await request(gallery.dataset.storeUrl, { method: 'POST', body });
                tile ??= createTile(source, null);
                updatePicture(tile, data);
                window.adminToast(gallery.dataset.savedMessage);
            } catch (error) {
                tile?.remove();
                if (sortable) sortable.sortable('refresh');
                window.adminToast(error.message || gallery.dataset.errorMessage, 'error');
            } finally {
                tile?.removeAttribute('aria-busy');
                changePending(-1);
                if (preview) URL.revokeObjectURL(preview);
            }
        });
    }

    const pictures = () => Array.from(list.children).filter(tile => tile.dataset.pictureId);
    async function saveOrder(previous) {
        const ids = pictures().map(tile => Number(tile.dataset.pictureId));
        if (ids.every((id, index) => id === Number(previous[index]?.dataset.pictureId))) return;
        changePending(1);
        try {
            await request(gallery.dataset.reorderUrl, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: Number(gallery.dataset.productId), ids }),
            });
            window.adminToast(gallery.dataset.orderSaved);
        } catch {
            previous.forEach(tile => list.append(tile));
            window.adminToast(gallery.dataset.orderError, 'error');
        } finally {
            changePending(-1);
        }
    }
    sortable = window.jQuery(list).sortable({
        items: '> [data-picture-id]',
        handle: '[data-gallery-move], img',
        cancel: 'button:not([data-gallery-move]), input',
        distance: 5,
        tolerance: 'pointer',
        placeholder: 'product-gallery-placeholder',
        forcePlaceholderSize: false,
        cursor: 'grabbing',
        helper: (event, item) => item.clone().removeAttr('data-picture-id').outerWidth(item.outerWidth()).outerHeight(item.outerHeight()),
        start: (event, ui) => {
            previousOrder = pictures();
            ui.placeholder.outerWidth(ui.item.outerWidth()).outerHeight(ui.item.outerHeight());
        },
        update: () => saveOrder(previousOrder),
    });
    list.addEventListener('keydown', async event => {
        const handle = event.target.closest('[data-gallery-move]');
        if (!handle || pending || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        const previous = pictures();
        const tile = handle.closest('[data-gallery-picture]');
        const backwards = ['ArrowLeft', 'ArrowUp'].includes(event.key);
        const target = previous[previous.indexOf(tile) + (backwards ? -1 : 1)];
        if (!target) return;
        list.insertBefore(tile, backwards ? target : target.nextSibling);
        await saveOrder(previous);
        handle.focus();
    });
    function addUrl(value = urlInput.value) {
        try {
            const url = new URL(value.trim());
            if (!['http:', 'https:'].includes(url.protocol)) throw new Error('invalid-url');
            enqueue(url.href);
            urlInput.value = '';
        } catch {
            window.adminToast(gallery.dataset.urlMessage, 'error');
        }
    }

    fileInput.addEventListener('change', () => {
        Array.from(fileInput.files).forEach(enqueue);
        fileInput.value = '';
    });
    gallery.querySelector('[data-gallery-add-url]').addEventListener('click', () => addUrl());
    urlInput.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addUrl();
        }
    });
    urlInput.addEventListener('paste', event => {
        const value = event.clipboardData?.getData('text/plain').trim();
        if (/^https?:\/\//i.test(value || '')) {
            event.preventDefault();
            addUrl(value);
        }
    });
    drop.addEventListener('dragenter', event => {
        event.preventDefault();
        dragDepth++;
        drop.classList.add('is-dragging');
    });
    drop.addEventListener('dragover', event => { event.preventDefault(); });
    drop.addEventListener('dragleave', event => {
        event.preventDefault();
        dragDepth = Math.max(0, dragDepth - 1);
        if (!dragDepth) drop.classList.remove('is-dragging');
    });
    drop.addEventListener('drop', event => {
        event.preventDefault();
        dragDepth = 0;
        drop.classList.remove('is-dragging');
        const files = Array.from(event.dataTransfer?.files || []);
        if (files.length) {
            files.forEach(enqueue);
        } else {
            const value = event.dataTransfer?.getData('text/uri-list') || event.dataTransfer?.getData('text/plain') || '';
            addUrl(value.split('\n').find(line => line.trim() && !line.startsWith('#')) || '');
        }
    });
    gallery.addEventListener('click', async event => {
        const button = event.target.closest('[data-gallery-delete], [data-gallery-rotate]');
        if (!button || button.disabled) return;
        const tile = button.closest('[data-gallery-picture]');
        const isRotation = button.hasAttribute('data-gallery-rotate');
        if (!isRotation && !button.dataset.destroyUrl) {
            tile.remove();
            return;
        }
        if (!isRotation && !window.confirm(gallery.dataset.deleteConfirm)) return;
        changePending(1);
        tile.setAttribute('aria-busy', 'true');
        try {
            const data = await request(isRotation ? button.dataset.rotateUrl : button.dataset.destroyUrl, { method: isRotation ? 'PATCH' : 'DELETE' });
            if (isRotation) {
                updatePicture(tile, data);
                window.adminToast(gallery.dataset.rotatedMessage);
            } else {
                tile.remove();
                sortable.sortable('refresh');
                window.adminToast(data.message || gallery.dataset.deletedMessage);
            }
        } catch (error) {
            window.adminToast(error.message || (isRotation ? gallery.dataset.rotateError : gallery.dataset.deleteError), 'error');
        } finally {
            tile.removeAttribute('aria-busy');
            changePending(-1);
        }
    });
    gallery.closest('form')?.addEventListener('submit', event => {
        if (pending) {
            event.preventDefault();
            window.adminToast(gallery.dataset.waitMessage, 'error');
        }
    });
    changePending(0);
});
