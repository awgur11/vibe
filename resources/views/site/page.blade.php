@extends('layouts.base')
@section('title', $page->tag_title ?: ($page->title ?: config('app.name')))
@section('description', trim(strip_tags($page->tag_description ?: ($page->short_content ?? ''))))

@section('content')
<main class="content-page" id="mainContent">
    <div class="container">
        @include('site.details.base.crumbs')
        <article class="content-page-panel">
            <header class="content-page-heading">
                <p class="eyebrow">KINGAVTO</p>
                <h1>{{ $page->title ?: $page->slag }}</h1>
                @if($page->short_content)<div class="content-page-lead">{!! $page->short_content !!}</div>@endif
            </header>
            @if($page->preview)
                <img class="content-page-image" src="{{ $page->previewUrl('one') }}" alt="{{ $page->title ?: $page->slag }}" fetchpriority="high">
            @endif
            <div class="content-page-body">{!! $page->content !!}</div>
            <a class="content-page-return" href="{{ route('index') }}"><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg>{{ __('На главную') }}</a>
        </article>
    </div>
</main>
@endsection
