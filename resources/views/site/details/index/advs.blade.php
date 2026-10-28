@if($advs->isNotEmpty())
    <section class="section benefits-section" aria-labelledby="benefitsTitle">
        <div class="container">
            <div class="section-head"><h2 id="benefitsTitle">{{ __('Наши преимущества') }}</h2></div>
            <div class="benefits-grid">
                @foreach($advs as $adv)
                    <div class="benefit">
                        <svg class="icon" aria-hidden="true"><use href="#{{ $adv->icon }}"/></svg>
                        <div><h3>{{ $adv->title }}</h3><p>{{ $adv->description }}</p></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
