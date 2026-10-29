@extends('layouts.base')
@section('title', __('Подтвердите электронную почту'))
@section('content')
<main class="section"><div class="container"><section class="product-detail-panel verification-panel">
    <svg class="icon verification-icon" aria-hidden="true"><use href="#i-mail"/></svg>
    <h1>{{ __('Подтвердите электронную почту') }}</h1>
    <p>{{ __('Мы отправили письмо со ссылкой на адрес') }} <strong>{{ auth()->user()->email }}</strong>.</p>
    <p>{{ __('Перейдите по ссылке в письме, чтобы подтвердить аккаунт. Если письма нет, проверьте папку «Спам» или запросите его повторно.') }}</p>
    @if(session('verification_sent'))<p class="product-review-status" role="status">{{ session('verification_sent') }}</p>@endif
    <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn btn-red" type="submit">{{ __('Отправить письмо повторно') }}</button></form>
    <a class="content-page-return" href="{{ route('index') }}">{{ __('На главную') }}</a>
</section></div></main>
@endsection
