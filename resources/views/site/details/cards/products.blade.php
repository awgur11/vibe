@foreach($products as $p)
  <article class="product-card" data-id="p{{ $p->id }}" data-category="{{ $p->catalog?->slag }}" data-name="{{ $p->title ?: $p->slag }}">
    <button type="button" class="favorite-toggle" aria-label="{{ __('Добавить в избранное') }}" data-favorite="{{ $p->id }}" data-favorite-url="{{ route('favorites.store', $p) }}" aria-pressed="false" data-add-label="{{ __('Добавить в избранное') }}" data-remove-label="{{ __('Убрать из избранного') }}">
      <svg class="icon"><use href="#i-heart"/></svg>
    </button>
    <button type="button" class="compare-toggle" aria-label="{{ __('Добавить в сравнение') }}" aria-pressed="false" data-compare="{{ $p->id }}" data-compare-url="{{ route('comparison.store', $p) }}" data-add-label="{{ __('Добавить в сравнение') }}" data-remove-label="{{ __('Убрать из сравнения') }}">
      <svg class="icon"><use href="#i-chart"/></svg>
    </button>
    <a href="{{ route('product', ['product' => $p->slag]) }}" class="product-photo">
      @if($p->preview)
        <img src="{{ $p->preview('mini') }}" alt="{{ $p->title ?: $p->slag }}" loading="lazy" width="320" height="240">
      @else
        <svg class="icon product-image-placeholder" aria-hidden="true"><use href="#i-no-image"/></svg>
      @endif
    </a>
    <h3><a href="{{ route('product', ['product' => $p->slag]) }}">{{ $p->title ?: $p->slag }}</a></h3>
    <p>
    @foreach($p->fvalues->slice(0,2) as $fvalue)
      {{ $fvalue->filter?->title }}: {{ $fvalue->title }}@unless($loop->last)<br>@endunless
    @endforeach
    </p>
    <div class="product-bottom"><b>{{ $p->final_price }} грн</b>{!! $p->html_available !!}</div>
    <button type="button" class="btn btn-red add-cart" data-product-id="{{ $p->id }}" data-cart-product-url="{{ route('cart.product', $p) }}" data-id="p{{ $p->id }}" data-price="{{ $p->final_price }}" data-name="{{ $p->title ?: $p->slag }}" @disabled($p->available === 2)> <svg class="icon"><use href="#i-cart"/></svg>{{ __('В корзину') }}</button>
  </article>
@endforeach
