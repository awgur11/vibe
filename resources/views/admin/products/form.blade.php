@extends('layouts.dashboard')
@section('title', __('Редагування товару'))
@section('content')
    <div class="heading"><h1>{{ __('Редагування товару') }}</h1><a class="button secondary" href="{{ route('products.index') }}">{{ __('До списку') }}</a></div>
    <form class="form admin-form" method="POST" action="{{ route('products.update', $product) }}" id="product-form">
        @csrf @method('PUT')

        @include('admin.products.details.gallary-block')
        <div class="panel form-settings">
            <div class="field">
                <label for="catalog_id">{{ __('Каталог') }}</label>
                <select id="catalog_id" name="catalog_id" required style="width:100%;padding:11px 12px;border:1px solid #cfcfd9;border-radius:9px">
                    <option value="">{{ __('Оберіть каталог') }}</option>
                    @foreach($catalogs as $catalog)<option value="{{ $catalog->id }}" data-filters-url="{{ route('products.catalog-filters', $catalog) }}" @selected((string) old('catalog_id', $product->catalog_id) === (string) $catalog->id)>{{ $catalog->translated('title', $baseLanguage) ?? $catalog->slag }}</option>@endforeach
                </select>
                @error('catalog_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="available">{{ __('Наявність') }}</label>
                <select id="available" name="available" required style="width:100%;padding:11px 12px;border:1px solid #cfcfd9;border-radius:9px">
                    @foreach(\App\Models\Product::AVAILABILITY as $status => $label)
                        <option value="{{ $status }}" @selected((string) old('available', $product->available) === (string) $status)>{{ __($label) }}</option>
                    @endforeach
                </select>
                @error('available')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            @include('admin.details.form.input', ['name' => 'articule', 'title' => __('Артикул'), 'languages' => false, 'value' => $product])
            @include('admin.details.form.input', ['name' => 'position', 'title' => __('Позиція'), 'languages' => false, 'value' => $product, 'type' => 'number', 'min' => 0, 'required' => false, 'data' => ['max' => '4294967295']])
            @include('admin.details.form.input', ['name' => 'slag', 'title' => 'slag', 'languages' => false, 'value' => $product, 'note' => __('Якщо поле порожнє, адреса формується з назви.')])
            @include('admin.details.form.input', ['name' => 'price', 'title' => __('Ціна'), 'languages' => false, 'value' => $product, 'type' => 'number', 'min' => 0, 'required' => true, 'data' => ['step' => '0.01', 'max' => '9999999999.99']])
            @include('admin.details.form.input', ['name' => 'discount', 'title' => __('Знижка, %'), 'languages' => false, 'value' => $product, 'type' => 'number', 'min' => 0, 'required' => true, 'data' => ['step' => '0.01', 'max' => '100']])
            <div class="field"><label for="final_price">{{ __('Кінцева ціна') }}</label><input id="final_price" value="{{ $product->final_price }}" readonly><p class="hint">{{ __('Розраховується автоматично з ціни та відсотка знижки.') }}</p></div>
        </div>
        @include('admin.details.form.input', ['name' => 'title', 'title' => __('Назва'), 'value' => $product, 'required' => true])
        @include('admin.details.form.textarea', ['name' => 'description', 'title' => __('Опис'), 'value' => $product, 'rows' => 8, 'summernote' => true])
        @include('admin.details.form.input', ['name' => 'tag_title', 'title' => 'tag_title', 'value' => $product])
        @include('admin.details.form.textarea', ['name' => 'tag_description', 'title' => 'tag_description', 'value' => $product, 'rows' => 3])
        <div class="panel">
            <h2>{{ __('Фільтри та значення') }}</h2>
            <p class="hint" id="product-filters-status" role="status" aria-live="polite"></p>
            <div id="product-filters">@include('admin.products.filters', ['selectedValueIds' => old('fvalues', $errors->any() ? [] : $product->fvalues->modelKeys())])</div>
            @error('fvalues')<p class="field-error">{{ $message }}</p>@enderror
            @error('fvalues.*')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="panel form-checkboxes"><div class="field">
            <input type="hidden" name="public" value="0">
            <label class="check" for="public"><input id="public" type="checkbox" name="public" value="1" @checked(old('public', $product->public))> {{ __('Опублікувати') }}</label>
        </div></div>
        <div class="actions"><button class="button" type="submit" id="product-save">{{ __('Зберегти') }}</button><a class="button secondary" href="{{ route('products.index') }}">{{ __('Скасувати') }}</a></div>
    </form>
    @push('scripts')
        <script>
            const productForm = document.getElementById('product-form');
            const catalogSelect = document.getElementById('catalog_id');
            const productFilters = document.getElementById('product-filters');
            const filterStatus = document.getElementById('product-filters-status');
            const saveProduct = document.getElementById('product-save');
            let filtersRequest = null;
            let filtersReady = true;
            catalogSelect.addEventListener('change', async () => {
                filtersRequest?.abort();
                productFilters.replaceChildren();
                const url = catalogSelect.selectedOptions[0]?.dataset.filtersUrl;
                filtersReady = !url;
                saveProduct.disabled = Boolean(url);
                if (!url) {
                    filterStatus.textContent = {{ Illuminate\Support\Js::from(__('Оберіть каталог.')) }};
                    productFilters.removeAttribute('aria-busy');
                    return;
                }
                const controller = new AbortController();
                filtersRequest = controller;
                productFilters.setAttribute('aria-busy', 'true');
                filterStatus.textContent = {{ Illuminate\Support\Js::from(__('Завантажуємо фільтри…')) }};
                try {
                    const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin', headers: { Accept: 'text/html' } });
                    if (!response.ok || response.redirected) throw new Error('filters-failed');
                    const html = await response.text();
                    if (controller.signal.aborted) return;
                    productFilters.innerHTML = html;
                    filtersReady = true;
                    saveProduct.disabled = false;
                    filterStatus.textContent = '';
                } catch (error) {
                    if (controller.signal.aborted) return;
                    filterStatus.textContent = {{ Illuminate\Support\Js::from(__('Не вдалося завантажити фільтри. Оберіть каталог повторно.')) }};
                } finally {
                    if (!controller.signal.aborted) productFilters.removeAttribute('aria-busy');
                }
            });
            productForm.addEventListener('submit', event => { if (!filtersReady) event.preventDefault(); });
            function updateFinalPrice() {
                const price = Number(document.getElementById('price').value);
                const discount = Number(document.getElementById('discount').value);
                const cents = Math.round(price * 100);
                const basisPoints = Math.round(discount * 100);
                document.getElementById('final_price').value = Number.isFinite(price) && Number.isFinite(discount) && price >= 0 && price <= 9999999999.99 && discount >= 0 && discount <= 100
                    ? (Number((BigInt(cents) * BigInt(10000 - basisPoints) + 5000n) / 10000n) / 100).toFixed(2) : '';
            }
            document.getElementById('price').addEventListener('input', updateFinalPrice);
            document.getElementById('discount').addEventListener('input', updateFinalPrice);
            updateFinalPrice();
        </script>
    @endpush
@endsection
