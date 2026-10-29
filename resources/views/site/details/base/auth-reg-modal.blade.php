@guest
<dialog id="authRegDialog" class="auth-reg-dialog" aria-labelledby="authRegTitle" data-auth-modal data-initial-screen="{{ session('auth_screen') }}" data-csrf-url="{{ route('order.csrf-token') }}" data-error="{{ __('Не удалось выполнить запрос. Попробуйте ещё раз.') }}">
    <button type="button" class="auth-reg-close" data-auth-close aria-label="{{ __('Закрыть') }}"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button>
    <p class="eyebrow">KINGAVTO</p><h2 id="authRegTitle">{{ __('Аккаунт') }}</h2>
    <div class="auth-reg-tabs" role="group" aria-label="{{ __('Аккаунт') }}"><button type="button" data-auth-tab="login" aria-pressed="true">{{ __('Вход') }}</button><button type="button" data-auth-tab="register" aria-pressed="false">{{ __('Регистрация') }}</button></div>
    <div data-auth-errors role="alert" class="auth-reg-errors" hidden></div>
    <form method="POST" action="{{ route('login.store') }}" data-auth-form="login">
        @csrf
        <label for="auth-login-email">{{ __('Электронная почта') }}</label><input id="auth-login-email" name="email" type="email" required maxlength="255" autocomplete="email">
        <label for="auth-login-password">{{ __('Пароль') }}</label><input id="auth-login-password" name="password" type="password" required autocomplete="current-password">
        <label class="auth-reg-remember"><input type="checkbox" name="remember" value="1">{{ __('Запомнить меня') }}</label>
        <button class="btn btn-red" type="submit">{{ __('Войти') }}</button>
        <a class="auth-reg-forgot" href="{{ route('password.request') }}">{{ __('Забыли пароль?') }}</a>
    </form>
    <form method="POST" action="{{ route('register.store') }}" data-auth-form="register" hidden>
        @csrf
        <label for="auth-register-name">{{ __('Имя') }}</label><input id="auth-register-name" name="name" required maxlength="255" autocomplete="name">
        <label for="auth-register-email">{{ __('Электронная почта') }}</label><input id="auth-register-email" name="email" type="email" required maxlength="255" autocomplete="email">
        <label for="auth-register-password">{{ __('Пароль') }}</label><input id="auth-register-password" name="password" type="password" required minlength="8" autocomplete="new-password">
        <label for="auth-register-confirmation">{{ __('Повторите пароль') }}</label><input id="auth-register-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
        <p class="auth-reg-hint">{{ __('Не менее 8 символов.') }}</p>
        <button class="btn btn-red" type="submit">{{ __('Создать аккаунт') }}</button>
    </form>
</dialog>
@endguest
