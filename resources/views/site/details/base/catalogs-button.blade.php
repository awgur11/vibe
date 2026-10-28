<button type="button" class="catalog-toggle btn btn-red" id="catalogToggle" aria-expanded="false" aria-controls="catalogDropdown">
    <svg class="icon"><use href="#i-menu"/></svg>{{ __('Каталог') }}
</button>
<div class="catalog-dropdown catalog-mega" id="catalogDropdown" hidden>
    <div class="catalog-mega-inner">
    <div class="catalog-roots">
        @foreach($menuCatalogs->get('', collect()) as $rootCatalog)
            <a class="catalog-menu-row" href="{{ route('catalog', ['catalogSlag' => $rootCatalog->slag]) }}" data-catalog-root="{{ $rootCatalog->id }}">
                <svg class="icon"><use href="#i-box"/></svg>
                <span>{{ $rootCatalog->title ?: $rootCatalog->slag }}</span>
                @if($menuCatalogs->has($rootCatalog->id))
                    <svg class="icon catalog-chevron"><use href="#i-chevron"/></svg>
                @endif
            </a>
        @endforeach
    </div>
    @foreach($menuCatalogs->get('', collect()) as $rootCatalog)
        @php($childCatalogs = $menuCatalogs->get($rootCatalog->id, collect()))
        @if($childCatalogs->isNotEmpty())
        <div class="catalog-branch" data-catalog-branch="{{ $rootCatalog->id }}" hidden>
            <div class="catalog-subcategories">
                @foreach($childCatalogs as $childCatalog)
                    <a class="catalog-menu-row" href="{{ route('catalog', ['catalogSlag' => $childCatalog->slag]) }}" data-catalog-child="{{ $childCatalog->id }}">
                        <span>{{ $childCatalog->title ?: $childCatalog->slag }}</span>
                        @if($menuCatalogs->has($childCatalog->id))
                            <svg class="icon catalog-chevron"><use href="#i-chevron"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
            @foreach($childCatalogs as $childCatalog)
                @if($menuCatalogs->has($childCatalog->id))
                <div class="catalog-tiles" data-catalog-tiles="{{ $childCatalog->id }}" hidden>
                    @foreach($menuCatalogs->get($childCatalog->id) as $tileCatalog)
                        <a class="catalog-tile" href="{{ route('catalog', ['catalogSlag' => $tileCatalog->slag]) }}">
                            @if($tileCatalog->preview)
                                <img src="{{ $tileCatalog->previewUrl('three') }}" alt="" loading="lazy" width="200" height="200">
                            @else
                                <svg class="icon catalog-tile-placeholder"><use href="#i-box"/></svg>
                            @endif
                            <span>{{ $tileCatalog->title ?: $tileCatalog->slag }}</span>
                        </a>
                    @endforeach
                </div>
                @endif
            @endforeach
        </div>
        @endif
    @endforeach
    </div>
</div>
