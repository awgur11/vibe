@extends('layouts.base')
@section('title', $page->tag_title ?: ($page->title ?: config('app.name')))
@section('description', trim(strip_tags($page->tag_description ?: ($page->short_content ?? ''))))

@section('content')
    <main class="article-page" id="mainContent">
        <div class="container">
            @include('site.details.base.crumbs')
            <div class="article-layout">
                <article class="article-main">
                    <header class="article-heading">
                        <a class="blog-pill" href="{{ route('page', $blog->slag) }}">{{ $blog->title ?: __('Блог') }}</a>
                        <h1>{{ $page->title ?: $page->slag }}</h1>
                        @if($page->created_at)<time datetime="{{ $page->created_at->toDateString() }}">{{ $page->created_at->locale(app()->getLocale())->translatedFormat('j F Y') }}</time>@endif
                    </header>
                    <div class="article-copy">
                        @if($page->preview)
                            <img class="article-main-image" src="{{ $page->previewUrl('three') }}" alt="{{ $page->title ?: $page->slag }}" width="768" loading="eager">
                        @endif
                        @if($page->short_content)<div class="article-intro">{!! $page->short_content !!}</div>@endif
                        <div class="article-content">{!! $page->content !!}</div>
                    </div>
                    <a class="article-return" href="{{ route('page', $blog->slag) }}"><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg>{{ __('Все статьи') }}</a>
                </article>
                <aside class="article-sidebar" aria-label="{{ __('Дополнительная информация') }}">
                    @if($latestArticles->isNotEmpty())
                        <section class="article-sidebar-panel" aria-labelledby="recentArticlesTitle">
                            <p class="eyebrow">{{ __('Читайте также') }}</p>
                            <h2 id="recentArticlesTitle">{{ __('Последние статьи') }}</h2>
                            <div class="article-recent-list">
                                @foreach($latestArticles as $recentArticle)
                                    <a class="article-recent-link" href="{{ route('article', ['blogSlag' => $blog->slag, 'articleSlag' => $recentArticle->slag]) }}">
                                        @if($recentArticle->preview)<img src="{{ $recentArticle->previewUrl('mini') }}" alt="" width="64" height="64" loading="lazy">@endif
                                        <span><strong>{{ $recentArticle->title ?: $recentArticle->slag }}</strong>@if($recentArticle->created_at)<time datetime="{{ $recentArticle->created_at->toDateString() }}">{{ $recentArticle->created_at->locale(app()->getLocale())->translatedFormat('j F Y') }}</time>@endif</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                    <section class="article-sidebar-panel article-sidebar-contacts" aria-labelledby="articleContactsTitle">
                        <div class="article-contact-symbol" aria-hidden="true"><svg class="icon"><use href="#i-headset"/></svg></div>
                        <h2 id="articleContactsTitle">{{ __('Контакты') }}</h2>
                        <p>{{ __('Поможем подобрать запчасти') }}</p>
                        @foreach(setting('phones') ?? [] as $phone)
                            <a class="article-contact-row" href="tel:{{ preg_replace('/[^+0-9]/', '', $phone) }}"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg><span>{{ $phone }}</span></a>
                        @endforeach
                        @if(setting('site_email'))<a class="article-contact-row" href="mailto:{{ setting('site_email') }}"><svg class="icon" aria-hidden="true"><use href="#i-mail"/></svg><span>{{ setting('site_email') }}</span></a>@endif
                        @if(setting('address'))<div class="article-contact-row"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg><span>{{ setting('address') }}</span></div>@endif
                        @if(setting('working_hours'))<div class="article-contact-row"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>{!! nl2br(e(setting('working_hours'))) !!}</span></div>@endif
                    </section>
                </aside>
            </div>
        </div>
    </main>
@endsection
