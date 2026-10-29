@extends('layouts.base')
@section('title', __('Каталог'))
@section('content')
<main class="catalogs-page section" id="mainContent">
    <div class="container">
        @include('site.details.base.crumbs')
        <div class="section-head catalogs-page-heading"><h1>{{ __('Каталог') }}</h1></div>
        @forelse($catalogs as $rootCatalog)
            <section class="catalogs-root" aria-labelledby="catalog-root-{{ $rootCatalog->id }}">
                <h2 id="catalog-root-{{ $rootCatalog->id }}">
                    @if($rootCatalog->children->isEmpty())
                        <a class="catalogs-root-link" href="{{ route('catalog', ['catalogSlag' => $rootCatalog->slag]) }}">{{ $rootCatalog->title ?: $rootCatalog->slag }}<svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                    @else
                        {{ $rootCatalog->title ?: $rootCatalog->slag }}
                    @endif
                </h2>
                @if($rootCatalog->children->isNotEmpty())
                    <div class="categories-grid">@include('site.details.cards.catalogs', ['catalogs' => $rootCatalog->children, 'showParents' => false])</div>
                @endif
            </section>
        @empty
            <p>{{ __('Каталоги пока не добавлены.') }}</p>
        @endforelse
    </div>
</main>
@endsection
