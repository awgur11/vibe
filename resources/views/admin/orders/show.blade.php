@extends('layouts.dashboard')
@section('title', __('Заказ №:number', ['number' => $order->id]))
@section('content')
    <div class="heading"><div><h1>{{ __('Заказ №:number', ['number' => $order->id]) }}</h1><p>{{ $order->created_at->format('d.m.Y H:i') }}</p></div><a class="button secondary" href="{{ route('orders.index') }}">{{ __('Заказы') }}</a></div>
    <div class="admin-form">
        <form class="panel" method="POST" action="{{ route('orders.update', $order) }}">
            @csrf @method('PATCH')
            <div class="field"><label for="order-status">{{ __('Статус') }}</label><select id="order-status" name="status" required>
                @foreach(\App\Models\Order::STATUSES as $value => $label)<option value="{{ $value }}" @selected(old('status', $order->status) === $value)>{{ __($label) }}</option>@endforeach
            </select></div>
            <button class="button" type="submit">{{ __('Сохранить') }}</button>
        </form>
        <div class="panel">
            <h2>{{ __('Клиент') }}</h2>
            <dl>
                @foreach(['customer_type' => 'Тип клиента', 'name' => 'Имя', 'phone' => 'Телефон', 'email' => 'Электронная почта', 'company_name' => 'Название компании', 'legal_address' => 'Юридический адрес', 'tax_id' => 'ИНН', 'kpp' => 'КПП'] as $field => $label)
                    @if(filled($order->$field))<dt><strong>{{ __($label) }}</strong></dt><dd>{{ $field === 'customer_type' ? __(\App\Models\Order::CUSTOMER_TYPES[$order->$field] ?? $order->$field) : $order->$field }}</dd>@endif
                @endforeach
            </dl>
            <h2>{{ __('Оплата и доставка') }}</h2>
            <p>{{ __(\App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method) }}</p>
            @if($order->delivery)
                <p>{{ __(\App\Models\Order::DELIVERY_METHODS[$order->delivery->delivery_method] ?? $order->delivery->delivery_method) }}</p>
                @if($order->delivery->delivery_method === 'pickup')
                    <p>{!! nl2br(e($order->delivery->pickup_address ?? '')) !!}</p>
                @else
                    <p>{{ $order->delivery->delivery_city }}<br>{{ $order->delivery->delivery_warehouse }}</p>
                @endif
            @else
                <p>{{ __('Данные доставки не указаны.') }}</p>
            @endif
        </div>
        <div class="panel">
            <h2>{{ __('Состав заказа') }}</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>{{ __('Товар') }}</th><th>{{ __('Артикул') }}</th><th>{{ __('Количество') }}</th><th>{{ __('Цена') }}</th><th>{{ __('Итого') }}</th></tr></thead>
                <tbody>@foreach($order->items as $item)<tr><td>{{ $item->title }}</td><td>{{ $item->articule ?: '—' }}</td><td>{{ $item->quantity }}</td><td>{{ $item->price }} ₴</td><td>{{ $item->total }} ₴</td></tr>@endforeach</tbody>
            </table></div>
            <p><strong>{{ __('Итого') }}: {{ $order->total }} ₴</strong></p>
        </div>
    </div>
@endsection
