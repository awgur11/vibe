@extends('layouts.base')

@section('title', $catalog->tag_title ?: ($catalog->title ?: $catalog->slag))
@section('description', trim(strip_tags($catalog->tag_description ?: ($catalog->short_content ?? ''))))

@section('content')
    <main class="catalog-page" id="mainContent">
        <section class="catalog-heading" aria-labelledby="catalogTitle">
            <div class="container">
                <div class="catalog-heading-inner">
                    <div>
                        <p class="eyebrow">{{ config('app.name') }} / {{ __('Каталог') }}</p>
                        <h1 id="catalogTitle">{{ $catalog->title ?: $catalog->slag }}</h1>
                        @if($catalog->short_content)<div class="catalog-intro">{!! $catalog->short_content !!}</div>@endif
                    </div>
                </div>
            </div>
        </section>
        <div class="container catalog-page-content">
            @include('site.details.base.crumbs')
            @if($catalogs->isNotEmpty())
                <section class="catalog-sections" aria-labelledby="catalogSectionsTitle">
                    <div class="section-head"><h2 id="catalogSectionsTitle">{{ __('Разделы каталога') }}</h2><span class="catalog-count">{{ __('Разделов: :count', ['count' => $catalogs->count()]) }}</span></div>
                    <div class="categories-grid catalog-categories-grid">
                        @include('site.details.cards.catalogs', ['catalogs' => $catalogs, 'showParents' => false])
                    </div>
                </section>
            @else
                <button type="button" class="catalog-filter-backdrop" data-catalog-filter-backdrop aria-label="{{ __('Закрыть фильтры') }}" tabindex="-1" hidden></button>
                <div class="catalog-products-layout">
                    <aside class="catalog-filter-panel" id="catalog-filter-panel" aria-labelledby="catalogFiltersTitle">
                        <button type="button" class="catalog-filter-close" data-catalog-filter-close aria-label="{{ __('Закрыть фильтры') }}"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button>
                        <h2 id="catalogFiltersTitle"><svg class="icon" aria-hidden="true"><use href="#i-gear"/></svg>{{ __('Фильтры') }}</h2>
                        <form method="GET" action="{{ route('catalog', ['catalogSlag' => $catalog->slag]) }}#products" data-catalog-filters id="catalog-filter-form">
                            @forelse($filters->filter(fn ($filter) => $filter->fvalues->isNotEmpty())->values() as $filter)
                                <details class="catalog-filter-group" @if($loop->index < 3) open @endif>
                                    <summary><span>{{ $filter->title ?: $filter->slag }}</span><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
                                    <div class="catalog-filter-values">
                                        @foreach($filter->fvalues as $filterValue)
                                            <label class="catalog-filter-option"><input type="checkbox" name="fvalues[]" value="{{ $filterValue->id }}" @checked(in_array($filterValue->id, $selectedValueIds, true))><span>{{ $filterValue->title ?: $filterValue->slag }}</span></label>
                                        @endforeach
                                    </div>
                                </details>
                            @empty
                                <p class="catalog-filter-note">{{ __('Для этого каталога фильтры пока не настроены.') }}</p>
                            @endforelse
                        </form>
                    </aside>
                    <section class="catalog-product-results" id="products">
                        <div class="section-head catalog-products-heading">
                            <div><span class="catalog-count">{{ __('Найдено товаров: :count', ['count' => $products->total()]) }}</span></div>
                            <div class="catalog-products-controls">
                                <button type="button" class="catalog-mobile-filter-toggle" data-catalog-filter-toggle aria-controls="catalog-filter-panel" aria-expanded="false"><svg class="icon" aria-hidden="true"><use href="#i-gear"/></svg>{{ __('Фильтры') }}@if($selectedValueIds !== [])<span>{{ count($selectedValueIds) }}</span>@endif</button>
                            <label class="catalog-sort"><span>{{ __('Сортировка') }}</span><select aria-label="{{ __('Сортировка') }}" name="sort" form="catalog-filter-form" data-catalog-sort>
                                <option value="popular" @selected($sort === 'popular')>{{ __('Популярные') }}</option>
                                <option value="price_desc" @selected($sort === 'price_desc')>{{ __('Сначала дорогие') }}</option>
                                <option value="price_asc" @selected($sort === 'price_asc')>{{ __('Сначала дешёвые') }}</option>
                            </select></label>
                            </div>
                        </div>
                        @if($activeFilters !== [])
                            <div class="catalog-active-filters" role="group" aria-label="{{ __('Выбранные фильтры') }}">
                                @foreach($activeFilters as $activeFilter)
                                    <a class="catalog-active-filter" href="{{ $activeFilter['url'] }}#products" aria-label="{{ __('Убрать фильтр: :filter', ['filter' => $activeFilter['label']]) }}"><span>{{ $activeFilter['label'] }}</span><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></a>
                                @endforeach
                                @if(count($activeFilters) >= 2)
                                    <a class="catalog-reset-filters" href="{{ route('catalog', ['catalogSlag' => $catalog->slag, ...($sort === 'popular' ? [] : ['sort' => $sort])]) }}#products">{{ __('Сбросить всё') }}</a>
                                @endif
                            </div>
                        @endif
                        @if($products->isNotEmpty())
                            <div class="products-grid catalog-products-grid">
                                @include('site.details.cards.products', ['products' => $products])
                            </div>
                        @else
                            <p class="catalog-products-empty">{{ __('Товары не найдены. Попробуйте изменить фильтры.') }}</p>
                        @endif
                        @if($products->hasPages())
                            <nav class="blog-pagination" aria-label="{{ __('Страницы товаров') }}">
                                @if($products->onFirstPage())<span class="blog-page-button is-disabled" aria-disabled="true">‹</span>@else<a class="blog-page-button" href="{{ $products->previousPageUrl() }}#products" rel="prev" aria-label="{{ __('Назад') }}">‹</a>@endif
                                @foreach($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $pageNumber => $pageUrl)
                                    <a class="blog-page-button" href="{{ $pageUrl }}#products" @if($pageNumber === $products->currentPage()) aria-current="page" @endif>{{ $pageNumber }}</a>
                                @endforeach
                                @if($products->hasMorePages())<a class="blog-page-button" href="{{ $products->nextPageUrl() }}#products" rel="next" aria-label="{{ __('Далі') }}">›</a>@else<span class="blog-page-button is-disabled" aria-disabled="true">›</span>@endif
                            </nav>
                        @endif
                    </section>
                </div>
            @endif
            @if($catalog->content)<section class="catalog-description">{!! $catalog->content !!}</section>@endif
        </div>
    </main>
@endsection
