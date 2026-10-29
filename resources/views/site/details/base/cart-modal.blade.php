<div data-cart-ui data-index-url="{{ route('cart.index') }}" data-store-url="{{ route('cart.store') }}" data-csrf="{{ csrf_token() }}" data-locale="{{ app()->getLocale() }}" data-empty-label="{{ __('В корзине пока нет товаров.') }}" data-loading-label="{{ __('Загрузка…') }}" data-error-label="{{ __('Не удалось обновить корзину. Попробуйте ещё раз.') }}" data-remove-label="{{ __('Удалить товар') }}" data-quantity-label="{{ __('Количество') }}" data-unavailable-label="{{ __('Товар отсутствует в наличии.') }}">
    <dialog class="shop-cart-dialog" id="cartProductDialog" aria-labelledby="cartProductTitle">
        <div class="shop-cart-heading"><h2 id="cartProductTitle">{{ __('Добавить в корзину') }}</h2><button class="shop-cart-close" type="button" data-cart-close aria-label="{{ __('Закрыть') }}"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button></div>
        <form id="cartProductForm" method="POST" action="{{ route('cart.store') }}">
            @csrf
            <input type="hidden" name="product_id">
            <div class="shop-cart-product">
                <div class="shop-cart-product-photo"><img id="cartProductImage" alt="" hidden><svg class="icon" id="cartProductPlaceholder" aria-hidden="true" hidden><use href="#i-no-image"/></svg></div>
                <div><h3 id="cartProductName"></h3><p class="shop-cart-unit-price" id="cartProductPrice"></p></div>
            </div>
            <label class="shop-cart-quantity-label" for="cartProductQuantity">{{ __('Количество') }}</label>
            <div class="shop-cart-quantity-control">
                <button type="button" data-cart-quantity-step="-1" aria-label="{{ __('Уменьшить количество') }}">−</button>
                <input id="cartProductQuantity" type="number" name="quantity" value="1" min="1" max="{{ App\Models\Cart::MAX_QUANTITY }}" step="1" required inputmode="numeric">
                <button type="button" data-cart-quantity-step="1" aria-label="{{ __('Увеличить количество') }}">+</button>
            </div>
            <p class="shop-cart-error" id="cartProductError" role="alert"></p>
            <div class="shop-cart-actions"><button type="submit" class="btn btn-red" id="cartProductSubmit">{{ __('Добавить в корзину') }}</button><button type="button" class="btn btn-outline" data-cart-close>{{ __('Продолжить покупки') }}</button></div>
        </form>
    </dialog>
    <dialog class="shop-cart-dialog shop-cart-dialog--wide" id="cartDialog" aria-labelledby="cartDialogTitle">
        <div class="shop-cart-heading"><h2 id="cartDialogTitle">{{ __('Корзина') }}</h2><button class="shop-cart-close" type="button" data-cart-close aria-label="{{ __('Закрыть') }}"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button></div>
        <div class="shop-cart-items" id="cartItems" aria-live="polite"></div>
        <p class="shop-cart-error" id="cartError" role="alert"></p>
        <div class="shop-cart-total"><span>{{ __('Итого') }}</span><strong id="cartTotal">0 грн</strong></div>
        <div class="shop-cart-actions"><a class="btn btn-red" id="cartCheckoutLink" href="{{ route('order.checkout') }}" hidden>{{ __('Оформить заказ') }}</a><button type="button" class="btn btn-outline" data-cart-close>{{ __('Продолжить покупки') }}</button></div>
    </dialog>
</div>
