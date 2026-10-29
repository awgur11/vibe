@extends('layouts.dashboard')
@section('title', __('Заказы'))
@section('content')
    <div class="heading"><h1>{{ __('Заказы') }}</h1></div>
    <div class="panel">
        <div class="table-wrap"><table>
            <thead><tr><th>№</th><th>{{ __('Дата') }}</th><th>{{ __('Клиент') }}</th><th>{{ __('Телефон') }}</th><th>{{ __('Позиций') }}</th><th>{{ __('Итого') }}</th><th>{{ __('Статус') }}</th><th>{{ __('Действия') }}</th></tr></thead>
            <tbody>
            @forelse($orders as $order)
                <tr>
                    <td><a href="{{ route('orders.show', $order) }}">#{{ $order->id }}</a></td>
                    <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                    <td>{{ $order->name }}@if($order->company_name)<br>{{ $order->company_name }}@endif</td>
                    <td>{{ $order->phone }}</td><td>{{ $order->items_count }}</td><td>{{ $order->total }} ₴</td>
                    <td><span class="badge {{ $order->status === 'new' ? 'base' : ($order->status === 'completed' ? 'active' : '') }}">{{ __(\App\Models\Order::STATUSES[$order->status] ?? $order->status) }}</span></td>
                    <td><a class="button secondary" href="{{ route('orders.show', $order) }}">{{ __('Просмотр') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('Заказов пока нет.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($orders->hasPages())
            <nav class="pagination" aria-label="{{ __('Заказы') }}">
                @if($orders->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $orders->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>
                @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">{{ __('Далее') }}</a>@else<span>{{ __('Далее') }}</span>@endif
            </nav>
        @endif
    </div>
@endsection
