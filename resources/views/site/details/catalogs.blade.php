@if($catalogs->isNotEmpty())
    <section class="site-catalogs my-4" aria-label="{{ __('Каталоги') }}">
        @if(request()->routeIs('index'))
            <h2 class="h3 mb-4">{{ __('Популярні каталоги') }}</h2>
        @endif
        <div class="site-catalog-grid">
            @foreach($catalogs as $catalogCard)
                <a class="site-catalog-card" href="{{ route('catalog', ['catalogSlag' => $catalogCard->slag]) }}">
                    <div class="site-catalog-image">
                        @if($catalogCard->preview)
                            <img src="{{ $catalogCard->previewUrl('three') }}" alt="" loading="lazy" width="300" height="300">
                        @endif
                    </div>
                    <span class="site-catalog-title">{{ $catalogCard->title ?: $catalogCard->slag }}</span>
                </a>
            @endforeach
        </div>
    </section>
    <style>
        .site-catalog-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.site-catalog-card{display:flex;flex-direction:column;aspect-ratio:1;min-width:0;padding:12px;border:1px solid #e1e1e5;background:#fff;color:#202334;text-decoration:none;transition:border-color .15s,color .15s}.site-catalog-card:hover{border-color:#df0707;color:#df0707}.site-catalog-card:focus-visible{outline:2px solid #df0707;outline-offset:3px}.site-catalog-image{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;overflow:hidden}.site-catalog-image img{display:block;width:100%;height:100%;object-fit:contain}.site-catalog-title{flex-shrink:0;margin-top:10px;text-align:center;font-size:14px;font-weight:600;line-height:1.35;overflow-wrap:anywhere}@media(min-width:576px){.site-catalog-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}}@media(min-width:992px){.site-catalog-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.site-catalog-card{padding:16px}.site-catalog-title{font-size:16px}}@media(min-width:1200px){.site-catalog-grid{grid-template-columns:repeat(6,minmax(0,1fr))}}
    </style>
@endif
