(() => {
  'use strict';
  const form = document.querySelector('[data-checkout-form]');
  if (!form) return;
  const company = form.querySelector('[data-checkout-company]');
  const nameLabel = form.querySelector('[data-checkout-name-label]');
  const customerTypes = [...form.querySelectorAll('input[name="customer_type"]')];
  const paymentsGroup = form.querySelector('[data-checkout-payments]');
  const payments = [...paymentsGroup.querySelectorAll('input[name="payment_method"]')];
  function updateCustomerType() {
    const isCompany = customerTypes.some(input => input.checked && input.value === 'company');
    company.hidden = !isCompany;
    company.querySelectorAll('input, textarea').forEach(input => {
      input.disabled = !isCompany;
      input.required = isCompany && input.hasAttribute('data-company-required');
    });
    nameLabel.textContent = isCompany ? nameLabel.dataset.companyLabel : nameLabel.dataset.individualLabel;
  }
  const validateDelivery = setupDelivery(form);
  customerTypes.forEach(input => input.addEventListener('change', updateCustomerType));
  payments.forEach(input => input.addEventListener('change', () => {
    if (input.checked) payments.forEach(other => { if (other !== input) other.checked = false; });
    payments.forEach(other => other.setCustomValidity(''));
  }));
  let submitting = false;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (submitting) return;
    if (!validateDelivery()) { event.preventDefault(); return; }
    if (!payments.some(input => input.checked)) {
      event.preventDefault();
      payments[0].setCustomValidity(paymentsGroup.dataset.requiredLabel);
      payments[0].reportValidity();
      return;
    }
    if (!form.reportValidity()) { event.preventDefault(); return; }
    const submitButton = form.querySelector('button[type="submit"]');
    const status = form.querySelector('[data-checkout-status]');
    submitting = true;
    submitButton.disabled = true;
    status.hidden = true;
    try {
      const response = await fetch(form.dataset.csrfUrl, {
        headers: {'Accept': 'application/json'},
        credentials: 'same-origin',
        cache: 'no-store',
        signal: AbortSignal.timeout(15000),
      });
      if (!response.ok) throw new Error('Session refresh failed');
      const data = await response.json();
      if (typeof data.token !== 'string' || !data.token) throw new Error('Missing CSRF token');
      form.querySelector('input[name="_token"]').value = data.token;
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (meta) meta.content = data.token;
      HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
      status.textContent = form.dataset.csrfError;
      status.hidden = false;
      submitting = false;
      submitButton.disabled = false;
    }
  });
  updateCustomerType();

  function setupDelivery(checkoutForm) {
    const delivery = checkoutForm.querySelector('[data-checkout-delivery]');
    if (!delivery) return () => true;
    const labels = delivery.dataset;
    const methods = [...delivery.querySelectorAll('input[name="delivery_method"]')];
    const novaFields = delivery.querySelector('[data-nova-poshta-fields]');
    const pickup = delivery.querySelector('[data-pickup-address]');
    const cityField = delivery.querySelector('[data-city-field]');
    const city = delivery.querySelector('#delivery-city-input');
    const cityRef = delivery.querySelector('#delivery-city-ref');
    const suggestions = delivery.querySelector('#delivery-city-options');
    const warehouse = delivery.querySelector('#delivery-warehouse');
    const status = delivery.querySelector('[data-delivery-status]');
    let cities = [];
    let activeCity = -1;
    let searchTimer;
    let cityController;
    let warehouseController;
    let cityVersion = 0;
    let warehouseVersion = 0;
    let warehouseReady = false;
    const isNovaPoshta = () => methods.some(method => method.checked && method.value === 'nova_poshta');
    function hideSuggestions() {
      suggestions.hidden = true;
      city.setAttribute('aria-expanded', 'false');
      city.removeAttribute('aria-activedescendant');
      activeCity = -1;
    }
    function cancelSearch() {
      clearTimeout(searchTimer);
      cityController?.abort();
      cityVersion++;
    }
    function warehousePlaceholder() {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = labels.warehouseRequiredLabel;
      warehouse.replaceChildren(option);
    }
    function resetWarehouse() {
      warehouseController?.abort();
      warehouseVersion++;
      warehouseReady = false;
      warehouse.disabled = true;
      warehouse.setCustomValidity('');
      warehousePlaceholder();
    }
    async function lookup(url, params, signal) {
      const endpoint = new URL(url, window.location.href);
      Object.entries(params).forEach(([name, value]) => endpoint.searchParams.set(name, value));
      const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}, credentials: 'same-origin', signal});
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || labels.errorLabel);
      return data;
    }
    async function loadWarehouses(ref, selected = '') {
      resetWarehouse();
      const version = warehouseVersion;
      warehouseController = new AbortController();
      status.textContent = labels.loadingLabel;
      try {
        const data = await lookup(labels.warehousesUrl, {city_ref: ref}, warehouseController.signal);
        if (version !== warehouseVersion || !isNovaPoshta() || cityRef.value !== ref) return;
        data.warehouses.forEach(item => {
          const option = document.createElement('option');
          option.value = item.ref;
          option.textContent = item.label;
          warehouse.append(option);
        });
        warehouseReady = data.warehouses.length > 0;
        warehouse.disabled = !warehouseReady;
        if (data.warehouses.some(item => item.ref === selected)) warehouse.value = selected;
        status.textContent = warehouseReady ? '' : labels.noWarehousesLabel;
      } catch (error) {
        if (version === warehouseVersion && error.name !== 'AbortError' && isNovaPoshta()) status.textContent = labels.errorLabel;
      }
    }
    function selectCity(item) {
      cancelSearch();
      hideSuggestions();
      city.value = item.label;
      cityRef.value = item.ref;
      city.setCustomValidity('');
      city.focus();
      loadWarehouses(item.ref);
    }
    async function searchCities(query, version) {
      cityController = new AbortController();
      status.textContent = labels.loadingLabel;
      try {
        const data = await lookup(labels.citiesUrl, {q: query.slice(0, 100)}, cityController.signal);
        if (version !== cityVersion || !isNovaPoshta()) return;
        cities = data.cities.slice(0, 10);
        suggestions.replaceChildren();
        cities.forEach((item, index) => {
          const option = document.createElement('button');
          option.type = 'button';
          option.id = `delivery-city-option-${index}`;
          option.tabIndex = -1;
          option.setAttribute('role', 'option');
          option.setAttribute('aria-selected', 'false');
          option.textContent = item.label;
          option.addEventListener('mousedown', event => event.preventDefault());
          option.addEventListener('click', () => selectCity(item));
          suggestions.append(option);
        });
        suggestions.hidden = cities.length === 0;
        city.setAttribute('aria-expanded', String(cities.length > 0));
        status.textContent = cities.length ? '' : labels.noCitiesLabel;
      } catch (error) {
        if (version === cityVersion && error.name !== 'AbortError' && isNovaPoshta()) status.textContent = labels.errorLabel;
      }
    }
    city.addEventListener('input', () => {
      cancelSearch();
      hideSuggestions();
      cityRef.value = '';
      city.setCustomValidity('');
      resetWarehouse();
      const query = city.value.trim();
      status.textContent = query.length < 2 ? labels.minimumLabel : '';
      if (query.length >= 2) {
        const version = cityVersion;
        searchTimer = setTimeout(() => searchCities(query, version), 300);
      }
    });
    city.addEventListener('keydown', event => {
      if (suggestions.hidden || !cities.length) return;
      if (event.key === 'Escape') { hideSuggestions(); return; }
      if (event.key === 'Enter') {
        event.preventDefault();
        selectCity(cities[Math.max(0, activeCity)]);
      } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        activeCity = activeCity < 0 ? (event.key === 'ArrowDown' ? 0 : cities.length - 1)
          : (activeCity + (event.key === 'ArrowDown' ? 1 : -1) + cities.length) % cities.length;
        [...suggestions.children].forEach((option, index) => option.setAttribute('aria-selected', String(index === activeCity)));
        city.setAttribute('aria-activedescendant', suggestions.children[activeCity].id);
        suggestions.children[activeCity].scrollIntoView({block: 'nearest'});
      }
    });
    cityField.addEventListener('focusout', event => { if (!cityField.contains(event.relatedTarget)) hideSuggestions(); });
    warehouse.addEventListener('change', () => warehouse.setCustomValidity(''));
    function updateMethod() {
      const nova = isNovaPoshta();
      novaFields.hidden = !nova;
      pickup.hidden = nova;
      city.disabled = !nova;
      city.required = nova;
      cityRef.disabled = !nova;
      warehouse.required = nova;
      warehouse.disabled = !nova || !warehouseReady;
      city.setCustomValidity('');
      warehouse.setCustomValidity('');
      if (!nova) {
        cancelSearch();
        warehouseController?.abort();
        warehouseVersion++;
        hideSuggestions();
        status.textContent = '';
      } else if (cityRef.value && !warehouseReady) {
        loadWarehouses(cityRef.value, warehouse.dataset.selectedWarehouse || '');
      }
    }
    methods.forEach(method => method.addEventListener('change', updateMethod));
    updateMethod();
    return () => {
      if (!isNovaPoshta()) return true;
      if (!cityRef.value) {
        city.setCustomValidity(labels.cityRequiredLabel);
        city.reportValidity();
        return false;
      }
      if (!warehouseReady || !warehouse.value) {
        status.textContent = labels.warehouseRequiredLabel;
        if (!warehouse.disabled) {
          warehouse.setCustomValidity(labels.warehouseRequiredLabel);
          warehouse.reportValidity();
        }
        return false;
      }
      return true;
    };
  }
})();
