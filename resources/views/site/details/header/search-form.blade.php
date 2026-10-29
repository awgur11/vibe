<div class="header-search-wrap" data-site-search data-suggestions-url="{{ route('search.suggestions') }}" data-error="{{ __('Не удалось выполнить поиск. Попробуйте ещё раз.') }}" data-empty="{{ __('Товары не найдены.') }}">
    <form class="header-search" id="headerSearch" role="search" method="GET" action="{{ route('search') }}">
        <svg class="icon" aria-hidden="true"><use href="#i-search"/></svg>
        <input id="siteSearch" name="q" type="search" value="{{ request()->routeIs('search') ? $query : '' }}" maxlength="200" placeholder="{{ __('Поиск товаров по названию') }}" aria-label="{{ __('Поиск товаров') }}" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="searchSuggestions">
        <button type="submit" aria-label="{{ __('Найти') }}"><svg class="icon"><use href="#i-search"/></svg></button>
    </form>
    <div class="search-suggestions" id="searchSuggestions" hidden>
        <p data-search-status role="status"></p>
        <div data-search-results role="listbox" aria-label="{{ __('Результаты поиска') }}"></div>
        <a class="search-all" data-search-all href="{{ route('search') }}">{{ __('Все результаты') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
    </div>
</div>
