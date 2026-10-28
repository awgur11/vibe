@php
    $valueErrors = $errors->getBag('fvalue');
    $ownerFilterId = $valueErrors->any() ? (int) old('_owner_filter_id') : null;
    $editingValueId = $valueErrors->any() ? (int) old('_fvalue_id') : null;
    $valueFormAction = $ownerFilterId ? ($editingValueId
        ? route('filters.fvalues.update', ['filter' => $ownerFilterId, 'fvalue' => $editingValueId, 'page' => request('page')])
        : route('filters.fvalues.store', ['filter' => $ownerFilterId, 'page' => request('page')])) : '#';
@endphp
<dialog id="fvalue-dialog" aria-labelledby="fvalue-dialog-title">
    <form method="POST" action="{{ $valueFormAction }}" id="fvalue-form">
        @csrf
        <input type="hidden" name="_method" value="{{ $editingValueId ? 'PUT' : 'POST' }}">
        <input type="hidden" name="_owner_filter_id" value="{{ $ownerFilterId }}">
        <input type="hidden" name="_fvalue_id" value="{{ $editingValueId }}">
        <h2 id="fvalue-dialog-title">{{ $editingValueId ? __('Редагування значення') : __('Нове значення фільтра') }}</h2>
        @if($valueErrors->any())<div class="errors" role="alert"><ul>@foreach($valueErrors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
        @foreach($site_languages as $language)
            @php($titleError = 'languages.'.$language->id.'.title')
            <div class="field">
                <label for="fvalue-title-{{ $language->id }}">{{ __('Заголовок') }} — {{ $language->title }} @if($language->isBase())<span class="badge base">{{ __('Базовый') }}</span>@endif</label>
                <input id="fvalue-title-{{ $language->id }}" name="languages[{{ $language->id }}][title]" data-locale="{{ $language->locale }}" value="{{ $valueErrors->any() ? old($titleError) : '' }}" maxlength="255" @required($language->isBase()) @if($valueErrors->has($titleError)) aria-invalid="true" @endif>
            </div>
        @endforeach
        <div class="field"><label for="fvalue-slag">slag</label><input id="fvalue-slag" name="slag" maxlength="255" value="{{ $valueErrors->any() ? old('slag') : '' }}"><p class="hint">{{ __('Якщо поле порожнє, адреса формується з назви.') }}</p></div>
        <div class="field"><label for="fvalue-position">{{ __('Позиція') }}</label><input id="fvalue-position" type="number" name="position" min="0" max="4294967295" value="{{ $valueErrors->any() ? old('position') : '' }}"></div>
        <div class="actions"><button class="button filter-icon-button" type="submit" title="{{ __('Зберегти') }}" aria-label="{{ __('Зберегти') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h12l4 4v14H3V3Z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/></svg></button><button class="button secondary filter-icon-button" type="button" data-fvalue-cancel title="{{ __('Скасувати') }}" aria-label="{{ __('Скасувати') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button></div>
    </form>
</dialog>
@push('scripts')
    <style>.filter-values[hidden]{display:none}#fvalue-dialog{width:min(640px,calc(100vw - 32px));max-height:calc(100vh - 48px);overflow:auto;padding:24px;border:1px solid #e6e6eb;border-radius:16px;color:#20202a;box-shadow:0 20px 80px #20202a33}#fvalue-dialog::backdrop{background:#20202a80}#fvalue-dialog h2{margin-top:0}</style>
    <script>
        const fvalueDialog = document.getElementById('fvalue-dialog');
        const fvalueForm = document.getElementById('fvalue-form');
        const fvalueRecords = {{ Illuminate\Support\Js::from($filters->getCollection()->mapWithKeys(fn ($filter) => [$filter->id => ['storeUrl' => route('filters.fvalues.store', ['filter' => $filter, 'page' => request('page')]), 'values' => (object) []]])) }};
        function openFvalueDialog(filterId, valueId = null) {
            const owner = fvalueRecords[filterId];
            const record = valueId ? owner.values[valueId] : null;
            fvalueForm.action = record ? record.url : owner.storeUrl;
            fvalueForm.elements._method.value = record ? 'PUT' : 'POST';
            fvalueForm.elements._owner_filter_id.value = filterId;
            fvalueForm.elements._fvalue_id.value = valueId ?? '';
            fvalueForm.elements.slag.value = record?.slag ?? '';
            fvalueForm.elements.position.value = record?.position ?? '';
            fvalueForm.querySelectorAll('[data-locale]').forEach(input => input.value = record?.titles[input.dataset.locale] ?? '');
            fvalueForm.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
            fvalueForm.querySelectorAll('.errors').forEach(error => error.hidden = true);
            document.getElementById('fvalue-dialog-title').textContent = record ? {{ Illuminate\Support\Js::from(__('Редагування значення')) }} : {{ Illuminate\Support\Js::from(__('Нове значення фільтра')) }};
            fvalueDialog.showModal();
        }
        document.querySelectorAll('[data-fvalue-create]').forEach(button => button.addEventListener('click', () => openFvalueDialog(button.dataset.ownerFilterId)));
        document.addEventListener('click', event => {
            const button = event.target.closest('[data-fvalue-edit]');
            if (button) openFvalueDialog(button.dataset.ownerFilterId, button.dataset.fvalueId);
        });
        document.querySelector('[data-fvalue-cancel]').addEventListener('click', () => fvalueDialog.close());
        async function loadFilterValues(button) {
            const container = document.getElementById(button.getAttribute('aria-controls'));
            if (container.dataset.loaded || button.disabled) return;
            button.disabled = true;
            container.setAttribute('aria-busy', 'true');
            const status = document.createElement('p');
            status.className = 'hint';
            status.setAttribute('role', 'status');
            status.textContent = {{ Illuminate\Support\Js::from(__('Завантаження…')) }};
            container.replaceChildren(status);
            try {
                const response = await fetch(button.dataset.valuesUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok || response.redirected) throw new Error('loading-failed');
                const data = await response.json();
                const template = document.createElement('template');
                template.innerHTML = data.html.trim();
                const values = template.content.firstElementChild;
                values.dataset.loaded = 'true';
                container.replaceWith(values);
                const filterId = JSON.parse(values.dataset.reorderScope).filter_id;
                fvalueRecords[filterId].values = data.values;
                values.addEventListener('reorder:saved', event => {
                    event.stopPropagation();
                    event.detail.ids.forEach((id, index) => fvalueRecords[filterId].values[id].position = index);
                });
                window.initializeAdminReorder?.(values.parentElement);
            } catch (error) {
                status.textContent = {{ Illuminate\Support\Js::from(__('Не вдалося завантажити значення.')) }};
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'button secondary';
                retry.textContent = {{ Illuminate\Support\Js::from(__('Повторити завантаження')) }};
                retry.addEventListener('click', () => loadFilterValues(button));
                container.append(retry);
            } finally {
                button.disabled = false;
                container.removeAttribute('aria-busy');
            }
        }
        document.addEventListener('click', async event => {
            const button = event.target.closest('[data-values-load-more]');
            if (!button || button.disabled) return;
            const container = button.closest('.filter-values');
            if (container.getAttribute('aria-busy') === 'true') return;
            const status = container.querySelector('[data-values-load-status]');
            const list = container.querySelector('[data-reorder-list]');
            const filterId = JSON.parse(container.dataset.reorderScope).filter_id;
            button.disabled = true;
            container.dataset.valuesLoading = 'true';
            container.setAttribute('aria-busy', 'true');
            const sortable = window.jQuery?.(list);
            if (sortable?.hasClass('ui-sortable')) sortable.sortable('disable');
            status.textContent = {{ Illuminate\Support\Js::from(__('Завантаження…')) }};
            try {
                const response = await fetch(button.dataset.nextUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok || response.redirected) throw new Error('loading-failed');
                const data = await response.json();
                const template = document.createElement('template');
                template.innerHTML = data.html.trim();
                const loadedIds = new Set(Array.from(list.querySelectorAll('[data-reorder-id]'), row => row.dataset.reorderId));
                template.content.querySelectorAll('.filter-value-list > [data-reorder-id]').forEach(row => {
                    if (!loadedIds.has(row.dataset.reorderId)) list.append(row);
                });
                Object.assign(fvalueRecords[filterId].values, data.values);
                if (data.next_url) button.dataset.nextUrl = data.next_url;
                else button.remove();
                if (sortable?.hasClass('ui-sortable')) sortable.sortable('refresh');
                status.textContent = '';
            } catch (error) {
                status.textContent = {{ Illuminate\Support\Js::from(__('Не вдалося завантажити значення. Спробуйте ще раз.')) }};
            } finally {
                button.disabled = false;
                delete container.dataset.valuesLoading;
                container.removeAttribute('aria-busy');
                if (sortable?.hasClass('ui-sortable')) sortable.sortable('enable');
            }
        });
        document.querySelectorAll('[data-fvalues-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const expanded = button.getAttribute('aria-expanded') !== 'true';
                button.setAttribute('aria-expanded', String(expanded));
                button.querySelector('[data-fvalues-arrow]').textContent = expanded ? '▾' : '▸';
                document.getElementById(button.getAttribute('aria-controls')).hidden = !expanded;
                if (expanded) loadFilterValues(button);
            });
            if (button.getAttribute('aria-expanded') === 'true') loadFilterValues(button);
        });
        @if($valueErrors->any()) fvalueDialog.showModal(); @endif
    </script>
@endpush
