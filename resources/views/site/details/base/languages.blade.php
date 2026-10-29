@if(count($languageButtons) > 1)
    <div class="site-languages" role="group" aria-label="{{ __('Мова сайту') }}">
        @foreach($languageButtons as $languageButton)
            <a class="site-language{{ $languageButton['active'] ? ' is-active' : '' }}"
                href="{{ $languageButton['url'] }}" lang="{{ $languageButton['locale'] }}" hreflang="{{ $languageButton['locale'] }}"
                title="{{ $languageButton['title'] }}" aria-label="{{ $languageButton['title'] }}"
                @if($languageButton['active']) aria-current="true" @endif>{{ $languageButton['short'] }}</a>
        @endforeach
    </div>
@endif
