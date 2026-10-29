<section class="hero" aria-labelledby="heroTitle">
      <div class="container hero-inner">
        <div class="hero-copy">
          <p class="eyebrow">{{ config('app.name') }} · {{ setting('header_small_title') }}</p>
          <h1 id="heroTitle">{{ setting('header_first_title') }} <span>{{ setting('header_second_title') }}</span></h1>
          <p class="hero-subtitle">{{ setting('header_sub_title') }}</p>
          <div class="hero-buttons"><a href="{{ route('catalogs') }}" class="btn btn-red">{{ __('Перейти в каталог') }} <svg class="icon"><use href="#i-arrow"/></svg></a><button type="button" class="btn btn-outline" data-callback-open aria-haspopup="dialog" aria-controls="callbackDialog"><svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg> {{ __('Мы перезвоним') }}</button></div>
          <div class="hero-perks"><span><svg class="icon"><use href="#i-shield"/></svg>{{ __('Тысячи позиций') }}<br>{{ __('в каталоге') }}</span><span><svg class="icon"><use href="#i-truck"/></svg>{{ __('Доставка') }}<br>{{ __('по Украине') }}</span><span><svg class="icon"><use href="#i-gear"/></svg>{{ __('Помощь') }}<br>{{ __('в подборе') }}</span></div>
        </div>
        <div class="hero-art"><img src="{{ asset('storage/'.setting('header_preview')) }}" width="960" height="446" alt="{{ __('Автомобиль и комплектующие: тормозные диски, фильтры, пружины') }}" fetchpriority="high"></div>
      </div>
    </section>