document.querySelectorAll('textarea[data-summernote]').forEach((field) => {
    const editor = window.jQuery(field);
    const isRequired = field.required;
    const uploadStatus = document.createElement('p');
    uploadStatus.className = 'hint';
    uploadStatus.setAttribute('role', 'status');
    field.after(uploadStatus);
    let pendingUploads = 0;

    editor.summernote({
        lang: 'uk-UA',
        height: 220,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture', 'table']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ],
        callbacks: {
            async onImageUpload(files) {
                editor.summernote('saveRange');
                for (const file of files) {
                    pendingUploads++;
                    uploadStatus.textContent = 'Завантаження зображення…';
                    try {
                        const body = new FormData();
                        body.append('image', file);
                        const response = await fetch(field.dataset.uploadUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': field.form.querySelector('[name="_token"]').value,
                            },
                            body,
                        });
                        const data = await response.json();
                        if (!response.ok || typeof data.url !== 'string') {
                            throw new Error(data.errors?.image?.[0] || 'Не вдалося завантажити зображення.');
                        }
                        editor.summernote('restoreRange');
                        editor.summernote('insertImage', data.url);
                        uploadStatus.textContent = '';
                    } catch (error) {
                        uploadStatus.textContent = error.message || 'Не вдалося завантажити зображення.';
                    } finally {
                        pendingUploads--;
                    }
                }
            },
            onChange(contents) {
                field.value = editor.summernote('isEmpty') ? '' : contents;
                field.dispatchEvent(new Event('input', { bubbles: true }));
            },
        },
    });

    field.required = false;
    field.summernoteInstance = {
        updateSourceElement() {
            field.value = editor.summernote('isEmpty') ? '' : editor.summernote('code');
        },
        setData(contents) {
            editor.summernote('code', contents);
        },
    };

    if (field.readOnly || field.disabled) {
        editor.summernote('disable');
    }

    field.form?.addEventListener('submit', (event) => {
        if (pendingUploads > 0) {
            event.preventDefault();
            uploadStatus.textContent = 'Дочекайтеся завершення завантаження зображень.';
        }
        field.summernoteInstance.updateSourceElement();
        if (isRequired && !field.disabled && !field.readOnly && editor.summernote('isEmpty')) {
            event.preventDefault();
            editor.summernote('focus');
        }
    });
});
