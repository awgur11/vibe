@extends('layouts.base')
@section('title', $product->tag_title ?: ($product->title ?: $product->slag))
@section('content')
<main class="product-page section">
<div class="container">
    <nav class="blog-crumbs" aria-label="{{ __('Хлебные крошки') }}"><a href="{{ route('index') }}">{{ __('Главная') }}</a>@if($product->catalog?->public)<span>/</span><a href="{{ route('catalog', ['catalogSlag' => $product->catalog->slag]) }}">{{ $product->catalog->title ?: $product->catalog->slag }}</a>@endif<span>/</span><span>{{ $product->title ?: $product->slag }}</span></nav>
    <div class="product-detail-layout">
        <section class="product-gallery" aria-label="{{ __('Фотографии товара') }}" data-product-gallery>
            <div class="product-gallery-actions">
                <button type="button" class="btn favorite-toggle" aria-label="{{ __('Добавить в избранное') }}" data-favorite="{{ $product->id }}" data-favorite-url="{{ route('favorites.store', $product) }}" data-add-label="{{ __('Добавить в избранное') }}" data-remove-label="{{ __('Убрать из избранного') }}" aria-pressed="false"><svg class="icon"><use href="#i-heart"/></svg></button>
                <button type="button" class="btn compare-toggle" aria-label="{{ __('Добавить в сравнение') }}" data-compare="{{ $product->id }}" data-compare-url="{{ route('comparison.store', $product) }}" data-add-label="{{ __('Добавить в сравнение') }}" data-remove-label="{{ __('Убрать из сравнения') }}" aria-pressed="false"><svg class="icon"><use href="#i-chart"/></svg></button>
            </div>
            @if($product->pictures->isNotEmpty())
                <button type="button" class="product-gallery-main" data-gallery-open aria-label="{{ __('Увеличить фото') }}"><img src="{{ $product->pictures->first()->url() }}" alt="{{ $product->title ?: $product->slag }}" data-gallery-main></button>
                <div class="product-gallery-thumbs">@foreach($product->pictures as $picture)<button type="button" data-gallery-image="{{ $picture->url() }}" aria-label="{{ __('Фото :number', ['number' => $loop->iteration]) }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><img src="{{ $picture->mini() }}" alt="" width="72" height="72" loading="lazy"></button>@endforeach</div>
                <dialog class="product-lightbox" data-gallery-dialog aria-label="{{ __('Фотографии товара') }}"><button type="button" class="btn product-lightbox-close" data-gallery-close aria-label="{{ __('Закрыть') }}"><svg class="icon"><use href="#i-close"/></svg></button><button type="button" class="btn" data-gallery-prev aria-label="{{ __('Предыдущее фото') }}">‹</button><img src="{{ $product->pictures->first()->url() }}" alt="{{ $product->title ?: $product->slag }}" data-gallery-full><button type="button" class="btn" data-gallery-next aria-label="{{ __('Следующее фото') }}">›</button></dialog>
            @else
                <div class="product-gallery-placeholder"><svg class="icon" aria-hidden="true"><use href="#i-no-image"/></svg><p>{{ __('Нет изображения') }}</p></div>
            @endif
        </section>
        <section class="product-detail-info">
            <p class="eyebrow">KINGAVTO</p><h1>{{ $product->title ?: $product->slag }}</h1>
            @if($product->articule)<p>{{ __('Артикул') }}: {{ $product->articule }}</p>@endif
            {!! $product->html_available !!}
            <div class="product-detail-price">{{ $product->final_price }} <small>грн</small>@if($product->discount > 0)<del>{{ $product->price }} грн</del>@endif</div>
            <button type="button" class="btn btn-red add-cart" data-cart-product-url="{{ route('cart.product', $product) }}" @disabled($product->available === 2)><svg class="icon"><use href="#i-cart"/></svg>{{ __('В корзину') }}</button>

            <div class="product-service-note"><svg class="icon"><use href="#i-truck"/></svg><span>{{ __('Доставка Новой почтой или самовывоз') }}</span></div>
            <div class="product-service-note"><svg class="icon"><use href="#i-shield"/></svg><span>{{ __('Поможем подобрать подходящую деталь') }}</span></div>
        </section>
    </div>
    <div class="product-detail-sections">
        @if($product->fvalues->isNotEmpty())<section class="product-detail-panel"><h2>{{ __('Характеристики') }}</h2><dl class="product-specifications">@foreach($product->fvalues->groupBy('filter_id') as $values)<div><dt>{{ $values->first()->filter?->title ?: $values->first()->filter?->slag }}</dt><dd>{{ $values->map(fn ($value) => $value->title ?: $value->slag)->implode(', ') }}</dd></div>@endforeach</dl></section>@endif
        @if($product->description)<section class="product-detail-panel"><h2>{{ __('Описание') }}</h2><div class="product-description">{!! $product->description !!}</div></section>@endif
        <section class="product-detail-panel" id="reviews"><h2>{{ __('Отзывы') }}</h2>
            @if(session('comment_status'))<p class="product-review-status" role="status">{{ session('comment_status') }}</p>@endif
            @forelse($comments as $comment)<article class="product-review"><strong>{{ $comment->name }}</strong><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d.m.Y') }}</time><p>{{ $comment->content }}</p></article>@empty<p>{{ __('Отзывов пока нет. Будьте первым!') }}</p>@endforelse
            @if($comments->hasPages())<nav class="pagination">@if($comments->previousPageUrl())<a class="btn" href="{{ $comments->previousPageUrl() }}">{{ __('Назад') }}</a>@endif<span>{{ $comments->currentPage() }} / {{ $comments->lastPage() }}</span>@if($comments->nextPageUrl())<a class="btn" href="{{ $comments->nextPageUrl() }}">{{ __('Далее') }}</a>@endif</nav>@endif
            <form method="POST" action="{{ route('product.comments.store', ['product' => $product->slag]) }}" class="product-review-form">
                @csrf<h3>{{ __('Оставить отзыв') }}</h3><p>{{ __('Отзывы публикуются после проверки модератором.') }}</p>
                @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <label for="review-name">{{ __('Имя') }} *</label><input id="review-name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
                <label for="review-content">{{ __('Ваш отзыв') }} *</label><textarea id="review-content" name="content" rows="5" required minlength="3" maxlength="5000">{{ old('content') }}</textarea>
                <button class="btn btn-red" type="submit">{{ __('Отправить отзыв') }}</button>
            </form>
        </section>
    </div>
</div>
</main>
@endsection
