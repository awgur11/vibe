@extends('layouts.dashboard')
@section('title', __('Редагування каталогу'))
@section('content')
    <div class="heading"><h1>{{ __('Редагування каталогу') }}</h1><a class="button secondary" href="{{ route('catalogs.index') }}">{{ __('До списку') }}</a></div>
    <form class="form admin-form" method="POST" enctype="multipart/form-data" action="{{ route('catalogs.update', $catalog) }}">
        @csrf @method('PUT')
        @include('admin.details.form.preview', ['name' => 'preview', 'title' => __('Зображення каталогу'), 'value' => $catalog])
        <div class="panel form-settings">
            <div class="field">
                <label for="parent_id">{{ __('Батьківський каталог') }}</label>
                <select id="parent_id" name="parent_id" style="width: 100%; padding: 11px 12px; border: 1px solid #cfcfd9; border-radius: 9px">
                    <option value="">{{ __('Кореневий розділ') }}</option>
                    @foreach($parents as $candidate)<option value="{{ $candidate->id }}" @selected((string) old('parent_id', $catalog->parent_id) === (string) $candidate->id)>{{ $candidate->translated('title', $baseLanguage) ?? $candidate->slag }}</option>@endforeach
                </select>
                @error('parent_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            @include('admin.details.form.input', ['name' => 'slag', 'title' => __('Адреса каталогу (slag)'), 'languages' => false, 'value' => $catalog, 'note' => __('Латинські літери, цифри та дефіси. Якщо поле порожнє, адреса формується з назви.')])
        </div>
        @include('admin.details.form.input', ['name' => 'title', 'title' => __('Заголовок'), 'value' => $catalog, 'required' => true])
        @include('admin.details.form.textarea', ['name' => 'short_content', 'title' => __('Короткий опис'), 'value' => $catalog, 'rows' => 3])
        @include('admin.details.form.textarea', ['name' => 'content', 'title' => __('Вміст'), 'value' => $catalog, 'rows' => 8, 'summernote' => true])
        @include('admin.details.form.input', ['name' => 'tag_title', 'title' => 'tag_title', 'value' => $catalog])
        @include('admin.details.form.textarea', ['name' => 'tag_description', 'title' => 'tag_description', 'value' => $catalog, 'rows' => 3])
        <div class="panel">
            <h2>{{ __('Фільтри') }}</h2>
            <p id="catalog-filters-status" class="hint" role="status" aria-live="polite"></p>
            <button type="button" class="button secondary" id="catalog-filters-retry" hidden>{{ __('Повторити завантаження') }}</button>
            <div id="catalog-filters"></div>
            @error('fvalues')<p class="field-error">{{ $message }}</p>@enderror
            @error('fvalues.*')<p class="field-error">{{ $message }}</p>@enderror
            @error('filters')<p class="field-error">{{ $message }}</p>@enderror
            @error('filters.*')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="panel form-checkboxes"><div class="field">
            <input type="hidden" name="public" value="0">
            <label class="check" for="public"><input id="public" type="checkbox" name="public" value="1" @checked(old('public', $catalog->public))> {{ __('Опублікувати') }}</label>
        </div><div class="field">
            <input type="hidden" name="popular" value="0">
            <label class="check" for="popular"><input id="popular" type="checkbox" name="popular" value="1" @checked(old('popular', $catalog->popular))> {{ __('Популярний') }}</label>
        </div></div>
        <div class="actions"><button class="button" type="submit">{{ __('Зберегти') }}</button><a class="button secondary" href="{{ route('catalogs.index', ['parent_id' => $catalog->parent_id]) }}">{{ __('Скасувати') }}</a></div>
    </form>
    @push('scripts')
        <style>
            .catalog-filter-heading-row{display:inline-flex;align-items:center;gap:8px}
            .catalog-filter-heading-row > .check{margin:0}
            .catalog-filter-values[hidden]{display:none}
            .button.catalog-filter-values-button{width:28px;height:28px;padding:5px;flex-shrink:0}
        </style>
        <script>
            const filtersContainer = document.getElementById('catalog-filters');
            const filtersStatus = document.getElementById('catalog-filters-status');
            const retryButton = document.getElementById('catalog-filters-retry');
            const catalogForm = filtersContainer.closest('form');
            const selectedFilters = new Set({{ Illuminate\Support\Js::from(old('filters', $errors->any() ? [] : $catalog->filters->modelKeys())) }}.map(String));
            const selectedValues = new Set({{ Illuminate\Support\Js::from(old('fvalues', $errors->any() ? [] : $catalog->fvalues->modelKeys())) }}.map(String));
            const savedValues = {{ Illuminate\Support\Js::from($selectedValueRecords->map(fn ($value) => ['id' => $value->id, 'filter_id' => $value->filter_id])) }};
            let filtersReady = false;
            const loadLabel = {{ Illuminate\Support\Js::from(__('Завантажити значення')) }};
            const loadingLabel = {{ Illuminate\Support\Js::from(__('Завантаження…')) }};
            const errorLabel = {{ Illuminate\Support\Js::from(__('Не вдалося завантажити. Спробуйте ще раз.')) }};

            async function fetchRecords(url) {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok || response.redirected) throw new Error('loading-failed');
                return response.json();
            }

            function createInput(name, id, type = 'checkbox') {
                const input = document.createElement('input');
                input.type = type;
                input.name = name;
                input.value = id;
                return input;
            }

            async function loadFilters() {
                filtersStatus.textContent = loadingLabel;
                retryButton.hidden = true;
                try {
                    const filters = await fetchRecords({{ Illuminate\Support\Js::from(route('catalogs.filters')) }});
                    filtersContainer.replaceChildren();
                    filters.forEach(filter => {
                        const fieldset = document.createElement('fieldset');
                        fieldset.className = 'language-fields';
                        const legend = document.createElement('legend');
                        const label = document.createElement('label');
                        label.className = 'check';
                        const toggle = createInput('filters[]', filter.id);
                        toggle.checked = selectedFilters.has(String(filter.id));
                        label.append(toggle, document.createTextNode(' ' + filter.title));
                        const headingRow = document.createElement('span');
                        headingRow.className = 'catalog-filter-heading-row';
                        headingRow.append(label);
                        legend.append(headingRow);
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'button secondary catalog-filter-values-button';
                        button.title = loadLabel;
                        button.setAttribute('aria-label', loadLabel + ': ' + filter.title);
                        button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M5 16v5h14v-5"/></svg>';
                        legend.className = 'catalog-filter-heading';
                        headingRow.append(button);
                        button.setAttribute('aria-expanded', 'false');
                        const status = document.createElement('p');
                        status.className = 'hint';
                        status.setAttribute('role', 'status');
                        const values = document.createElement('div');
                        values.className = 'catalog-filter-values';
                        values.id = 'catalog-filter-values-' + filter.id;
                        button.setAttribute('aria-controls', values.id);
                        let valuesLoaded = false;
                        function setExpanded(expanded) {
                            values.hidden = !expanded;
                            status.hidden = !expanded;
                            button.setAttribute('aria-expanded', String(expanded));
                            const title = expanded
                                ? {{ Illuminate\Support\Js::from(__('Приховати значення')) }}
                                : {{ Illuminate\Support\Js::from(__('Показати значення')) }};
                            button.title = title;
                            button.setAttribute('aria-label', title + ': ' + filter.title);
                        }
                        values.hidden = true;
                        savedValues.filter(value => String(value.filter_id) === String(filter.id) && selectedValues.has(String(value.id))).forEach(value => {
                            const input = createInput('fvalues[]', value.id, 'hidden');
                            input.disabled = !toggle.checked;
                            values.append(input);
                        });
                        toggle.addEventListener('change', () => {
                            values.querySelectorAll('input').forEach(input => {
                                input.disabled = !toggle.checked;
                                if (!toggle.checked) {
                                    input.checked = false;
                                    selectedValues.delete(String(input.value));
                                    if (input.type === 'hidden') input.remove();
                                }
                            });
                        });
                        button.addEventListener('click', async () => {
                            if (valuesLoaded) {
                                setExpanded(values.hidden);
                                return;
                            }
                            status.hidden = false;
                            button.disabled = true;
                            status.textContent = loadingLabel;
                            try {
                                const records = await fetchRecords(filter.values_url);
                                const content = document.createDocumentFragment();
                                records.forEach(value => {
                                    const row = document.createElement('div');
                                    row.className = 'field';
                                    const label = document.createElement('label');
                                    label.className = 'check';
                                    const input = createInput('fvalues[]', value.id);
                                    input.checked = toggle.checked && selectedValues.has(String(value.id));
                                    input.disabled = !toggle.checked;
                                    label.append(input, document.createTextNode(' ' + value.title));
                                    row.append(label);
                                    content.append(row);
                                });
                                values.replaceChildren(content);
                                status.textContent = records.length ? '' : {{ Illuminate\Support\Js::from(__('Значення ще не додані.')) }};
                                valuesLoaded = true;
                                setExpanded(true);
                            } catch (error) {
                                status.textContent = errorLabel;
                            } finally {
                                button.disabled = false;
                            }
                        });
                        fieldset.append(legend, status, values);
                        filtersContainer.append(fieldset);
                    });
                    filtersReady = true;
                    filtersStatus.textContent = filters.length ? '' : {{ Illuminate\Support\Js::from(__('Фільтри ще не додані.')) }};
                } catch (error) {
                    filtersStatus.textContent = errorLabel;
                    retryButton.hidden = false;
                }
            }
            catalogForm.addEventListener('submit', event => {
                if (!filtersReady) {
                    event.preventDefault();
                    filtersStatus.scrollIntoView({ block: 'center' });
                }
            });
            retryButton.addEventListener('click', loadFilters);
            loadFilters();
        </script>
    @endpush
@endsection
