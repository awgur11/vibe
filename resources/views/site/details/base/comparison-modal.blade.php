<dialog id="comparisonDialog" class="comparison-dialog" aria-labelledby="comparison-title" data-comparison-ui data-state-url="{{ route('comparison.state') }}" data-error="{{ __('Не удалось обновить сравнение. Попробуйте ещё раз.') }}">
    <div class="comparison-dialog-heading"><h2 id="comparison-title">{{ __('Сравнение товаров') }}</h2><button type="button" class="btn" data-comparison-close aria-label="{{ __('Закрыть') }}">×</button></div>
    <p data-comparison-status role="status"></p>
    <p data-comparison-empty hidden>{{ __('В сравнении пока нет товаров.') }}</p>
    <div class="comparison-categories" data-comparison-categories></div>
</dialog>
