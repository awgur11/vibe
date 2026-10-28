@extends('layouts.dashboard')
@section('title', __('Фільтри'))
@section('content')
    <div class="heading"><div><h1>{{ __('Фільтри') }}</h1><p>{{ __('Керування фільтрами каталогів') }}</p></div><button class="button filter-icon-button" type="button" data-filter-create title="{{ __('Додати фільтр') }}" aria-label="{{ __('Додати фільтр') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></button></div>
    <form method="GET" action="{{ route('filters.index') }}" style="width:370px;max-width:100%;margin-bottom:20px">
        <div class="field">
            <label for="filter-search">{{ __('Пошук фільтрів') }}</label>
            <input id="filter-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="{{ __('Назва або адреса фільтра') }}">
        </div>
        <div class="actions">
            <button class="button" type="submit">{{ __('Знайти') }}</button>
            @if($search !== '')<a class="button secondary" href="{{ route('filters.index') }}">{{ __('Скинути') }}</a>@endif
        </div>
    </form>
    <div class="filters-board" data-reorder data-reorder-url="{{ route('reorder.update', ['resource' => 'filters']) }}" data-reorder-token="{{ csrf_token() }}">
        <p class="hint" data-reorder-status role="status" aria-live="polite"></p>
        <div class="filter-list" data-reorder-list role="list" aria-label="{{ __('Фільтри') }}">
            @forelse($filters as $filter)
                @php($valuesExpanded = (string) request('expanded') === (string) $filter->id || ($errors->fvalue->any() && (int) old('_owner_filter_id') === $filter->id))
                <div class="filter-group" role="listitem" data-reorder-id="{{ $filter->id }}">
                    <div class="filter-card">
                        <button type="button" class="button secondary filter-icon-button" data-reorder-handle title="{{ __('Перетягуйте рядки') }}" aria-label="{{ __('Перемістити фільтр') }} {{ $filter->translated('title', $baseLanguage) }}" style="touch-action:none;cursor:grab"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18M8 7l4-4 4 4M8 17l4 4 4-4"/></svg></button>
                        <span class="filter-card-title">{{ $filter->translated('title', $baseLanguage) ?? $filter->slag }}</span>
                        <div class="actions">
                            <button class="button secondary filter-icon-button filter-values-toggle" type="button" title="{{ __('Значення фільтра') }}" aria-label="{{ __('Значення фільтра') }}" data-fvalues-toggle data-values-url="{{ route('filters.values', ['filter' => $filter, 'page' => request('page')]) }}" aria-expanded="{{ $valuesExpanded ? 'true' : 'false' }}" aria-controls="fvalues-{{ $filter->id }}"><span class="filter-values-count">{{ $filter->fvalues_count }}</span> <span data-fvalues-arrow aria-hidden="true">{{ $valuesExpanded ? '▾' : '▸' }}</span></button>
                        <button type="button" class="button secondary filter-icon-button" data-fvalue-create data-owner-filter-id="{{ $filter->id }}" title="{{ __('Додати значення') }}" aria-label="{{ __('Додати значення') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></button>
                        <button type="button" class="button secondary filter-icon-button" data-filter-edit data-filter-id="{{ $filter->id }}" title="{{ __('Редагувати') }}" aria-label="{{ __('Редагувати') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/></svg></button>
                        <form method="POST" action="{{ route('filters.destroy', $filter) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Видалити цей фільтр?')) }})">@csrf @method('DELETE')<button class="button danger filter-icon-button" type="submit" title="{{ __('Видалити') }}" aria-label="{{ __('Видалити') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg></button></form>
                        </div>
                    </div>
                    <div id="fvalues-{{ $filter->id }}" class="filter-values" @if(!$valuesExpanded) hidden @endif></div>
                </div>
            @empty
                <p class="filter-empty">{{ $search !== '' ? __('Фільтрів не знайдено.') : __('Фільтри ще не додані.') }}</p>
            @endforelse
        </div>
        @if($filters->hasPages())
            <nav class="pagination" aria-label="{{ __('Сторінки фільтрів') }}">
                @if($filters->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $filters->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $filters->currentPage() }} / {{ $filters->lastPage() }}</span>
                @if($filters->hasMorePages())<a href="{{ $filters->nextPageUrl() }}">{{ __('Далі') }}</a>@else<span>{{ __('Далі') }}</span>@endif
            </nav>
        @endif
    </div>
    <dialog id="filter-dialog" aria-labelledby="filter-dialog-title">
        <form method="POST" action="{{ old('_filter_id') ? route('filters.update', ['filter' => (int) old('_filter_id'), 'page' => request('page')]) : route('filters.store') }}" id="filter-form">
            @csrf
            <input type="hidden" name="_method" value="{{ old('_filter_id') ? 'PUT' : 'POST' }}">
            <input type="hidden" name="_filter_id" value="{{ old('_filter_id') }}">
            <h2 id="filter-dialog-title">{{ old('_filter_id') ? __('Редагування фільтра') : __('Новий фільтр') }}</h2>
            @include('admin.details.form.input', ['name' => 'title', 'title' => __('Заголовок'), 'required' => true])
            @include('admin.details.form.input', ['name' => 'slag', 'title' => 'slag', 'languages' => false, 'note' => __('Якщо поле порожнє, адреса формується з назви.')])
            @include('admin.details.form.input', ['name' => 'position', 'title' => __('Позиція'), 'languages' => false, 'type' => 'number', 'min' => 0, 'data' => ['max' => 4294967295]])
            <div class="actions"><button class="button filter-icon-button" type="submit" title="{{ __('Зберегти') }}" aria-label="{{ __('Зберегти') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h12l4 4v14H3V3Z"/><path d="M7 3v6h10V3M7 21v-8h10v8"/></svg></button><button class="button secondary filter-icon-button" type="button" data-filter-cancel title="{{ __('Скасувати') }}" aria-label="{{ __('Скасувати') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button></div>
        </form>
    </dialog>
    @include('admin.filters.fvalue-dialog')
    @include('admin.details.reorder')
    @push('scripts')
        <style>
            .filters-board{max-width:100%}.filter-list{width:370px;max-width:100%;display:flex;flex-direction:column;gap:12px}.filter-group{width:370px;max-width:100%}.filter-card,.filter-value-card{width:350px;max-width:100%;display:flex;align-items:center;gap:8px;padding:12px;background:#fff;border:1px solid #e6e6eb;border-radius:12px}.filter-card-title{flex:1;min-width:0;overflow-wrap:anywhere;font-size:14px;font-weight:600}.filter-card .actions,.filter-value-card .actions{flex-shrink:0;flex-wrap:nowrap}.filters-board .filter-values{width:350px;min-width:0;max-width:calc(100% - 20px);margin:8px 0 0 20px}.filter-value-list{display:flex;flex-direction:column;gap:8px}.filter-value-card{font-size:14px}.filter-values [data-reorder-status]:empty,.filters-board>[data-reorder-status]:empty{display:none}.filter-empty{margin:0;padding:12px;background:#fff;border:1px solid #e6e6eb;border-radius:12px}.filters-board>.pagination{width:370px;max-width:100%}.filters-board .filter-list>.ui-sortable-helper{display:block;border:0;background:transparent}.filters-board .filter-value-list>.ui-sortable-helper{display:flex}.filters-board .reorder-placeholder{border:2px dashed #8a7bd0;border-radius:12px;min-height:56px}
            .button.filter-icon-button{width:30px;height:30px;padding:5px;gap:4px;flex-shrink:0}.button.filter-values-toggle{width:auto;min-width:30px;font-size:12px}.filter-values-count{font-variant-numeric:tabular-nums}.filter-values-toggle [data-fvalues-arrow]{font-size:14px}.actions:has(.filter-icon-button){gap:5px}#filter-dialog{width:min(800px,calc(100vw - 32px));max-height:calc(100vh - 48px);overflow:auto;padding:24px;border:1px solid #e6e6eb;border-radius:16px;color:#20202a;box-shadow:0 20px 80px #20202a33}#filter-dialog::backdrop{background:#20202a80}#filter-dialog h2{margin-top:0}</style>
        <script>
            const filterDialog = document.getElementById('filter-dialog');
            const filterForm = document.getElementById('filter-form');
            const filterRecords = {{ Illuminate\Support\Js::from($filters->mapWithKeys(fn ($filter) => [$filter->id => ['url' => route('filters.update', ['filter' => $filter, 'page' => request('page')]), 'slag' => $filter->slag, 'position' => $filter->position, 'titles' => $filter->translations->pluck('title', 'lacale')]])) }};
            document.querySelector('[data-reorder]').addEventListener('reorder:saved', event => {
                event.detail.ids.forEach((id, index) => filterRecords[id].position = {{ ($filters->currentPage() - 1) * $filters->perPage() }} + index);
            });
            function openFilterDialog(id = null) {
                const record = id ? filterRecords[id] : null;
                filterForm.action = record ? record.url : {{ Illuminate\Support\Js::from(route('filters.store')) }};
                filterForm.elements._method.value = record ? 'PUT' : 'POST';
                filterForm.elements._filter_id.value = id ?? '';
                filterForm.elements.slag.value = record?.slag ?? '';
                filterForm.elements.position.value = record?.position ?? '';
                filterForm.querySelectorAll('[data-locale]').forEach(input => input.value = record?.titles[input.dataset.locale] ?? '');
                filterForm.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
                filterForm.querySelectorAll('.field-error').forEach(error => error.hidden = true);
                filterForm.querySelectorAll('[data-translation-status]').forEach(status => status.textContent = '');
                document.getElementById('filter-dialog-title').textContent = record ? {{ Illuminate\Support\Js::from(__('Редагування фільтра')) }} : {{ Illuminate\Support\Js::from(__('Новий фільтр')) }};
                filterDialog.showModal();
            }
            document.querySelector('[data-filter-create]').addEventListener('click', () => openFilterDialog());
            document.querySelectorAll('[data-filter-edit]').forEach(button => button.addEventListener('click', () => openFilterDialog(button.dataset.filterId)));
            document.querySelector('[data-filter-cancel]').addEventListener('click', () => filterDialog.close());
            @if($errors->any()) filterDialog.showModal(); @endif
        </script>
    @endpush
@endsection
