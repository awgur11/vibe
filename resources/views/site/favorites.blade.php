@extends('layouts.base')
@section('content')
<main class="section" data-favorites-page>
    <div class="container">
        <nav class="blog-crumbs" aria-label="{{ __('Хлебные крошки') }}"><a href="{{ route('index') }}">{{ __('Главная') }}</a><span>/</span><span>{{ __('Избранное') }}</span></nav>
        <div class="section-head"><h1>{{ __('Избранное') }}</h1></div>
        @if($products->isEmpty())
            <p>{{ __('В избранном пока нет товаров.') }}</p>
            <a class="btn btn-red" href="{{ route('index') }}">{{ __('Продолжить покупки') }}</a>
        @else
            <div class="products-grid">@include('site.details.cards.products', ['products' => $products])</div>
            @if($products->hasPages())
                <nav class="pagination" aria-label="{{ __('Избранное') }}">
                    @if($products->previousPageUrl())<a class="btn" href="{{ $products->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                    <span>{{ $products->currentPage() }} / {{ $products->lastPage() }}</span>
                    @if($products->nextPageUrl())<a class="btn" href="{{ $products->nextPageUrl() }}">{{ __('Далее') }}</a>@endif
                </nav>
            @endif
        @endif
    </div>
</main>
@endsection
