@if($slides->isNotEmpty())
    <section class="site-slider carousel slide my-4 overflow-hidden" id="siteSlider" data-bs-ride="carousel" data-bs-interval="5000" aria-label="{{ __('Слайдер') }}" aria-roledescription="{{ __('Карусель') }}">
        @if($slides->count() > 1)
            <div class="carousel-indicators">
                @foreach($slides as $slide)
                    <button type="button" data-bs-target="#siteSlider" data-bs-slide-to="{{ $loop->index }}"
                        class="{{ $loop->first ? 'active' : '' }}" @if($loop->first) aria-current="true" @endif
                        aria-label="{{ __('Слайд') }} {{ $loop->iteration }}: {{ $slide->title }}"></button>
                @endforeach
            </div>
        @endif
        <div class="carousel-inner">
            @foreach($slides as $slide)
                <div class="carousel-item {{ $loop->first ? 'active' : '' }}" role="group" aria-roledescription="{{ __('Слайд') }}" aria-label="{{ $loop->iteration }} / {{ $slides->count() }}">
                    @if(str_starts_with($slide->preview ?? '', 'slides/kingavto/'))
                        @if($slide->link)<a class="d-block" href="{{ $slide->link }}">@endif
                            <img class="site-slide-banner d-block w-100" src="{{ $slide->previewUrl('fhd') }}" alt="{{ $slide->title }}" @if(!$loop->first) loading="lazy" @else fetchpriority="high" @endif>
                        @if($slide->link)</a>@endif
                    @else
                    <div class="site-slide bg-dark text-white position-relative d-flex align-items-center">
                        @if($slide->preview)
                            <img class="site-slide-image position-absolute top-0 start-0 w-100 h-100" src="{{ $slide->previewUrl('fhd') }}" alt="" @if(!$loop->first) loading="lazy" @else fetchpriority="high" @endif>
                        @endif
                        <div class="site-slide-shade position-absolute top-0 start-0 w-100 h-100" aria-hidden="true"></div>
                        <div class="site-slide-content position-relative py-4">
                            <h2 class="display-5 fw-bold">{{ $slide->title }}</h2>
                            @if($slide->subtitle)<p class="lead mt-3 mb-4 text-white">{{ $slide->subtitle }}</p>@endif
                            @if($slide->link)<a class="btn btn-primary" href="{{ $slide->link }}">{{ __('Дізнатися більше') }}</a>@endif
                        </div>
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
        @if($slides->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#siteSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">{{ __('Попередній слайд') }}</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#siteSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">{{ __('Наступний слайд') }}</span>
            </button>
        @endif
    </section>
    <style>
        .site-slide{min-height:clamp(200px,26.6667vw,346.6667px)}.site-slide-banner{aspect-ratio:3/1;object-fit:cover}.site-slide-image{object-fit:cover}.site-slide-shade{background:linear-gradient(90deg,rgba(16,20,35,.88),rgba(16,20,35,.32))}.site-slide-content{width:100%;padding-inline:12%;overflow-wrap:anywhere}.site-slide-content h2,.site-slide-content p{max-width:700px}.site-slider .carousel-control-prev,.site-slider .carousel-control-next{width:9%}.site-slider .carousel-indicators{margin-bottom:14px}
        .site-slider .carousel-indicators [data-bs-target]{box-sizing:border-box;width:12px;height:12px;margin-inline:5px;padding:0;border:1px solid #df0707;border-radius:0;background:#fff;opacity:1;background-clip:border-box}.site-slider .carousel-indicators .active{background:#df0707}.site-slider .carousel-indicators [data-bs-target]:focus-visible{outline:2px solid #fff;outline-offset:3px}
        @media(max-width:575px){.site-slide-content{padding-inline:13%}.site-slide-content h2{font-size:1.75rem}.site-slide-content .lead{font-size:1rem}}
    </style>
@endif
