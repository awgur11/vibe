@extends('layouts.base')
@section('title', __('Мои заказы'))
@section('content')
<main class="section"><div class="container"><h1>{{ __('Мои заказы') }}</h1>
@forelse($orders as $order)
<article class="product-detail-panel account-order"><h2>{{ __('Заказ №:number', ['number' => $order->id]) }}</h2><p>{{ $order->created_at->format('d.m.Y H:i') }} · {{ __(\App\Models\Order::STATUSES[$order->status] ?? $order->status) }}</p>
@foreach($order->items as $item)<div class="account-order-item"><span>{{ $item->title }} × {{ $item->quantity }}</span><strong>{{ $item->total }} грн</strong></div>@endforeach
<p><strong>{{ __('Итого') }}: {{ $order->total }} грн</strong></p>
</article>
@empty<p>{{ __('У вас пока нет заказов.') }}</p>@endforelse
@if($orders->hasPages())<nav class="pagination">@if($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}">{{ __('Назад') }}</a>@endif<span>{{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>@if($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">{{ __('Далее') }}</a>@endif</nav>@endif
</div></main>
@endsection
