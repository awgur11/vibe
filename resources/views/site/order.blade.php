@extends('layouts.base')
@section('title', __('Оформление заказа'))

@section('content')
    @php($isCompany = old('customer_type', 'individual') === 'company')
    @php($deliveryMethod = old('delivery_method', 'nova_poshta'))
    <main class="checkout-page" id="mainContent">
        <div class="container">
            <nav class="blog-crumbs" aria-label="{{ __('Хлебные крошки') }}"><a href="{{ route('index') }}">{{ __('Главная') }}</a><span aria-hidden="true">/</span><span>{{ __('Оформление заказа') }}</span></nav>
            <div class="checkout-heading"><p class="eyebrow">{{ config('app.name') }}</p><h1>{{ __('Оформление заказа') }}</h1></div>
            @if(session('placed_order'))
                <section class="checkout-message checkout-success" role="status">
                    <svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>
                    <h2>{{ __('Заказ №:number оформлен', ['number' => session('placed_order.id')]) }}</h2>
                    <p>{{ __('Спасибо за заказ! Мы свяжемся с вами для подтверждения.') }}</p>
                    <strong>{{ __('Итого') }}: {{ session('placed_order.total') }} грн</strong>
                    <a class="btn btn-red" href="{{ route('index') }}">{{ __('Продолжить покупки') }}</a>
                </section>
            @elseif($cartItems === [])
                <section class="checkout-message"><svg class="icon" aria-hidden="true"><use href="#i-cart"/></svg><h2>{{ __('В корзине пока нет товаров.') }}</h2><p>{{ __('Добавьте товары в корзину, чтобы оформить заказ.') }}</p><a class="btn btn-red" href="{{ route('index') }}">{{ __('Продолжить покупки') }}</a></section>
            @else
                <div class="checkout-layout">
                    <form method="POST" action="{{ route('order.store') }}" class="checkout-form" data-checkout-form data-csrf-url="{{ route('order.csrf-token') }}" data-csrf-error="{{ __('Не удалось обновить сессию. Попробуйте оформить заказ ещё раз.') }}">
                        @csrf
                        @if($errors->any())<div class="checkout-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                        <section class="checkout-panel">
                            <h2>{{ __('Данные покупателя') }}</h2>
                            <fieldset class="checkout-customer-types">
                                <legend class="sr-only">{{ __('Тип покупателя') }}</legend>
                                @foreach(App\Models\Order::CUSTOMER_TYPES as $value => $label)
                                    <label><input type="radio" name="customer_type" value="{{ $value }}" @checked(old('customer_type', 'individual') === $value) required><span>{{ __($label) }}</span></label>
                                @endforeach
                            </fieldset>
                            <div class="checkout-company-fields" data-checkout-company @if(!$isCompany) hidden @endif>
                                <div class="checkout-field">
                                    <label for="checkout-company-name">{{ __('Название компании') }} <span aria-hidden="true">*</span></label>
                                    <input id="checkout-company-name" type="text" name="company_name" value="{{ old('company_name') }}" maxlength="255" autocomplete="organization" data-company-required @disabled(!$isCompany) @required($isCompany)>
                                </div>
                                <div class="checkout-field">
                                    <label for="checkout-legal-address">{{ __('Юридический адрес') }}</label>
                                    <textarea id="checkout-legal-address" name="legal_address" rows="2" maxlength="1000" @disabled(!$isCompany)>{{ old('legal_address') }}</textarea>
                                </div>
                                <div class="checkout-field-row">
                                    <div class="checkout-field"><label for="checkout-tax-id">{{ __('ИНН') }}</label><input id="checkout-tax-id" type="text" name="tax_id" value="{{ old('tax_id') }}" maxlength="32" @disabled(!$isCompany)></div>
                                    <div class="checkout-field"><label for="checkout-kpp">{{ __('КПП') }}</label><input id="checkout-kpp" type="text" name="kpp" value="{{ old('kpp') }}" maxlength="32" @disabled(!$isCompany)></div>
                                </div>
                            </div>
                            <div class="checkout-field">
                                <label for="checkout-name"><span data-checkout-name-label data-individual-label="{{ __('Имя') }}" data-company-label="{{ __('ПІБ') }}">{{ $isCompany ? __('ПІБ') : __('Имя') }}</span> <span aria-hidden="true">*</span></label>
                                <input id="checkout-name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                            </div>
                            <div class="checkout-field-row">
                                <div class="checkout-field"><label for="checkout-phone">{{ __('Телефон') }} <span aria-hidden="true">*</span></label><input id="checkout-phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="40" autocomplete="tel"></div>
                                <div class="checkout-field"><label for="checkout-email">{{ __('Электронная почта') }}</label><input id="checkout-email" type="email" name="email" value="{{ old('email') }}" maxlength="254" autocomplete="email"></div>
                            </div>
                        </section>
                        <section class="checkout-panel" data-checkout-delivery data-cities-url="{{ route('delivery.nova_poshta.cities') }}" data-warehouses-url="{{ route('delivery.nova_poshta.warehouses') }}" data-loading-label="{{ __('Загрузка…') }}" data-error-label="{{ __('Не удалось загрузить данные Новой почты. Попробуйте ещё раз.') }}" data-minimum-label="{{ __('Введите минимум 2 символа названия города.') }}" data-no-cities-label="{{ __('Города не найдены. Уточните название.') }}" data-no-warehouses-label="{{ __('В выбранном городе нет доступных отделений.') }}" data-city-required-label="{{ __('Выберите город из подсказок.') }}" data-warehouse-required-label="{{ __('Выберите отделение') }}">
                            <h2>{{ __('Способ доставки') }}</h2>
                            <fieldset class="checkout-customer-types">
                                <legend class="sr-only">{{ __('Способ доставки') }}</legend>
                                @foreach(App\Models\Order::DELIVERY_METHODS as $value => $label)
                                    <label><input type="radio" name="delivery_method" value="{{ $value }}" @checked($deliveryMethod === $value) required><span>{{ __($label) }}</span></label>
                                @endforeach
                            </fieldset>
                            <div data-nova-poshta-fields @if($deliveryMethod !== 'nova_poshta') hidden @endif>
                                <div class="checkout-field checkout-city-field" data-city-field>
                                    <label for="delivery-city-input">{{ __('Город') }} <span aria-hidden="true">*</span></label>
                                    <input id="delivery-city-input" type="text" name="delivery_city" value="{{ old('delivery_city') }}" placeholder="{{ __('Начните вводить название города') }}" maxlength="255" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="delivery-city-options" aria-expanded="false" @required($deliveryMethod === 'nova_poshta') @disabled($deliveryMethod !== 'nova_poshta')>
                                    <input id="delivery-city-ref" type="hidden" name="delivery_city_ref" value="{{ old('delivery_city_ref') }}" @disabled($deliveryMethod !== 'nova_poshta')>
                                    <div class="checkout-city-options" id="delivery-city-options" role="listbox" hidden></div>
                                </div>
                                <div class="checkout-field">
                                    <label for="delivery-warehouse">{{ __('Отделение') }} <span aria-hidden="true">*</span></label>
                                    <select id="delivery-warehouse" name="delivery_warehouse_ref" data-selected-warehouse="{{ old('delivery_warehouse_ref') }}" @required($deliveryMethod === 'nova_poshta') disabled><option value="">{{ __('Выберите отделение') }}</option></select>
                                </div>
                                <p class="checkout-delivery-status" data-delivery-status role="status" aria-live="polite"></p>
                            </div>
                            <div class="checkout-pickup-address" data-pickup-address @if($deliveryMethod !== 'pickup') hidden @endif><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg><div><strong>{{ __('Адрес самовывоза') }}</strong><p>{{ setting('address') }}</p></div></div>
                        </section>
                        <section class="checkout-panel">
                            <h2>{{ __('Способ оплаты') }}</h2>
                            <fieldset class="checkout-payment-methods" data-checkout-payments data-required-label="{{ __('Выберите способ оплаты.') }}">
                                <legend class="sr-only">{{ __('Способ оплаты') }}</legend>
                                @foreach(App\Models\Order::PAYMENT_METHODS as $value => $label)
                                    <label><input type="checkbox" name="payment_method" value="{{ $value }}" @checked(old('payment_method', 'cash') === $value)><span>{{ __($label) }}</span></label>
                                @endforeach
                            </fieldset>
                        </section>
                        @if($hasUnavailableItems)<p class="checkout-errors" role="alert">{{ __('В корзине есть недоступные товары. Обновите корзину перед оформлением.') }}</p>@endif
                        <p data-checkout-status role="alert" hidden></p>
                        <button class="btn btn-red checkout-submit" type="submit" @disabled($hasUnavailableItems)>{{ __('Подтвердить заказ') }} <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></button>
                    </form>
                    <aside class="checkout-panel checkout-summary" aria-labelledby="checkoutSummaryTitle">
                        <h2 id="checkoutSummaryTitle">{{ __('Ваш заказ') }}</h2>
                        <div class="checkout-items">
                            @foreach($cartItems as $item)
                                <div class="checkout-item">
                                    @if($item['preview'])<img src="{{ $item['preview'] }}" alt="" width="64" height="64" loading="lazy">@else<span class="checkout-item-placeholder" aria-hidden="true"><svg class="icon"><use href="#i-no-image"/></svg></span>@endif
                                    <div><strong>{{ $item['title'] }}</strong><small>{{ $item['quantity'] }} × {{ $item['price'] }} грн</small>@if(!$item['available'])<span class="checkout-item-unavailable">{{ __('Товар недоступен') }}</span>@endif</div>
                                    <b>{{ $item['total'] }} грн</b>
                                </div>
                            @endforeach
                        </div>
                        <div class="checkout-total"><span>{{ __('Итого') }}</span><strong>{{ $cartTotal }} грн</strong></div>
                    </aside>
                </div>
            @endif
        </div>
    </main>
@endsection
