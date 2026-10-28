@extends('layouts.base')
@section('title', $page->tag_title ?: ($page->title ?: 'Vibe'))
@section('description', trim(strip_tags($page->tag_description ?: ($page->short_content ?? ''))))

@section('content')
    <article class="article mx-auto py-4 py-md-5">
        <a href="{{ route('page', $blog->slag) }}">← {{ $blog->title ?: __('Блог') }}</a>
        <h1 class="display-5 fw-bold my-4">{{ $page->title ?: $page->slag }}</h1>
        @if($page->origin)
            <img class="article-image img-fluid w-100 rounded-4 my-4" src="{{ $page->fhd }}" alt="{{ $page->title ?? '' }}">
        @endif
        @if($page->short_content)
            <div class="lead text-secondary mb-4">{!! $page->short_content !!}</div>
        @endif
        <div class="article-body">{!! $page->content !!}</div>
    </article>
@endsection
