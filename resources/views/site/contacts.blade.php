@extends('layouts.base')

@section('title', $page->tag_title ?: ($page->title ?: 'Vibe'))

@section('description', trim(strip_tags($page->tag_description ?: ($page->short_content ?? ''))))

@section('content')
@php
    $contactPhones = setting('phones') ?? [];
    $contactEmail = setting('site_email');
    $contactAddress = setting('address');
    $workingHours = setting('working_hours');
    $mapIframe = setting('google_maps_iframe');
    $mapCoordinates = trim(setting('store_coordinates') ?? '');
    $mapQuery = $mapCoordinates !== '' ? $mapCoordinates : ($contactAddress ?? '');
    $directionsUrl = 'https://www.google.com/maps/dir/?'.http_build_query(['api' => 1, 'destination' => $mapQuery], '', '&', PHP_QUERY_RFC3986);
    $mapUrl = 'https://www.google.com/maps?'.http_build_query(['q' => $mapQuery], '', '&', PHP_QUERY_RFC3986);
@endphp
  <main id="mainContent" class="contacts-page">
    <section class="contacts-hero" aria-labelledby="contactsTitle">
      <div class="container">
        <nav class="breadcrumbs" aria-label="{{ __('Хлебные крошки') }}"><a href="{{ route('index') }}">{{ __('Главная') }}</a><span aria-hidden="true">/</span><span>{{ __('Контакты') }}</span></nav>
        <div class="contact-intro">
          <div class="contact-intro-copy">
            <p class="eyebrow">KINGAVTO · {{ __('НА СВЯЗИ') }}</p>
            <h1 id="contactsTitle">{{ __('Наши') }} <span>{{ __('контакты') }}</span></h1>
            <p>{{ setting('contact_page_short_text') }}</p>
            <div class="contact-hero-actions">
              @if(!empty($contactPhones[0]))
              <a class="btn btn-red" href="tel:{{ preg_replace('/[^+0-9]/', '', $contactPhones[0]) }}"><svg class="icon"><use href="#i-phone"/></svg>{{ __('Позвонить нам') }}</a>
              @endif
              <a class="btn btn-outline" href="#location"><svg class="icon"><use href="#i-pin"/></svg>{{ __('Как добраться') }}</a>
            </div>
          </div>
          <div class="contact-hero-graphic" aria-hidden="true"><div class="hero-halo"></div><img src="{{ asset('storage/'.setting('contact_page_preview')) }}" width="740" height="385" alt="" loading="eager"></div>
        </div>
      </div>
    </section>

    <section class="contacts-section" id="contacts-main" aria-labelledby="contactDetailsTitle">
      <div class="container">
        <div class="contact-section-title"><p class="eyebrow">{{ __('ВСЕ СПОСОБЫ СВЯЗИ') }}</p><h2 id="contactDetailsTitle">{{ __('Мы всегда рядом') }}</h2></div>
        <div class="contact-cards">
          <article class="contact-card contact-card--phones">
            <div class="contact-card-icon"><svg class="icon"><use href="#i-phone"/></svg></div>
            <h3>{{ __('Телефоны') }}</h3>
            <p class="card-description">{{ __('Позвоните нам для консультации') }}</p>
            <div class="contact-phone-list">
              @foreach($contactPhones as $phone)
                <a href="tel:{{ preg_replace('/[^+0-9]/', '', $phone) }}">{{ $phone }}<svg class="icon"><use href="#i-arrow"/></svg></a>
              @endforeach
            </div>
          </article>
          <article class="contact-card">
            <div class="contact-card-icon"><svg class="icon"><use href="#i-mail"/></svg></div>
            <h3>{{ __('Электронная почта') }}</h3>
            <p class="card-description">{{ __('Пишите нам по вопросам заказа') }}</p>
            <a class="contact-email" href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
            <a class="contact-card-link" href="mailto:{{ $contactEmail }}">{{ __('Написать письмо') }} <svg class="icon"><use href="#i-arrow"/></svg></a>
          </article>
          <article class="contact-card">
            <div class="contact-card-icon"><svg class="icon"><use href="#i-pin"/></svg></div>
            <h3>{{ __('Адрес магазина') }}</h3>
            <p class="card-description">{{ __('Адрес нашего магазина') }}</p>
            <p class="contact-address">{{ $contactAddress }}</p>
            <div class="contact-card-actions"><a class="contact-card-link" href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer">{{ __('Маршрут') }} <svg class="icon"><use href="#i-arrow"/></svg></a><button class="copy-address" id="copyAddress" type="button" data-address="{{ $contactAddress }}" data-default-label="{{ __('Скопировать адрес') }}" data-success-label="{{ __('Адрес скопирован!') }}" data-error-label="{{ __('Не удалось скопировать автоматически — выделите адрес на странице.') }}">{{ __('Скопировать адрес') }}</button><span class="contact-copy-status" id="copyAddressStatus" role="status" aria-live="polite"></span></div>
          </article>
          <article class="contact-card contact-card--hours">
            <div class="contact-card-icon"><svg class="icon"><use href="#i-calendar"/></svg></div>
            <h3>{{ __('График работы') }}</h3>

            <p class="contact-working-hours">{{ $workingHours }}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="location-section" id="location" aria-labelledby="locationTitle">
      <div class="container">
        <div class="location-head"><div><p class="eyebrow">{{ __('ПРИЕЗЖАЙТЕ К НАМ') }}</p><h2 id="locationTitle">{{ __(':name на карте', ['name' => config('app.name')]) }}</h2><p>{{ $contactAddress }}</p></div><a class="btn btn-red" href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer"><svg class="icon"><use href="#i-pin"/></svg>{{ __('Проложить маршрут') }}</a></div>
        <div class="map-panel">
          @if($mapIframe)
            {!! $mapIframe !!}
          @elseif($mapQuery !== '')
            <iframe title="{{ config('app.name') }} — {{ $contactAddress }}" src="{{ $mapUrl }}&amp;z=16&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          @endif
          <div class="map-location-overlay"><span class="map-location-dot"><svg class="icon"><use href="#i-pin"/></svg></span><div><strong>{{ config('app.name') }}</strong><span>{{ $contactAddress }}</span><a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer">{{ __('Открыть в Google Maps') }} <svg class="icon"><use href="#i-arrow"/></svg></a></div></div>
        </div>
        <p class="map-note">{{ __('Если карта не отображается,') }} <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">{{ __('откройте адрес в Google Maps') }}</a>.</p>
      </div>
    </section>
    <section class="help-strip"><div class="container help-strip-inner"><div><p class="eyebrow">{{ __('ПОМОЖЕМ С ВЫБОРОМ') }}</p><h2>{{ __('Нужна помощь с автозапчастями?') }}</h2><p>{{ __('Свяжитесь с нами удобным способом — поможем уточнить наличие и подобрать нужную деталь.') }}</p></div>@if(!empty($contactPhones[0]))<a class="btn btn-red" href="tel:{{ preg_replace('/[^+0-9]/', '', $contactPhones[0]) }}">{{ __('Связаться с менеджером') }} <svg class="icon"><use href="#i-arrow"/></svg></a>@endif</div></section>
  </main>


@endsection