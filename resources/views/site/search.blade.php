@extends('layouts.base')
@section('title', __('Результаты поиска'))
@section('content')
<main class="section"><div class="container">
    @include('site.details.base.crumbs')
    <div class="section-head catalogs-page-heading"><h1>{{ __('Результаты поиска') }}</h1></div>
    @if(mb_strlen($query) < 3)<p>{{ __('Введите минимум 3 символа для поиска.') }}</p>
    @else<p>{{ __('По запросу «:query» найдено: :count', ['query' => $query, 'count' => $products->total()]) }}</p>
        @if($products->isEmpty())<p>{{ __('Товары не найдены.') }}</p>@else<div class="products-grid">@include('site.details.cards.products', ['products' => $products])</div>@endif
        @if($products->hasPages())<nav class="pagination">@if($products->previousPageUrl())<a class="btn" href="{{ $products->previousPageUrl() }}">{{ __('Назад') }}</a>@endif<span>{{ $products->currentPage() }} / {{ $products->lastPage() }}</span>@if($products->nextPageUrl())<a class="btn" href="{{ $products->nextPageUrl() }}">{{ __('Далее') }}</a>@endif</nav>@endif
    @endif
</div></main>
@endsection
