@extends('layouts.auth')
@php
    $titles = ['login' => __('Вход'), 'register' => __('Регистрация'), 'forgot' => __('Восстановление пароля'), 'reset' => __('Новый пароль')];
    $actions = ['login' => 'login.store', 'register' => 'register.store', 'forgot' => 'password.email', 'reset' => 'password.update'];
    $buttons = ['login' => __('Войти'), 'register' => __('Создать аккаунт'), 'forgot' => __('Отправить ссылку'), 'reset' => __('Сохранить пароль')];
@endphp
@section('title', $titles[$screen])
@section('content')
    <h1>{{ $titles[$screen] }}</h1>
    @if($screen === 'forgot')<p>{{ __('Укажите email аккаунта. Мы отправим ссылку для создания нового пароля.') }}</p>@endif
    <form method="POST" action="{{ route($actions[$screen]) }}">
        @csrf
        @if($screen === 'reset')<input type="hidden" name="token" value="{{ $token }}">@endif
        @if($screen === 'register')
            <label for="name">{{ __('Имя') }}</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" autofocus>
        @endif
        <label for="email">{{ __('Email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email ?? '') }}" required maxlength="255" autocomplete="email" @if($screen !== 'register') autofocus @endif>
        @if($screen !== 'forgot')
            <label for="password">{{ __('Пароль') }}</label>
            <input id="password" name="password" type="password" required @if($screen !== 'login') minlength="8" @endif autocomplete="{{ $screen === 'login' ? 'current-password' : 'new-password' }}">
        @endif
        @if(in_array($screen, ['register', 'reset']))
            <label for="password_confirmation">{{ __('Повторите пароль') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
            <p>{{ __('Не менее 8 символов.') }}</p>
        @endif
        @if($screen === 'login')<label class="check"><input type="checkbox" name="remember" value="1"> {{ __('Запомнить меня') }}</label>@endif
        <button type="submit">{{ $buttons[$screen] }}</button>
    </form>
    <nav class="links" aria-label="{{ __('Аккаунт') }}">
        @if($screen === 'login')
            <a href="{{ route('register') }}">{{ __('Создать аккаунт') }}</a><a href="{{ route('password.request') }}">{{ __('Забыли пароль?') }}</a>
        @else
            <a href="{{ route('login') }}">{{ __('Вернуться ко входу') }}</a>
            @if($screen === 'reset')<a href="{{ route('password.request') }}">{{ __('Запросить новую ссылку') }}</a>@endif
        @endif
    </nav>
@endsection
