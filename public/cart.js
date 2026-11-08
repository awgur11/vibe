jQuery(function ($) {
  'use strict';
  const $root = $('[data-cart-ui]');
  if (!$root.length) return;
  const labels = $root.data();
  const $productDialog = $root.find('#cartProductDialog');
  const $cartDialog = $root.find('#cartDialog');
  const $productForm = $root.find('#cartProductForm');
  const $productSubmit = $root.find('#cartProductSubmit');
  const $productError = $root.find('#cartProductError');
  const $cartError = $root.find('#cartError');
  const $quantityInput = $root.find('#cartProductQuantity');
  const money = value => new Intl.NumberFormat(labels.locale.replace('_', '-'),
    {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(value)) + ' грн';
  let scrollLocked = false;
  let previousOverflow = '';
  let returnFocus = null;
  let productRequest = 0;
  let productAvailable = false;

  async function request(url, method = 'GET', body = null) {
    try {
      return await $.ajax({url, method, dataType: 'json', contentType: 'application/json',
        headers: {'X-CSRF-TOKEN': labels.csrf},
        ...(body === null ? {} : {data: JSON.stringify(body)})});
    } catch (error) {
      const data = error.responseJSON || {};
      throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || labels.errorLabel);
    }
  }
  function showDialog($dialog) {
    if (!scrollLocked) {
      previousOverflow = document.body.style.overflow;
      returnFocus = document.activeElement;
      scrollLocked = true;
    }
    $('body').css('overflow', 'hidden');
    if (!$dialog.prop('open')) $dialog[0].showModal();
  }
  $productDialog.add($cartDialog).on('close', () => {
    if ($productDialog.prop('open') || $cartDialog.prop('open')) return;
    $('body').css('overflow', previousOverflow);
    scrollLocked = false;
    $(returnFocus).trigger('focus');
  }).on('click', function (event) {
    if (event.target !== this) return;
    const rect = this.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) this.close();
  });
  $root.on('click', '[data-cart-close]', function () { $(this).closest('dialog')[0].close(); });

  function productImage(url, title = '') {
    if (url) return $('<img>').attr({src: url, alt: title, loading: 'lazy'});
    return $('<span>').addClass('shop-cart-image-placeholder').attr('aria-hidden', 'true')
      .append('<svg class="icon"><use href="#i-no-image"/></svg>');
  }
  function renderCart(data) {
    $('#cartCount').text(data.count);
    $root.find('#cartCheckoutLink').prop('hidden', data.count === 0);
    $root.find('#cartTotal').text(money(data.total));
    const $items = $root.find('#cartItems').empty();
    if (!data.items.length) $('<p>').addClass('shop-cart-empty').text(labels.emptyLabel).appendTo($items);
    data.items.forEach(item => {
      const $row = $('<article>').addClass('shop-cart-line');
      const $details = $('<div>').addClass('shop-cart-line-info');
      const $title = $('<strong>').text(item.title);
      const $price = $('<small>').text(money(item.price));
      const $quantity = $('<input>').attr({type: 'number', min: 1, max: $quantityInput.attr('max'), step: 1,
        inputmode: 'numeric', 'aria-label': `${labels.quantityLabel}: ${item.title}`})
        .val(item.quantity).prop('disabled', item.can_update === false);
      const $total = $('<b>').addClass('shop-cart-line-total').text(money(item.total));
      const $remove = $('<button>').attr({type: 'button', title: labels.removeLabel,
        'aria-label': `${labels.removeLabel}: ${item.title}`}).addClass('shop-cart-remove')
        .append('<svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg>');
      $details.append($title, $price, $quantity);
      $row.append(productImage(item.preview), $details, $total, $remove).appendTo($items);
      async function changeItem(method, body = null) {
        $cartError.text('');
        $quantity.add($remove).prop('disabled', true);
        try {
          renderCart(await request(method === 'DELETE' ? item.remove_url : item.update_url, method, body));
        } catch (error) {
          $cartError.text(error.message || labels.errorLabel);
          $quantity.val(item.quantity);
        } finally {
          $quantity.prop('disabled', item.can_update === false);
          $remove.prop('disabled', false);
        }
      }
      $quantity.on('change', function () {
        if (this.reportValidity()) changeItem('PATCH', {quantity: Number($quantity.val())});
      });
      $remove.on('click', () => changeItem('DELETE'));
    });
  }
  async function loadCart() {
    const data = await request(labels.indexUrl);
    renderCart(data);
    return data;
  }
  const initialized = loadCart().catch(() => null);
  $(document).on('click', '.add-cart[data-cart-product-url]', async function (event) {
    const $button = $(this);
    if ($button.prop('disabled')) return;
    event.preventDefault();
    const sequence = ++productRequest;
    productAvailable = false;
    $productSubmit.prop('disabled', true);
    $productForm.find('[name=product_id]').val('');
    $quantityInput.val('1');
    $productError.text('');
    $root.find('#cartProductName').text(labels.loadingLabel);
    $root.find('#cartProductPrice').text('');
    $root.find('#cartProductImage, #cartProductPlaceholder').prop('hidden', true);
    showDialog($productDialog);
    try {
      await initialized;
      const data = await request($button.attr('data-cart-product-url'));
      if (sequence !== productRequest || !$productDialog.prop('open')) return;
      const product = data.product;
      $productForm.find('[name=product_id]').val(product.id);
      $root.find('#cartProductName').text(product.title);
      $root.find('#cartProductPrice').text(money(product.price));
      $root.find('#cartProductImage').prop('hidden', !product.preview);
      $root.find('#cartProductPlaceholder').prop('hidden', Boolean(product.preview));
      if (product.preview) $root.find('#cartProductImage').attr('src', product.preview);
      productAvailable = product.can_add_to_cart;
      $productSubmit.prop('disabled', !productAvailable);
      if (!productAvailable) $productError.text(labels.unavailableLabel);
    } catch (error) {
      if (sequence === productRequest) $productError.text(error.message || labels.errorLabel);
    }
  });
  $root.on('click', '[data-cart-quantity-step]', function () {
    if (Number($(this).attr('data-cart-quantity-step')) > 0) $quantityInput[0].stepUp();
    else $quantityInput[0].stepDown();
  });
  $productForm.on('submit', async function (event) {
    event.preventDefault();
    if ($productSubmit.prop('disabled') || !this.reportValidity()) return;
    $productSubmit.prop('disabled', true);
    $productError.text('');
    try {
      await initialized;
      renderCart(await request(labels.storeUrl, 'POST', {
        product_id: Number($productForm.find('[name=product_id]').val()), quantity: Number($quantityInput.val())
      }));
      $cartError.text('');
      $productDialog[0].close();
      showDialog($cartDialog);
    } catch (error) { $productError.text(error.message || labels.errorLabel); }
    finally { $productSubmit.prop('disabled', !productAvailable); }
  });
  $('#cartButton').on('click', async () => {
    $cartError.text('');
    $root.find('#cartItems').text(labels.loadingLabel);
    showDialog($cartDialog);
    try { await initialized; await loadCart(); }
    catch (error) {
      $cartError.text(error.message || labels.errorLabel);
      $root.find('#cartItems').text('');
    }
  });
});
