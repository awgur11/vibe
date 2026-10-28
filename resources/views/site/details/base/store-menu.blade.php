<div class="site-store-menu border-bottom">
    <div class="container site-store-menu-inner">
        <button class="btn site-catalog-button" type="button">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            {{ __('КАТАЛОГ') }}
        </button>
        <div class="site-store-search">@include('site.details.header.search-form')</div>
        <div class="site-store-actions">
            <button class="site-store-icon" type="button" title="{{ __('Обране') }}" aria-label="{{ __('Обране') }}">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
            </button>
            <button class="site-store-icon" type="button" title="{{ __('Порівняти') }}" aria-label="{{ __('Порівняти') }}">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 20V10M12 20V4M19 20V7"/></svg>
            </button>
            <button class="site-store-icon" type="button" title="{{ __('Кошик') }}" aria-label="{{ __('Кошик') }}">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h3l3 12h11l3-9H6M8 15l-1 3h12"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg>
            </button>
        </div>
    </div>
</div>
