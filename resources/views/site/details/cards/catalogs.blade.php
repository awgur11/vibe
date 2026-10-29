@foreach($catalogs as $mc)
    <a href="{{ route('catalog', ['catalogSlag' => $mc->slag]) }}" @class(['category-card', 'category-card--no-image' => !$mc->preview])>
        @if($mc->preview)
            <img src="{{ $mc->previewUrl('mini') }}" alt="" loading="lazy" width="320" height="240">
        @else
            <div class="category-placeholder" aria-hidden="true"><svg class="icon"><use href="#i-box"/></svg></div>
        @endif
        <strong>{{ $mc->title ?: $mc->slag }}</strong>
        @if($showParents ?? true)
            <span>{{ $mc->parent?->title }} {{ $mc->parent?->parent?->title ? ' → '.$mc->parent->parent->title : '' }}</span>
        @endif
        <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg>
    </a>
@endforeach
