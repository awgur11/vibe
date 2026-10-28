<div id="fvalues-{{ $filter->id }}" class="filter-values" data-reorder data-reorder-url="{{ route('reorder.update', ['resource' => 'fvalues']) }}" data-reorder-token="{{ csrf_token() }}" data-reorder-scope='@json(["filter_id" => $filter->id])' @if(!$valuesExpanded) hidden @endif>
    <p class="hint" data-reorder-status role="status" aria-live="polite"></p>
    <div class="filter-value-list" data-reorder-list role="list" aria-label="{{ __('Значення фільтра') }}: {{ $filter->translated('title', $baseLanguage) ?? $filter->slag }}">
        @forelse($values as $fvalue)
            <div class="filter-value-card" role="listitem" data-reorder-id="{{ $fvalue->id }}">
                <button type="button" class="button secondary filter-icon-button" data-reorder-handle title="{{ __('Перетягуйте рядки') }}" aria-label="{{ __('Перемістити значення') }} {{ $fvalue->translated('title', $baseLanguage) }}" style="touch-action:none;cursor:grab"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18M8 7l4-4 4 4M8 17l4 4 4-4"/></svg></button>
                <span class="filter-card-title">{{ $fvalue->translated('title', $baseLanguage) ?? $fvalue->slag }}</span>
                <div class="actions">
                    <button type="button" class="button secondary filter-icon-button" data-fvalue-edit data-owner-filter-id="{{ $filter->id }}" data-fvalue-id="{{ $fvalue->id }}" title="{{ __('Редагувати') }}" aria-label="{{ __('Редагувати') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/></svg></button>
                    <form method="POST" action="{{ route('filters.fvalues.destroy', ['filter' => $filter, 'fvalue' => $fvalue, 'page' => request('page')]) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Видалити це значення?')) }})">@csrf @method('DELETE')<button class="button danger filter-icon-button" type="submit" title="{{ __('Видалити') }}" aria-label="{{ __('Видалити') }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg></button></form>
                </div>
            </div>
        @empty
            <p class="filter-empty">{{ __('Значення ще не додані.') }}</p>
        @endforelse
    </div>
    <p class="hint" data-values-load-status role="status" aria-live="polite"></p>
    @if($values->hasMorePages())
        <button class="button secondary" type="button" data-values-load-more data-next-url="{{ $values->nextPageUrl() }}">{{ __('Завантажити ще') }}</button>
    @endif
</div>
