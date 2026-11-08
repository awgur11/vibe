<x-mail::message>
# {{ __($forAdmin ? 'Новый заказ №:number' : 'Ваш заказ №:number', ['number' => $order->id]) }}

{{ __($forAdmin ? 'На сайте оформлен новый заказ.' : 'Спасибо! Ваш заказ принят.') }}

**{{ __('Имя') }}:** {{ $order->user->name }}  
**{{ __('Телефон') }}:** {{ $order->user->phone }}  
@if (! $order->user->has_fake_email)
**{{ __('Электронная почта') }}:** {{ $order->user->email }}  
@endif
@if ($order->user->customer_type === 'company')
**{{ __('Название компании') }}:** {{ $order->user->company_name }}  
**{{ __('Юридический адрес') }}:** {{ $order->user->legal_address }}  
**{{ __('ИНН') }}:** {{ $order->user->tax_id }}  
**{{ __('КПП') }}:** {{ $order->user->kpp }}  
@endif

<x-mail::table>
| {{ __('Товар') }} | {{ __('Количество') }} | {{ __('Цена') }} | {{ __('Итого') }} |
| :--- | :---: | ---: | ---: |
@foreach ($order->items as $item)
| {{ str_replace(['|', "\r", "\n"], ' ', $item->title) }}@if ($item->articule) ({{ str_replace(['|', "\r", "\n"], ' ', $item->articule) }})@endif | {{ $item->quantity }} | {{ $item->price }} ₴ | {{ $item->total }} ₴ |
@endforeach
</x-mail::table>

**{{ __('Итого') }}: {{ $order->total }} ₴**

**{{ __('Способ оплаты') }}:** {{ __(\App\Models\Order::PAYMENT_METHODS[$order->payment_method]) }}

@if ($order->delivery)
**{{ __('Доставка') }}:** {{ __(\App\Models\Order::DELIVERY_METHODS[$order->delivery->delivery_method]) }}

@if ($order->delivery->delivery_method === 'pickup')
{{ $order->delivery->pickup_address }}
@else
{{ $order->delivery->delivery_city }}  
{{ $order->delivery->delivery_warehouse }}
@endif
@endif

@if (! $forAdmin && $password !== null)
**{{ __('Электронная почта') }}:** {{ $order->user->email }}  
**{{ __('Пароль') }}:** {{ $password }}

<x-mail::button :url="route('login')">{{ __('Войти') }}</x-mail::button>
@endif

{{ config('app.name') }}
</x-mail::message>
