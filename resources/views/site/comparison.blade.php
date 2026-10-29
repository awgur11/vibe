@extends('layouts.base')
@section('content')
<main class="section" data-comparison-page>
    <div class="container">
        <nav class="blog-crumbs" aria-label="{{ __('Хлебные крошки') }}"><a href="{{ route('index') }}">{{ __('Главная') }}</a><span>/</span><span>{{ __('Сравнение') }}</span></nav>
        <div class="section-head"><h1>{{ __('Сравнение товаров') }}: {{ $catalog->title ?: $catalog->slag }}</h1></div>
        @if($products->isEmpty())
            <p>{{ __('В этой категории пока нет товаров для сравнения.') }}</p>
            <a class="btn btn-red" href="{{ route('catalog', ['catalogSlag' => $catalog->slag]) }}">{{ __('Продолжить покупки') }}</a>
        @else
            <div class="comparison-table-wrap" tabindex="0" aria-label="{{ __('Сравнение товаров') }}"><table class="comparison-table">
                <thead><tr><th scope="col">{{ __('Товар') }}</th>
                    @foreach($products as $p)<th scope="col" class="comparison-product-cell">
                        @if($p->preview)<img src="{{ $p->preview('mini') }}" alt="{{ $p->title ?: $p->slag }}" width="160" height="120">@else<svg class="icon comparison-placeholder" aria-hidden="true"><use href="#i-no-image"/></svg>@endif
                        <h2>{{ $p->title ?: $p->slag }}</h2>
                        <button type="button" class="btn comparison-remove" title="{{ __('Удалить из сравнения') }}" aria-label="{{ __('Удалить из сравнения') }}" data-compare-remove data-compare="{{ $p->id }}" data-compare-url="{{ route('comparison.store', $p) }}" data-add-label="{{ __('Добавить в сравнение') }}" data-remove-label="{{ __('Удалить из сравнения') }}"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button>
                    </th>@endforeach
                </tr></thead>
                <tbody>
                    <tr><th scope="row">{{ __('Артикул') }}</th>@foreach($products as $p)<td>{{ $p->articule ?: '—' }}</td>@endforeach</tr>
                    <tr><th scope="row">{{ __('Цена') }}</th>@foreach($products as $p)<td><strong>{{ $p->final_price }} грн</strong></td>@endforeach</tr>
                    <tr><th scope="row">{{ __('Наличие') }}</th>@foreach($products as $p)<td>{!! $p->html_available !!}</td>@endforeach</tr>
                    @foreach($filters as $filter)
                        <tr><th scope="row">{{ $filter->title ?: $filter->slag }}</th>@foreach($products as $p)<td>{{ $p->fvalues->where('filter_id', $filter->id)->map(fn ($value) => $value->title ?: $value->slag)->implode(', ') ?: '—' }}</td>@endforeach</tr>
                    @endforeach
                    <tr><th scope="row">{{ __('В корзину') }}</th>@foreach($products as $p)<td><button type="button" class="btn btn-red add-cart" data-cart-product-url="{{ route('cart.product', $p) }}" @disabled($p->available === 2)><svg class="icon" aria-hidden="true"><use href="#i-cart"/></svg>{{ __('В корзину') }}</button></td>@endforeach</tr>
                </tbody>
            </table></div>
        @endif
    </div>
</main>
@endsection
