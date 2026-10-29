@extends('layouts.base')
@section('title', $page->tag_title ?: ($page->title ?: __('Блог')))
@section('description', trim(strip_tags($page->tag_description ?: ($page->short_content ?? ''))))

@section('content')
    @php($featuredArticle = $articles->onFirstPage() ? $articles->first() : null)
    <main class="blog-page" id="mainContent">
        <section class="blog-hero" aria-labelledby="blogTitle">
            <div class="container">
                @include('site.details.base.crumbs')
                <div class="blog-hero-layout">
                    <div class="blog-hero-copy">
                        <p class="eyebrow">{{ config('app.name') }} / {{ __('ПОЛЕЗНЫЕ МАТЕРИАЛЫ') }}</p>
                        <h1 id="blogTitle">{{ $page->title ?: __('Блог') }} <span>{{ config('app.name') }}</span></h1>
                        @if($page->short_content)
                            <div class="blog-lead">{!! $page->short_content !!}</div>
                        @else
                            <p class="blog-lead">{{ setting('blog_page_short_text') }}</p>
                        @endif
                        <a href="#articles" class="btn btn-red">{{ __('Читать статьи') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                        <div class="blog-hero-facts"><span><svg class="icon" aria-hidden="true"><use href="#i-gear"/></svg>{{ __('Практичные советы') }}</span><span><svg class="icon" aria-hidden="true"><use href="#i-shield"/></svg>{{ __('Подбор без ошибок') }}</span></div>
                    </div>
                    <div class="blog-hero-visual" aria-hidden="true"><img src="{{ $page->previewUrl('one') ?: asset('imports/kingavto/blog-banner.webp') }}" width="1440" height="810" alt="" fetchpriority="high"></div>
                </div>
            </div>
        </section>
        @if($featuredArticle)
            @php($featuredUrl = route('article', ['blogSlag' => $page->slag, 'articleSlag' => $featuredArticle->slag]))
            <section class="blog-feature-section" aria-labelledby="featuredTitle">
                <div class="container">
                    <div class="blog-section-heading"><div><p class="eyebrow">{{ __('РЕКОМЕНДУЕМ ПРОЧИТАТЬ') }}</p><h2 id="featuredTitle">{{ __('Свежая статья') }}</h2></div><a href="{{ $featuredUrl }}" class="section-more">{{ __('Читать материал') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a></div>
                    <a href="{{ $featuredUrl }}" @class(['blog-feature-card', 'blog-feature-card--text' => !$featuredArticle->preview])>
                        @if($featuredArticle->preview)
                            <div class="blog-feature-image"><img src="{{ $featuredArticle->previewUrl('one') }}" width="1000" height="563" alt="" loading="lazy"><span class="blog-image-label">{{ __('НОВОЕ В БЛОГЕ') }}</span></div>
                        @endif
                        <div class="blog-feature-text">
                            <span class="blog-pill">{{ __('Блог') }}</span>
                            @if($featuredArticle->created_at)<time class="blog-date" datetime="{{ $featuredArticle->created_at->toDateString() }}">{{ $featuredArticle->created_at->locale(app()->getLocale())->translatedFormat('j F Y') }}</time>@endif
                            <h3>{{ $featuredArticle->title ?: $featuredArticle->slag }}</h3>
                            <p>{{ Illuminate\Support\Str::limit(strip_tags($featuredArticle->short_content ?: ($featuredArticle->content ?? '')), 240) }}</p>
                            <span class="blog-more">{{ __('Подробнее') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></span>
                        </div>
                    </a>
                </div>
            </section>
        @endif
        <section class="blog-list-section" id="articles" aria-labelledby="articlesTitle">
            <div class="container">
                @if($page->content)<div class="blog-page-content">{!! $page->content !!}</div>@endif
                <div class="blog-section-heading blog-section-heading-catalog"><div><p class="eyebrow">{{ __('АКТУАЛЬНОЕ ОБ АВТОМОБИЛЯХ') }}</p><h2 id="articlesTitle">{{ __('Все статьи') }}</h2></div><p class="blog-count">{{ __('Всего статей: :count', ['count' => $articles->total()]) }}</p></div>
                @if($articles->isNotEmpty())
                    <div class="blog-grid">
                        @foreach($articles as $article)
                            @php($articleUrl = route('article', ['blogSlag' => $page->slag, 'articleSlag' => $article->slag]))
                            <article @class(['blog-card', 'blog-card--text' => !$article->preview])>
                                @if($article->preview)
                                    <a class="blog-card-image" href="{{ $articleUrl }}" tabindex="-1" aria-hidden="true"><img src="{{ $article->previewUrl('five') }}" width="640" height="400" loading="lazy" alt=""></a>
                                @endif
                                <div class="blog-card-content">
                                    <div class="blog-card-info"><span class="blog-tag">{{ __('Блог') }}</span>@if($article->created_at)<time datetime="{{ $article->created_at->toDateString() }}">{{ $article->created_at->locale(app()->getLocale())->translatedFormat('j F Y') }}</time>@endif</div>
                                    <h3><a class="blog-card-title" href="{{ $articleUrl }}">{{ $article->title ?: $article->slag }}</a></h3>
                                    <p>{{ Illuminate\Support\Str::limit(strip_tags($article->short_content ?: ($article->content ?? '')), 240) }}</p>
                                    <div class="blog-card-footer"><a class="blog-card-read" href="{{ $articleUrl }}">{{ __('Подробнее') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a></div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="blog-empty"><svg class="icon" aria-hidden="true"><use href="#i-calendar"/></svg><h3>{{ __('Статті ще не додано.') }}</h3></div>
                @endif
                @if($articles->hasPages())
                    <nav class="blog-pagination" aria-label="{{ __('Сторінки блогу') }}">
                        @if($articles->onFirstPage())<span class="blog-page-button is-disabled" aria-disabled="true">‹</span>@else<a class="blog-page-button" href="{{ $articles->previousPageUrl() }}#articles" rel="prev" aria-label="{{ __('Назад') }}">‹</a>@endif
                        @foreach($articles->getUrlRange(max(1, $articles->currentPage() - 2), min($articles->lastPage(), $articles->currentPage() + 2)) as $pageNumber => $pageUrl)
                            <a class="blog-page-button" href="{{ $pageUrl }}#articles" @if($pageNumber === $articles->currentPage()) aria-current="page" @endif>{{ $pageNumber }}</a>
                        @endforeach
                        @if($articles->hasMorePages())<a class="blog-page-button" href="{{ $articles->nextPageUrl() }}#articles" rel="next" aria-label="{{ __('Далі') }}">›</a>@else<span class="blog-page-button is-disabled" aria-disabled="true">›</span>@endif
                    </nav>
                @endif
            </div>
        </section>
        <section class="blog-cta"><div class="container blog-cta-inner"><div class="blog-cta-graphic" aria-hidden="true"><svg class="icon"><use href="#i-headset"/></svg></div><div><p class="eyebrow">{{ __('НЕ НАШЛИ ОТВЕТ?') }}</p><h2>{{ __('Поможем подобрать запчасти') }}</h2><p>{{ __('Уточним совместимость деталей с вашим автомобилем и подскажем, на что обратить внимание.') }}</p></div><a class="btn btn-red" href="#contacts">{{ __('Связаться с нами') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a></div></section>
    </main>
@endsection
