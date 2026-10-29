<dialog id="callbackDialog" class="auth-reg-dialog" data-callback-modal aria-labelledby="callbackTitle" data-csrf-url="{{ route('order.csrf-token') }}" data-error="{{ __('Не удалось отправить заявку. Позвоните нам или попробуйте позже.') }}">
    <button type="button" class="auth-reg-close" data-callback-close aria-label="{{ __('Закрыть') }}"><svg class="icon"><use href="#i-close"/></svg></button>
    <p class="eyebrow">KINGAVTO</p><h2 id="callbackTitle">{{ __('Мы перезвоним') }}</h2>
    <p>{{ __('Оставьте имя и телефон, и мы свяжемся с вами.') }}</p>
    <div data-callback-status role="status" hidden></div>
    <form method="POST" action="{{ route('callback.store') }}" data-callback-form>
        @csrf
        <label for="callbackName">{{ __('Имя') }}</label><input id="callbackName" name="name" required maxlength="100" autocomplete="name">
        <label for="callbackPhone">{{ __('Телефон') }}</label><input id="callbackPhone" type="tel" name="phone" required maxlength="40" autocomplete="tel" placeholder="+380">
        <button type="submit" class="btn btn-red">{{ __('Жду звонка') }}</button>
    </form>
</dialog>
