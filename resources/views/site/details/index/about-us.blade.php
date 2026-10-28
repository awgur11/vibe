@php($aboutImage = setting('about_us_picture.four'))
<section class="section" id="about" aria-labelledby="aboutTitle">
    <div class="container">
        <div @class(['about-panel', 'about-panel--no-image' => !$aboutImage])>
            <div class="about-copy">
                <p class="eyebrow">{{ __('О МАГАЗИНЕ') }}</p>
                <h2 id="aboutTitle">{{ setting('about_us_title') }}</h2>
                <p>{!! nl2br(e(setting('about_us_text'))) !!}</p>
                <a href="#contacts" class="btn btn-red">{{ __('Связаться с нами') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
            </div>
            <div class="about-facts">
                <div><svg class="icon" aria-hidden="true"><use href="#i-trophy"/></svg><b>{{ __('Опыт') }}</b><small>{{ __('в подборе запчастей') }}</small></div>
                <div><svg class="icon" aria-hidden="true"><use href="#i-box"/></svg><b>{{ __('Каталог') }}</b><small>{{ __('товаров разных категорий') }}</small></div>
                <div><svg class="icon" aria-hidden="true"><use href="#i-truck"/></svg><b>{{ __('Доставка') }}</b><small>{{ __('по Украине') }}</small></div>
                <div><svg class="icon" aria-hidden="true"><use href="#i-users"/></svg><b>{{ __('Поддержка') }}</b><small>{{ __('перед покупкой') }}</small></div>
            </div>
            @if($aboutImage)
                <div class="about-image"><img src="{{ $aboutImage }}" alt="{{ setting('about_us_title') }}" loading="lazy"></div>
            @endif
        </div>
    </div>
</section>
