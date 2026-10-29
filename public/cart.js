(() => {
  'use strict';
  const root = document.querySelector('[data-cart-ui]');
  if (!root) return;
  const $ = selector => root.querySelector(selector);
  const labels = root.dataset;
  const productDialog = $('#cartProductDialog');
  const cartDialog = $('#cartDialog');
  const productForm = $('#cartProductForm');
  const productSubmit = $('#cartProductSubmit');
  const productError = $('#cartProductError');
  const cartError = $('#cartError');
  const quantityInput = $('#cartProductQuantity');
  const money = value => new Intl.NumberFormat(labels.locale.replace('_', '-'), {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(value)) + ' грн';
  let scrollLocked = false;
  let previousOverflow = '';
  let returnFocus = null;
  let productRequest = 0;
  let productAvailable = false;

  async function request(url, method = 'GET', body = null) {
    const response = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': labels.csrf, 'X-Requested-With': 'XMLHttpRequest'},
      ...(body === null ? {} : {body: JSON.stringify(body)})
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat()[0] || data.message || labels.errorLabel);
    return data;
  }

  function showDialog(dialog) {
    if (!scrollLocked) {
      previousOverflow = document.body.style.overflow;
      returnFocus = document.activeElement;
      scrollLocked = true;
    }
    document.body.style.overflow = 'hidden';
    if (!dialog.open) dialog.showModal();
  }

  [productDialog, cartDialog].forEach(dialog => {
    dialog.addEventListener('close', () => {
      if (productDialog.open || cartDialog.open) return;
      document.body.style.overflow = previousOverflow;
      scrollLocked = false;
      returnFocus?.focus?.();
    });
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const rect = dialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    });
  });
  root.querySelectorAll('[data-cart-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));

  function productImage(url, title = '') {
    if (url) {
      const image = document.createElement('img');
      image.src = url;
      image.alt = title;
      image.loading = 'lazy';
      return image;
    }
    const placeholder = document.createElement('span');
    placeholder.className = 'shop-cart-image-placeholder';
    placeholder.setAttribute('aria-hidden', 'true');
    placeholder.innerHTML = '<svg class="icon"><use href="#i-no-image"/></svg>';
    return placeholder;
  }

  function renderCart(data) {
    const counter = document.getElementById('cartCount');
    if (counter) counter.textContent = data.count;
    $('#cartCheckoutLink').hidden = data.count === 0;
    $('#cartTotal').textContent = money(data.total);
    const items = $('#cartItems');
    items.replaceChildren();
    if (!data.items.length) {
      const empty = document.createElement('p');
      empty.className = 'shop-cart-empty';
      empty.textContent = labels.emptyLabel;
      items.append(empty);
    }
    data.items.forEach(item => {
      const row = document.createElement('article');
      row.className = 'shop-cart-line';
      const details = document.createElement('div');
      details.className = 'shop-cart-line-info';
      const title = document.createElement('strong');
      title.textContent = item.title;
      const price = document.createElement('small');
      price.textContent = money(item.price);
      const quantity = document.createElement('input');
      quantity.type = 'number';
      quantity.min = '1';
      quantity.max = quantityInput.max;
      quantity.step = '1';
      quantity.value = item.quantity;
      quantity.inputMode = 'numeric';
      quantity.disabled = item.can_update === false;
      quantity.setAttribute('aria-label', `${labels.quantityLabel}: ${item.title}`);
      const total = document.createElement('b');
      total.className = 'shop-cart-line-total';
      total.textContent = money(item.total);
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'shop-cart-remove';
      remove.setAttribute('aria-label', `${labels.removeLabel}: ${item.title}`);
      remove.title = labels.removeLabel;
      remove.innerHTML = '<svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg>';
      details.append(title, price, quantity);
      row.append(productImage(item.preview), details, total, remove);
      items.append(row);
      async function changeItem(method, body = null) {
        cartError.textContent = '';
        quantity.disabled = true;
        remove.disabled = true;
        try {
          renderCart(await request(method === 'DELETE' ? item.remove_url : item.update_url, method, body));
        } catch (error) {
          cartError.textContent = error.message || labels.errorLabel;
          quantity.value = item.quantity;
        } finally {
          quantity.disabled = item.can_update === false;
          remove.disabled = false;
        }
      }
      quantity.addEventListener('change', () => {
        if (!quantity.reportValidity()) return;
        changeItem('PATCH', {quantity: Number(quantity.value)});
      });
      remove.addEventListener('click', () => changeItem('DELETE'));
    });
  }

  async function loadCart() {
    const data = await request(labels.indexUrl);
    renderCart(data);
    return data;
  }
  const initialized = loadCart().catch(() => null);

  document.addEventListener('click', async event => {
    const button = event.target.closest('.add-cart[data-cart-product-url]');
    if (!button || button.disabled) return;
    event.preventDefault();
    const sequence = ++productRequest;
    productAvailable = false;
    productSubmit.disabled = true;
    productForm.elements.product_id.value = '';
    quantityInput.value = '1';
    productError.textContent = '';
    $('#cartProductName').textContent = labels.loadingLabel;
    $('#cartProductPrice').textContent = '';
    $('#cartProductImage').hidden = true;
    $('#cartProductPlaceholder').toggleAttribute('hidden', true);
    showDialog(productDialog);
    try {
      await initialized;
      const data = await request(button.dataset.cartProductUrl);
      if (sequence !== productRequest || !productDialog.open) return;
      const product = data.product;
      productForm.elements.product_id.value = product.id;
      $('#cartProductName').textContent = product.title;
      $('#cartProductPrice').textContent = money(product.price);
      $('#cartProductImage').hidden = !product.preview;
      $('#cartProductPlaceholder').toggleAttribute('hidden', Boolean(product.preview));
      if (product.preview) $('#cartProductImage').src = product.preview;
      productAvailable = product.can_add_to_cart;
      productSubmit.disabled = !productAvailable;
      if (!productAvailable) productError.textContent = labels.unavailableLabel;
    } catch (error) {
      if (sequence === productRequest) productError.textContent = error.message || labels.errorLabel;
    }
  });

  root.querySelectorAll('[data-cart-quantity-step]').forEach(button => button.addEventListener('click', () => {
    if (Number(button.dataset.cartQuantityStep) > 0) quantityInput.stepUp();
    else quantityInput.stepDown();
  }));
  productForm.addEventListener('submit', async event => {
    event.preventDefault();
    if (productSubmit.disabled || !productForm.reportValidity()) return;
    productSubmit.disabled = true;
    productError.textContent = '';
    try {
      await initialized;
      const data = await request(labels.storeUrl, 'POST', {product_id: Number(productForm.elements.product_id.value), quantity: Number(quantityInput.value)});
      renderCart(data);
      cartError.textContent = '';
      productDialog.close();
      showDialog(cartDialog);
    } catch (error) {
      productError.textContent = error.message || labels.errorLabel;
    } finally {
      productSubmit.disabled = !productAvailable;
    }
  });

  document.getElementById('cartButton')?.addEventListener('click', async () => {
    cartError.textContent = '';
    $('#cartItems').textContent = labels.loadingLabel;
    showDialog(cartDialog);
    try {
      await initialized;
      await loadCart();
    } catch (error) {
      cartError.textContent = error.message || labels.errorLabel;
      $('#cartItems').textContent = '';
    }
  });
})();
