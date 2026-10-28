<section class="section" id="products" aria-labelledby="productsTitle">
      <div class="container">
        <div class="section-head"><h2 id="productsTitle">{{ __('Популярные товары') }}</h2><a href="#products" class="section-more" id="showAllProducts" data-expand-label="{{ __('Смотреть все товары') }}" data-collapse-label="{{ __('Свернуть список') }}">{{ __('Смотреть все товары') }} <svg class="icon"><use href="#i-arrow"/></svg></a></div>
        <div class="product-filters" role="group" aria-label="{{ __('Фильтр популярных товаров') }}">
          <button type="button" class="filter-btn is-active" data-filter="all" aria-pressed="true">{{ __('Все товары') }}</button>
        @foreach($products->unique('catalog_id') as $p)
          <button type="button" class="filter-btn" data-filter="{{ $p->catalog->slag }}" aria-pressed="false">{{ $p->catalog->title }}</button>
        @endforeach
        </div>
        <div class="products-grid" id="productsGrid">
        @foreach($products as $p)
          <article class="product-card" data-id="p{{ $p->id }}" data-category="{{ $p->catalog->slag }}" data-name="{{ $p->title }}">
            <button type="button" class="favorite-toggle" aria-label="{{ __('Добавить в избранное') }}" data-favorite="p{{ $p->id }}" aria-pressed="false" data-add-label="{{ __('Добавить в избранное') }}" data-remove-label="{{ __('Убрать из избранного') }}">
              <svg class="icon"><use href="#i-heart"/></svg>
            </button>
            <button type="button" class="compare-toggle" aria-label="{{ __('Добавить в сравнение') }}" aria-pressed="false" data-compare="p{{ $p->id }}" data-add-label="{{ __('Добавить в сравнение') }}" data-remove-label="{{ __('Убрать из сравнения') }}">
              <svg class="icon"><use href="#i-chart"/></svg>
            </button>
            <div class="product-photo">
              <img src="{{ $p->preview }}" alt="{{ $p->title }}" loading="lazy">
            </div>
            <h3>{{ $p->title }}</h3>
            <p>
            @foreach($p->fvalues->slice(0,2) as $fvalue)
              {!! $fvalue->filter->title.': '.$fvalue->title.($loop->last ? '' : '<br>') !!}
            @endforeach
            </p>
            <div class="product-bottom"><b>{{ $p->final_price }} грн</b>{!! $p->html_available !!}</div>
            <button class="btn btn-red add-cart" data-id="p{{ $p->id }}" data-price="{{ $p->final_price }}" data-name="{{ $p->title }}"> <svg class="icon"><use href="#i-cart"/></svg>{{ __('В корзину') }}</button>
          </article>
        @endforeach
          
        </div>
      </div>
    </section>