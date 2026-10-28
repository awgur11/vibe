@extends('layouts.base')

@section('content')

    @include('site.details.base.header')

    @include('site.details.index.catalogs')

    @include('site.details.index.popular-products')

    @include('site.details.index.advs')

    @include('site.details.index.brands')

    @include('site.details.index.blog-block')

    @include('site.details.index.about-us')

@endsection
