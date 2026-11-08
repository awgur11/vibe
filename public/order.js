jQuery(function ($) {
  'use strict';
  const $form = $('[data-checkout-form]');
  if (!$form.length) return;
  const $company = $form.find('[data-checkout-company]');
  const $nameLabel = $form.find('[data-checkout-name-label]');
  const $customerTypes = $form.find('input[name="customer_type"]');
  const $paymentsGroup = $form.find('[data-checkout-payments]');
  const $payments = $paymentsGroup.find('input[name="payment_method"]');
  function updateCustomerType() {
    const isCompany = $customerTypes.filter(':checked').val() === 'company';
    $company.prop('hidden', !isCompany).find('input, textarea').each(function () {
      $(this).prop({disabled: !isCompany, required: isCompany && $(this).is('[data-company-required]')});
    });
    $nameLabel.text($nameLabel.attr(isCompany ? 'data-company-label' : 'data-individual-label'));
  }
  const validateDelivery = setupDelivery();
  $customerTypes.on('change', updateCustomerType);
  $payments.on('change', function () {
    if (this.checked) $payments.not(this).prop('checked', false);
    $payments.each(function () { this.setCustomValidity(''); });
  });
  let submitting = false;
  $form.on('submit', async function (event) {
    event.preventDefault();
    if (submitting || !validateDelivery()) return;
    if (!$payments.is(':checked')) {
      $payments[0].setCustomValidity($paymentsGroup.attr('data-required-label'));
      $payments[0].reportValidity();
      return;
    }
    if (!this.reportValidity()) return;
    const $submitButton = $form.find('button[type="submit"]');
    const $status = $form.find('[data-checkout-status]');
    submitting = true;
    $submitButton.prop('disabled', true);
    $status.prop('hidden', true);
    try {
      const data = await $.ajax({url: $form.attr('data-csrf-url'), cache: false, dataType: 'json', timeout: 15000});
      if (typeof data.token !== 'string' || !data.token) throw new Error('Missing CSRF token');
      $form.find('input[name="_token"]').val(data.token);
      $('meta[name="csrf-token"]').attr('content', data.token);
      HTMLFormElement.prototype.submit.call(this);
    } catch (error) {
      $status.text($form.attr('data-csrf-error')).prop('hidden', false);
      submitting = false;
      $submitButton.prop('disabled', false);
    }
  });
  updateCustomerType();

  function setupDelivery() {
    const $delivery = $form.find('[data-checkout-delivery]');
    if (!$delivery.length) return () => true;
    const labels = $delivery.data();
    const $methods = $delivery.find('input[name="delivery_method"]');
    const $novaFields = $delivery.find('[data-nova-poshta-fields]');
    const $pickup = $delivery.find('[data-pickup-address]');
    const $cityField = $delivery.find('[data-city-field]');
    const $city = $delivery.find('#delivery-city-input');
    const $cityRef = $delivery.find('#delivery-city-ref');
    const $suggestions = $delivery.find('#delivery-city-options');
    const $warehouse = $delivery.find('#delivery-warehouse');
    const $status = $delivery.find('[data-delivery-status]');
    let cities = [];
    let activeCity = -1;
    let searchTimer;
    let cityRequest;
    let warehouseRequest;
    let cityVersion = 0;
    let warehouseVersion = 0;
    let warehouseReady = false;
    const isNovaPoshta = () => $methods.filter(':checked').val() === 'nova_poshta';
    function hideSuggestions() {
      $suggestions.prop('hidden', true);
      $city.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
      activeCity = -1;
    }
    function cancelSearch() {
      clearTimeout(searchTimer);
      cityVersion++;
      cityRequest?.abort();
    }
    function resetWarehouse() {
      warehouseVersion++;
      warehouseRequest?.abort();
      warehouseReady = false;
      $warehouse.prop('disabled', true).empty()
        .append($('<option>').val('').text(labels.warehouseRequiredLabel));
      $warehouse[0].setCustomValidity('');
    }
    function lookup(url, params) {
      return $.ajax({url, data: params, dataType: 'json'});
    }
    async function loadWarehouses(ref, selected = '') {
      resetWarehouse();
      const version = warehouseVersion;
      warehouseRequest = lookup(labels.warehousesUrl, {city_ref: ref});
      $status.text(labels.loadingLabel);
      try {
        const data = await warehouseRequest;
        if (version !== warehouseVersion || !isNovaPoshta() || $cityRef.val() !== ref) return;
        data.warehouses.forEach(item => $('<option>').val(item.ref).text(item.label).appendTo($warehouse));
        warehouseReady = data.warehouses.length > 0;
        $warehouse.prop('disabled', !warehouseReady);
        if (data.warehouses.some(item => item.ref === selected)) $warehouse.val(selected);
        $status.text(warehouseReady ? '' : labels.noWarehousesLabel);
      } catch (error) {
        if (version === warehouseVersion && error.statusText !== 'abort' && isNovaPoshta()) $status.text(labels.errorLabel);
      }
    }
    function selectCity(item) {
      cancelSearch();
      hideSuggestions();
      $city.val(item.label).trigger('focus');
      $cityRef.val(item.ref);
      $city[0].setCustomValidity('');
      loadWarehouses(item.ref);
    }
    async function searchCities(query, version) {
      cityRequest = lookup(labels.citiesUrl, {q: query.slice(0, 100)});
      $status.text(labels.loadingLabel);
      try {
        const data = await cityRequest;
        if (version !== cityVersion || !isNovaPoshta()) return;
        cities = data.cities.slice(0, 10);
        $suggestions.empty();
        cities.forEach((item, index) => {
          $('<button>').attr({type: 'button', id: `delivery-city-option-${index}`, tabindex: -1,
            role: 'option', 'aria-selected': 'false'}).text(item.label)
            .on('mousedown', event => event.preventDefault()).on('click', () => selectCity(item)).appendTo($suggestions);
        });
        $suggestions.prop('hidden', cities.length === 0);
        $city.attr('aria-expanded', String(cities.length > 0));
        $status.text(cities.length ? '' : labels.noCitiesLabel);
      } catch (error) {
        if (version === cityVersion && error.statusText !== 'abort' && isNovaPoshta()) $status.text(labels.errorLabel);
      }
    }
    $city.on('input', function () {
      cancelSearch();
      hideSuggestions();
      $cityRef.val('');
      this.setCustomValidity('');
      resetWarehouse();
      const query = $city.val().trim();
      $status.text(query.length < 2 ? labels.minimumLabel : '');
      if (query.length >= 2) {
        const version = cityVersion;
        searchTimer = setTimeout(() => searchCities(query, version), 300);
      }
    }).on('keydown', event => {
      if ($suggestions.prop('hidden') || !cities.length) return;
      if (event.key === 'Escape') { hideSuggestions(); return; }
      if (event.key === 'Enter') {
        event.preventDefault();
        selectCity(cities[Math.max(0, activeCity)]);
      } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        activeCity = activeCity < 0 ? (event.key === 'ArrowDown' ? 0 : cities.length - 1)
          : (activeCity + (event.key === 'ArrowDown' ? 1 : -1) + cities.length) % cities.length;
        const $options = $suggestions.children().attr('aria-selected', 'false');
        const $active = $options.eq(activeCity).attr('aria-selected', 'true');
        $city.attr('aria-activedescendant', $active.attr('id'));
        $active[0].scrollIntoView({block: 'nearest'});
      }
    });
    $cityField.on('focusout', event => { if (!$(event.relatedTarget).closest($cityField).length) hideSuggestions(); });
    $warehouse.on('change', function () { this.setCustomValidity(''); });
    function updateMethod() {
      const nova = isNovaPoshta();
      $novaFields.prop('hidden', !nova);
      $pickup.prop('hidden', nova);
      $city.prop({disabled: !nova, required: nova});
      $cityRef.prop('disabled', !nova);
      $warehouse.prop({required: nova, disabled: !nova || !warehouseReady});
      $city[0].setCustomValidity('');
      $warehouse[0].setCustomValidity('');
      if (!nova) {
        cancelSearch();
        warehouseVersion++;
        warehouseRequest?.abort();
        hideSuggestions();
        $status.text('');
      } else if ($cityRef.val() && !warehouseReady) {
        loadWarehouses($cityRef.val(), $warehouse.attr('data-selected-warehouse') || '');
      }
    }
    $methods.on('change', updateMethod);
    updateMethod();
    return () => {
      if (!isNovaPoshta()) return true;
      if (!$cityRef.val()) {
        $city[0].setCustomValidity(labels.cityRequiredLabel);
        $city[0].reportValidity();
        return false;
      }
      if (!warehouseReady || !$warehouse.val()) {
        $status.text(labels.warehouseRequiredLabel);
        if (!$warehouse.prop('disabled')) {
          $warehouse[0].setCustomValidity(labels.warehouseRequiredLabel);
          $warehouse[0].reportValidity();
        }
        return false;
      }
      return true;
    };
  }
});
