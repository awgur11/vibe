<section class="panel product-gallery" aria-labelledby="product-gallery-title" data-product-gallery
    data-store-url="{{ route('products.pictures.store', $product) }}" data-token="{{ csrf_token() }}"
    data-product-id="{{ $product->id }}" data-reorder-url="{{ route('reorder.update', 'pictures') }}"
    data-rotate-message="{{ __('Повернути на 90° за годинниковою стрілкою') }}"
    data-rotate-error="{{ __('Не вдалося повернути фото. Спробуйте ще раз.') }}"
    data-move-message="{{ __('Перетягніть фото для зміни порядку') }}"
    data-order-saved="{{ __('Порядок збережено.') }}" data-order-error="{{ __('Не вдалося зберегти порядок. Попередній порядок відновлено.') }}"
    data-max-bytes="{{ App\Services\UploadLimits::bytes() ?? 0 }}"
    data-rotated-message="{{ __('Фото повернуто.') }}" data-deleted-message="{{ __('Фото видалено.') }}"
    data-saved-message="{{ __('Фото збережено.') }}" data-error-message="{{ __('Не вдалося завантажити фото. Спробуйте ще раз.') }}"
    data-invalid-message="{{ __('Оберіть фото у форматі JPG, PNG, GIF або WebP.') }}"
    data-size-message="{{ __('Розмір фото перевищує ліміт: :size.', ['size' => App\Services\UploadLimits::label()]) }}"
    data-url-message="{{ __('Вставте посилання на фото, що починається з http:// або https://.') }}"
    data-wait-message="{{ __('Дочекайтеся завершення завантаження фото.') }}"
    data-delete-message="{{ __('Видалити фото') }}" data-delete-confirm="{{ __('Видалити це фото?') }}"
    data-delete-error="{{ __('Не вдалося видалити фото. Спробуйте ще раз.') }}">
    <h2 id="product-gallery-title">{{ __('Фото товару') }}</h2>
    <div class="product-gallery-drop" data-gallery-drop>
        <p>{{ __('Перетягніть фото сюди або оберіть файли') }}</p>
        <label class="button secondary" for="product-gallery-files">{{ __('Обрати фото') }}</label>
        <input id="product-gallery-files" data-gallery-files type="file" hidden accept="image/jpeg,image/png,image/gif,image/webp" multiple>
        <p class="hint">JPG, PNG, GIF, WebP · {{ __('Максимальний розмір фото: :size', ['size' => App\Services\UploadLimits::label()]) }}</p>
    </div>
    <div class="product-gallery-url">
        <div class="field"><label for="product-gallery-url">{{ __('Посилання на фото') }}</label><input id="product-gallery-url" data-gallery-url type="text" inputmode="url" placeholder="https://example.com/photo.jpg"></div>
        <button class="button secondary" type="button" data-gallery-add-url>{{ __('Додати фото') }}</button>
    </div>
    <div class="product-gallery-list" data-gallery-list>
        @foreach($product->pictures as $picture)
            <div class="product-gallery-picture" data-gallery-picture data-picture-id="{{ $picture->id }}">
                <a draggable="false" href="{{ $picture->url() }}" target="_blank" rel="noopener"><img draggable="false" src="{{ $picture->url('mini') }}" alt="{{ __('Фото товару') }} {{ $loop->iteration }}" width="160" height="120" loading="lazy"></a>
                <div class="product-gallery-controls">
                    <button class="button secondary product-gallery-move" type="button" data-gallery-move title="{{ __('Перетягніть фото для зміни порядку') }}" aria-label="{{ __('Перетягніть фото для зміни порядку') }}">↕</button>
                    <button class="button secondary" type="button" data-gallery-rotate data-rotate-url="{{ route('products.pictures.rotate', ['product' => $product, 'picture' => $picture]) }}" title="{{ __('Повернути на 90° за годинниковою стрілкою') }}" aria-label="{{ __('Повернути на 90° за годинниковою стрілкою') }}">↻</button>
                    <button class="button danger product-gallery-delete" type="button" data-gallery-delete data-destroy-url="{{ route('products.pictures.destroy', ['product' => $product, 'picture' => $picture]) }}" title="{{ __('Видалити фото') }}" aria-label="{{ __('Видалити фото') }}">×</button>
                </div>
            </div>
        @endforeach
    </div>
</section>
@include('admin.details.reorder')
@once
    @push('scripts')
        <style>
            .product-gallery h2{margin-top:0}.product-gallery-drop{padding:24px;text-align:center;border:2px dashed #d9d5ee;border-radius:12px;background:#faf9ff}.product-gallery-drop.is-dragging{border-color:#6151d5;background:#eeeafd}.product-gallery-drop>p:first-child{margin-top:0}.product-gallery-drop label{cursor:pointer}.product-gallery-url{display:flex;align-items:flex-end;gap:12px;margin-top:20px}.product-gallery-url .field{flex:1;min-width:0;margin:0}.product-gallery-list{display:flow-root;margin-top:20px}.product-gallery-list>.product-gallery-picture,.product-gallery-list>.product-gallery-placeholder{float:left;width:160px;max-width:100%;margin:0 12px 12px 0}.product-gallery-picture{position:relative;min-width:0;height:174px;padding:8px;border:1px solid #e6e6eb;border-radius:10px;background:#fafafd}.product-gallery-picture img{display:block;width:100%;height:120px;object-fit:contain;border-radius:6px}.product-gallery-picture a{display:block}.product-gallery-controls{display:flex;gap:6px;margin-top:8px}.product-gallery-controls .button{width:28px;height:28px;padding:0;font-size:20px;line-height:1}.product-gallery-controls .product-gallery-move{margin-right:auto;touch-action:none;cursor:grab}.product-gallery-list .ui-sortable-helper{box-shadow:0 12px 32px #20202a33;border-color:#6151d5;cursor:grabbing}.product-gallery-placeholder{border:2px dashed #8a7bd0;border-radius:10px;background:#eeeafd;min-height:174px}.product-gallery-picture.is-error{border-color:#d58d8d}@media(max-width:540px){.product-gallery-url{align-items:stretch;flex-direction:column}}
        </style>
        <script src="{{ asset('js/product-gallery.js') }}" defer></script>
    @endpush
@endonce
