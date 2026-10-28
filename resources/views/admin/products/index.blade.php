@extends('layouts.dashboard')
@section('title', __('Товари'))
@section('content')
    <div class="heading"><div><h1>{{ __('Товари') }}</h1><p>{{ __('Керування товарами') }}</p></div><button type="button" class="button" data-product-create>+ {{ __('Додати товар') }}</button></div>
    <div class="panel">
        <form method="GET" action="{{ route('products.index') }}" style="margin-bottom:20px">
            <div class="field">
                <label for="product-search">{{ __('Пошук товарів') }}</label>
                <input id="product-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="{{ __('Назва, артикул або адреса товару') }}">
            </div>
            <div class="actions">
                <button class="button" type="submit">{{ __('Знайти') }}</button>
                @if($search !== '')<a class="button secondary" href="{{ route('products.index') }}">{{ __('Скинути') }}</a>@endif
            </div>
        </form>
        <div class="table-wrap"><table>
            <thead><tr><th>{{ __('Назва') }}</th><th>{{ __('Каталог') }}</th><th>{{ __('Ціна') }}</th><th>{{ __('Знижка, %') }}</th><th>{{ __('Кінцева ціна') }}</th><th>{{ __('Наявність') }}</th><th>{{ __('Публікація') }}</th><th>{{ __('Дії') }}</th></tr></thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->translated('title', $baseLanguage) ?? $product->slag }}</td>
                    <td>{{ $product->catalog?->translated('title', $baseLanguage) ?? '—' }}</td>
                    <td>{{ $product->price }}</td><td>{{ $product->discount }}</td><td>{{ $product->final_price }}</td>
                    <td>{{ __(\App\Models\Product::AVAILABILITY[$product->available]) }}</td>
                    <td><span class="badge {{ $product->public ? 'active' : '' }}">{{ $product->public ? __('Опубліковано') : __('Чернетка') }}</span></td>
                    <td><div class="actions"><a class="button secondary" href="{{ route('products.edit', $product) }}">{{ __('Редагувати') }}</a><form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Видалити цей товар?')) }})">@csrf @method('DELETE')<button class="button danger" type="submit">{{ __('Видалити') }}</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="8">{{ $search !== '' ? __('Товарів не знайдено.') : __('Товари ще не додані.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($products->hasPages())
            <nav class="pagination" aria-label="{{ __('Сторінки товарів') }}">
                @if($products->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $products->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $products->currentPage() }} / {{ $products->lastPage() }}</span>
                @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}">{{ __('Далі') }}</a>@else<span>{{ __('Далі') }}</span>@endif
            </nav>
        @endif
    </div>
    <dialog id="product-create-dialog" aria-labelledby="product-create-title">
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            <h2 id="product-create-title">{{ __('Новий товар') }}</h2>
            <div class="field"><label for="product-title">{{ __('Назва товару') }}</label><input id="product-title" name="title" value="{{ old('title') }}" required maxlength="255" autofocus>@error('title')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div class="actions"><button class="button" type="submit">{{ __('Створити') }}</button><button class="button secondary" type="button" data-product-cancel>{{ __('Скасувати') }}</button></div>
        </form>
    </dialog>
    @push('scripts')
        <style>#product-create-dialog{width:min(480px,calc(100vw - 32px));padding:24px;border:1px solid #e6e6eb;border-radius:16px;color:#20202a;box-shadow:0 20px 80px #20202a33}#product-create-dialog::backdrop{background:#20202a80}#product-create-dialog h2{margin-top:0}</style>
        <script>
            const productDialog = document.getElementById('product-create-dialog');
            document.querySelector('[data-product-create]').addEventListener('click', () => productDialog.showModal());
            document.querySelector('[data-product-cancel]').addEventListener('click', () => productDialog.close());
            @if($errors->has('title')) productDialog.showModal(); @endif
        </script>
    @endpush
@endsection
