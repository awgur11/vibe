@php
    $previewName = $name ?? 'preview';
    $previewId = $id ?? $previewName;
    $previewUrl = isset($value) ? $value->previewUrl('four') : null;
    $externalUrl = isset($value) && preg_match('/^https?:\/\//', $value->preview ?? '') ? $value->preview : '';
@endphp
<div class="panel field" data-preview-field data-current-url="{{ $previewUrl }}">
    <div class="preview-layout">
        <div class="preview-display" aria-label="{{ __('Попередній перегляд') }}">
            <img data-preview-image hidden alt="{{ __('Попередній перегляд') }}">
            <div data-preview-placeholder class="preview-placeholder">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m3 17 5-5 4 4 3-3 6 6"/></svg>
                <span>{{ __('Зображення відсутнє') }}</span>
            </div>
        </div>
        <div class="preview-controls">
            <label for="{{ $previewId }}">{{ $title ?? __('Зображення сторінки') }}</label>
            <input type="file" id="{{ $previewId }}" name="{{ $previewName }}" accept="image/jpeg,image/png,image/gif,image/webp"
                data-preview-input @error($previewName) aria-invalid="true" aria-describedby="{{ $previewId }}-error" @enderror>
            <p class="hint">{{ __('validation.upload_hint', ['size' => App\Services\UploadLimits::label()]) }}</p>
            <label for="{{ $previewId }}-url">{{ __('Або посилання на зображення') }}</label>
            <input type="url" id="{{ $previewId }}-url" name="preview_url" value="{{ old('preview_url', $externalUrl) }}"
                placeholder="https://example.com/image.jpg" maxlength="255" data-preview-url
                @error('preview_url') aria-invalid="true" aria-describedby="{{ $previewId }}-url-error" @enderror>
            <p class="hint">{{ __('Зображення за посиланням завантажується на сервер. Мініатюри створюються автоматично.') }}</p>
            <p class="field-error" data-preview-status role="status" aria-live="polite"></p>
            @if($previewUrl)
                <label class="check"><input type="checkbox" name="remove_preview" value="1" @checked(old('remove_preview'))> {{ __('Видалити зображення') }}</label>
            @endif
            @error($previewName)<p class="field-error" id="{{ $previewId }}-error" role="alert">{{ $message }}</p>@enderror
            @error('preview_url')<p class="field-error" id="{{ $previewId }}-url-error" role="alert">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

@once
    @push('scripts')
        <style>
            .preview-layout{display:grid;grid-template-columns:minmax(180px,280px) minmax(0,1fr);gap:24px;align-items:start}
            .preview-display{display:flex;align-items:center;justify-content:center;min-height:220px;padding:12px;background:#f5f5f7;border:1px dashed #cfcfd9;border-radius:12px;overflow:hidden}
            .preview-display img{max-width:100%;max-height:280px;object-fit:contain}
            .preview-placeholder{display:flex;flex-direction:column;align-items:center;gap:12px;color:#91919d;text-align:center;font-size:14px}
            .preview-display [hidden]{display:none}.preview-controls{min-width:0}
            @media(max-width:640px){.preview-layout{grid-template-columns:minmax(0,1fr)}}
        </style>
        <script>
            document.querySelectorAll('[data-preview-field]').forEach((block) => {
                const input = block.querySelector('[data-preview-input]');
                const urlInput = block.querySelector('[data-preview-url]');
                const image = block.querySelector('[data-preview-image]');
                const placeholder = block.querySelector('[data-preview-placeholder]');
                const status = block.querySelector('[data-preview-status]');
                const remove = block.querySelector('[name="remove_preview"]');
                let objectUrl;

                const render = () => {
                    status.textContent = '';
                    if (objectUrl) URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                    let url = block.dataset.currentUrl;
                    if (input.files[0]) {
                        objectUrl = URL.createObjectURL(input.files[0]);
                        url = objectUrl;
                    } else if (urlInput.value.trim()) {
                        url = urlInput.validity.valid ? urlInput.value.trim() : '';
                    }
                    if (remove?.checked) url = '';
                    image.hidden = true;
                    placeholder.hidden = false;
                    if (url) image.src = url;
                    else image.removeAttribute('src');
                };
                image.addEventListener('load', () => {
                    image.hidden = false;
                    placeholder.hidden = true;
                });
                image.addEventListener('error', () => {
                    image.hidden = true;
                    placeholder.hidden = false;
                    status.textContent = 'Не вдалося відобразити зображення.';
                });
                input.addEventListener('change', () => {
                    if (input.files[0]) {
                        urlInput.value = '';
                        if (remove) remove.checked = false;
                    }
                    render();
                });
                urlInput.addEventListener('input', () => {
                    input.value = '';
                    if (remove) remove.checked = false;
                    render();
                });
                remove?.addEventListener('change', render);
                render();
            });
        </script>
    @endpush
@endonce
