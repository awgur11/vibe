@extends('layouts.base')

@section('title', $catalog->tag_title ?: ($catalog->title ?: $catalog->slag))
@section('description', trim(strip_tags($catalog->tag_description ?: ($catalog->short_content ?? ''))))

@section('content')
    <h1 class="h2 my-4">{{ $catalog->title ?: $catalog->slag }}</h1>
    @if($catalog->short_content)
        <div class="article-body mb-4">{!! $catalog->short_content !!}</div>
    @endif
    @include('site.details.catalogs')
    @if($catalog->content)
        <div class="article-body my-4">{!! $catalog->content !!}</div>
    @endif
@endsection
