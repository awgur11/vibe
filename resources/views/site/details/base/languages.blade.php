@if(count($languageButtons) > 1)
    <div class="btn-group flex-shrink-0" role="group" aria-label="{{ __('Мова сайту') }}">
        @foreach($languageButtons as $languageButton)
            <a class="btn btn-sm {{ $languageButton['active'] ? 'btn-primary' : 'btn-outline-secondary border-0' }}"
                href="{{ $languageButton['url'] }}" lang="{{ $languageButton['locale'] }}" hreflang="{{ $languageButton['locale'] }}"
                title="{{ $languageButton['title'] }}" aria-label="{{ $languageButton['title'] }}"
                @if($languageButton['active']) aria-current="true" @endif>{{ $languageButton['short'] }}</a>
        @endforeach
    </div>
@endif
