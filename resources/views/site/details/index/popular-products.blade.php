<section class="section" id="products" aria-labelledby="productsTitle">
      <div class="container">
        <div class="section-head"><h2 id="productsTitle">{{ __('Популярные товары') }}</h2><a href="#products" class="section-more" id="showAllProducts" data-expand-label="{{ __('Смотреть все товары') }}" data-collapse-label="{{ __('Свернуть список') }}">{{ __('Смотреть все товары') }} <svg class="icon"><use href="#i-arrow"/></svg></a></div>
        <div class="product-filters" role="group" aria-label="{{ __('Фильтр популярных товаров') }}">
          <button type="button" class="filter-btn is-active" data-filter="all" aria-pressed="true">{{ __('Все товары') }}</button>
        @foreach($products->filter(fn ($product) => $product->catalog !== null)->unique('catalog_id') as $p)
          <button type="button" class="filter-btn" data-filter="{{ $p->catalog->slag }}" aria-pressed="false">{{ $p->catalog->title }}</button>
        @endforeach
        </div>
        <div class="products-grid" id="productsGrid">
        @include('site.details.cards.products', ['products' => $products])
          
        </div>
        <p id="emptyProducts" hidden>{{ __('Товары не найдены.') }}</p>
      </div>
    </section>