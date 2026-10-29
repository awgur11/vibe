@extends('layouts.base')
@section('title', __('Мой профиль'))
@section('content')
<main class="section"><div class="container"><section class="product-detail-panel verification-panel">
<h1>{{ __('Мой профиль') }}</h1><p><strong>{{ __('Имя') }}:</strong> {{ $user->name }}</p><p><strong>{{ __('Электронная почта') }}:</strong> {{ $user->email }}</p>
@if(!$user->hasVerifiedEmail())<a class="btn btn-red" href="{{ route('verification.notice') }}">{{ __('Подтвердить почту') }}</a>@endif
<p><a class="content-page-return" href="{{ route('account.orders') }}">{{ __('Мои заказы') }}</a></p>
</section></div></main>
@endsection
