<footer class="site-footer" id="contacts">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="{{ asset('storage/'.setting('footer_logo')) }}" width="344" height="120" alt="{{ config('app.name') }}">
      </div>
      <div class="footer-catalog">
        <h3>{{ __('Каталог') }}</h3>
      @foreach($menuCatalogs->collapse()->where('parent_id', '') as $mc)
        <a href="{{ route('catalog', ['catalogSlag' => $mc->slag]) }}">{{ $mc->title }}</a>
      @endforeach
      </div>
      <div class="footer-column">
        <h3>{{ __('Покупателю') }}</h3>
      @foreach($menuPages as $mp)
        <a href="{{ route('page', ['slag' => $mp->slag]) }}">{{ $mp->title }}</a>
      @endforeach
      </div>
      <div class="footer-column">
        <h3>{{ __('Контакты') }}</h3>
      @foreach((setting('phones') ?? []) as  $ph)
        <a href="tel:{{ $ph }}"><svg class="icon"><use href="#i-phone"/></svg>{{ $ph }}</a>
      @endforeach
        <a href="mailto:{{ setting('site_email') }}"><svg class="icon"><use href="#i-mail"/></svg>{{ setting('site_email') }}</a>
        <span class="footer-contact"><svg class="icon"><use href="#i-pin"/></svg>{{ setting('address') }}</span>
        <small class="footer-contact footer-hours">
          <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <span>{!! nl2br(e(setting('working_hours'))) !!}</span>
        </small>
      </div>
      <p class="footer-note">{!! nl2br(e(setting('footer_note'))) !!}</p>
    </div>
    <div class="footer-bottom">
      <span>© <span id="year"></span> {{ config('app.name') }} · {{ __('Все права защищены.') }}</span>
    </div>
  </div>
</footer>