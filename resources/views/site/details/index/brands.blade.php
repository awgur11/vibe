@if($brands->isNotEmpty())
    <section class="section brand-section" aria-labelledby="brandsTitle">
        <div class="container">
            <div class="section-head"><h2 id="brandsTitle">{{ __('Популярные бренды') }}</h2></div>
            <div class="brands-grid">
                @foreach($brands as $brand)
                    <div title="{{ $brand->title }}">{!! nl2br(e($brand->name)) !!}</div>
                @endforeach
            </div>
        </div>
    </section>
@endif
