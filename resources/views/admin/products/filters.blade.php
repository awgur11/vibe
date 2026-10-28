@forelse($filters as $filter)
    <fieldset class="language-fields">
        <legend>{{ $filter->translated('title', $baseLanguage) ?? $filter->slag }}</legend>
        @forelse($filter->fvalues as $fvalue)
            <div class="field"><label class="check"><input type="checkbox" name="fvalues[]" value="{{ $fvalue->id }}" @checked(in_array($fvalue->id, $selectedValueIds))> {{ $fvalue->translated('title', $baseLanguage) ?? $fvalue->slag }}</label></div>
        @empty
            <p class="hint">{{ __('У каталозі немає доступних значень цього фільтра.') }}</p>
        @endforelse
    </fieldset>
@empty
    <p class="hint">{{ __('Оберіть каталог із налаштованими фільтрами.') }}</p>
@endforelse
