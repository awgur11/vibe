<x-mail::message>
# {{ __($forAdmin ? 'Новый заказ №:number' : 'Ваш заказ №:number', ['number' => $order->id]) }}

{{ __($forAdmin ? 'На сайте оформлен новый заказ.' : 'Спасибо! Ваш заказ принят.') }}

**{{ __('Имя') }}:** {{ $order->name }}  
**{{ __('Телефон') }}:** {{ $order->phone }}  
@if ($order->email)
**{{ __('Электронная почта') }}:** {{ $order->email }}  
@endif
@if ($order->customer_type === 'company')
**{{ __('Название компании') }}:** {{ $order->company_name }}  
**{{ __('Юридический адрес') }}:** {{ $order->legal_address }}  
**{{ __('ИНН') }}:** {{ $order->tax_id }}  
**{{ __('КПП') }}:** {{ $order->kpp }}  
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

{{ config('app.name') }}
</x-mail::message>
