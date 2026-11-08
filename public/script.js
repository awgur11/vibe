jQuery(function ($) {
  'use strict';
  const categoryNames = {
    parts: 'Запчасти', tires: 'Шины', wheels: 'Диски', tools: 'Инструменты',
    accessories: 'Автоаксессуары', oils: 'Масла', batteries: 'Аккумуляторы',
    brakes: 'Тормозная система', suspension: 'Подвеска', filters: 'Фильтры',
    lighting: 'Освещение', electrics: 'Электрика'
  };
  const closeCatalog = () => {
    $('#catalogDropdown').prop('hidden', true);
    $('#catalogToggle').attr('aria-expanded', 'false');
  };
  const $mobileNav = $('#mainNav');
  const $mobileToggle = $('#mobileNavToggle');
  const closeMobile = () => {
    $mobileNav.removeClass('is-open');
    $mobileToggle.attr('aria-expanded', 'false');
  };
  $mobileToggle.on('click', () => {
    $mobileNav.toggleClass('is-open');
    $mobileToggle.attr('aria-expanded', String($mobileNav.hasClass('is-open')));
  });
  $mobileNav.on('click', event => {
    if (!event.isDefaultPrevented() && $(event.target).closest('a').length) { closeMobile(); closeCatalog(); }
  });

  const $productsGrid = $('#productsGrid');
  const $cards = $productsGrid.find('.product-card');
  const $filterButtons = $('.filter-btn');
  let activeFilter = 'all';
  let searchTerm = '';
  let expanded = true;
  function renderProducts() {
    if (!$productsGrid.length) return;
    const $matches = $cards.filter(function () {
      const categories = ($(this).attr('data-category') || '').split(' ');
      const name = ($(this).attr('data-name') || '').toLocaleLowerCase('ru');
      const categoryMatch = activeFilter === 'all' || categories.includes(activeFilter);
      const queryMatch = !searchTerm || name.includes(searchTerm)
        || categories.some(category => (categoryNames[category] || '').toLocaleLowerCase('ru').includes(searchTerm));
      return categoryMatch && queryMatch;
    });
    $cards.prop('hidden', true);
    const $visible = activeFilter === 'all' && !searchTerm && !expanded ? $matches.slice(0, 5) : $matches;
    $visible.prop('hidden', false);
    $('#emptyProducts').prop('hidden', $visible.length > 0);
    const $showAll = $('#showAllProducts');
    if ($showAll.length) {
      $showAll.text($showAll.attr(expanded ? 'data-collapse-label' : 'data-expand-label'))
        .append(' <svg class="icon"><use href="#i-arrow"/></svg>');
    }
    $filterButtons.each(function () {
      const $button = $(this);
      const pressed = $button.attr('data-filter') === activeFilter;
      $button.toggleClass('is-active', pressed).attr('aria-pressed', String(pressed));
    });
  }
  function selectCategory(category) {
    activeFilter = category;
    expanded = true;
    searchTerm = '';
    $('#siteSearch').val('');
    renderProducts();
  }
  $filterButtons.on('click', function () { selectCategory($(this).attr('data-filter')); });
  $('#showAllProducts').on('click', event => {
    event.preventDefault();
    if (activeFilter !== 'all' || searchTerm) { selectCategory('all'); }
    else { expanded = !expanded; renderProducts(); }
  });
  $(document).on('click', '[data-select-category], .category-card[data-category]', function (event) {
    const category = $(this).attr('data-select-category') || $(this).attr('data-category');
    if (!category) return;
    selectCategory(category);
    closeCatalog();
    event.preventDefault();
    $('#products')[0]?.scrollIntoView({behavior: 'smooth'});
  }).on('keydown', event => {
    if (event.key === 'Escape') { closeCatalog(); closeMobile(); }
  });

  const $track = $('#newsTrack');
  const $news = $track.find('.news-card');
  const $dots = $('#newsDots');
  let newsIndex = 0;
  const visibleNews = () => window.innerWidth <= 680 ? 1 : window.innerWidth <= 900 ? 2 : 3;
  const maxNewsIndex = () => Math.max(0, $news.length - visibleNews());
  const newsStep = () => $news.length > 1 ? $news[1].offsetLeft - $news[0].offsetLeft : 1;
  function updateNewsDots() {
    if (!$dots.length || !$track.length) return;
    $dots.empty();
    for (let index = 0; index <= maxNewsIndex(); index++) {
      $('<button>').attr({type: 'button',
        'aria-label': ($dots.attr('data-group-label') || '').replace(':number', String(index + 1)),
        'aria-current': String(index === newsIndex)})
        .toggleClass('active', index === newsIndex).on('click', () => scrollNews(index)).appendTo($dots);
    }
    $('#newsPrev').prop('disabled', newsIndex === 0);
    $('#newsNext').prop('disabled', newsIndex >= maxNewsIndex());
  }
  function scrollNews(index) {
    if (!$track.length) return;
    newsIndex = Math.max(0, Math.min(maxNewsIndex(), index));
    $track[0].scrollTo({left: newsIndex * newsStep(), behavior: 'smooth'});
    updateNewsDots();
  }
  $('#newsPrev').on('click', () => scrollNews(newsIndex - 1));
  $('#newsNext').on('click', () => scrollNews(newsIndex + 1));
  let resizeTimer;
  $(window).on('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => scrollNews(Math.min(newsIndex, maxNewsIndex())), 120);
  });
  $track.on('scroll', () => {
    const estimated = Math.max(0, Math.min(maxNewsIndex(), Math.round($track.scrollLeft() / newsStep())));
    if (estimated !== newsIndex) { newsIndex = estimated; updateNewsDots(); }
  });

  let formMode = 'vin';
  $('.vin-tab').on('click', function () {
    const $button = $(this);
    formMode = $button.attr('data-mode');
    $('.vin-tab').removeClass('active').attr('aria-pressed', 'false');
    $button.addClass('active').attr('aria-pressed', 'true');
    $('#vinInput').val('').prop('maxLength', formMode === 'vin' ? 17 : 100)
      .attr('placeholder', formMode === 'vin' ? 'Введите VIN-код вашего автомобиля' : 'Марка, модель и год выпуска');
    $('#vinInputLabel').text(formMode === 'vin' ? 'VIN-код автомобиля' : 'Параметры автомобиля');
    $('#vinMessage').text('');
  });
  $('#vinForm').on('submit', event => {
    event.preventDefault();
    const $input = $('#vinInput');
    const value = $input.val().trim().toUpperCase();
    const valid = formMode === 'vin' ? /^[A-HJ-NPR-Z0-9]{17}$/.test(value) : value.length >= 4;
    const $message = $('#vinMessage').attr('class', 'form-message ' + (valid ? 'is-ok' : 'is-error'));
    if (!valid) {
      $message.text(formMode === 'vin' ? 'Укажите VIN из 17 символов (без I, O и Q).' : 'Укажите марку, модель и год автомобиля.');
      $input.trigger('focus');
    } else {
      $message.text('Данные введены корректно. Для получения заявок подключите форму к обработчику Laravel.');
    }
  });
  $('#year').text(new Date().getFullYear());
  renderProducts();
  updateNewsDots();
});

jQuery(function ($) {
  'use strict';
  const $button = $('#copyAddress');
  if (!$button.length) return;
  const $status = $('#copyAddressStatus');
  let restoreLabel;
  $button.on('click', async () => {
    try {
      await navigator.clipboard.writeText($button.attr('data-address'));
      clearTimeout(restoreLabel);
      $button.add($status).text($button.attr('data-success-label'));
      restoreLabel = setTimeout(() => {
        $button.text($button.attr('data-default-label'));
        $status.text('');
      }, 2600);
    } catch (error) { $status.text($button.attr('data-error-label')); }
  });
});

jQuery(function ($) {
  'use strict';
  const $form = $('[data-catalog-filters]');
  if (!$form.length) return;
  $form.on('change', 'input[type="checkbox"]', () => $form[0].requestSubmit());
  $('[data-catalog-sort]').on('change', () => $form[0].requestSubmit());
});

jQuery(function ($) {
  'use strict';
  const $panel = $('#catalog-filter-panel');
  const $toggle = $('[data-catalog-filter-toggle]');
  const $closeButton = $('[data-catalog-filter-close]');
  const $backdrop = $('[data-catalog-filter-backdrop]');
  if (!$panel.length || !$toggle.length || !$closeButton.length || !$backdrop.length) return;
  const mobile = window.matchMedia('(max-width:680px)');
  let isOpen = false;
  let previousOverflow = '';
  function closeFilters(restoreFocus = true) {
    if (isOpen) $('body').css('overflow', previousOverflow);
    isOpen = false;
    $panel.removeClass('is-open').removeAttr('role aria-modal').prop('inert', mobile.matches);
    $backdrop.prop('hidden', true);
    $toggle.attr('aria-expanded', 'false');
    if (restoreFocus && mobile.matches) $toggle.trigger('focus');
  }
  $toggle.on('click', () => {
    if (!mobile.matches) return;
    if (isOpen) { closeFilters(); return; }
    previousOverflow = document.body.style.overflow;
    $('body').css('overflow', 'hidden');
    isOpen = true;
    $panel.prop('inert', false).addClass('is-open').attr({role: 'dialog', 'aria-modal': 'true'});
    $backdrop.prop('hidden', false);
    $toggle.attr('aria-expanded', 'true');
    $closeButton.trigger('focus');
  });
  $closeButton.add($backdrop).on('click', () => closeFilters());
  $(document).on('keydown', event => {
    if (!isOpen) return;
    if (event.key === 'Escape') { event.preventDefault(); closeFilters(); }
    if (event.key === 'Tab') {
      const $controls = $panel.find('button, summary, input:not([disabled]), a[href], select').filter(':visible');
      const $first = $controls.first();
      const $last = $controls.last();
      if (event.shiftKey && document.activeElement === $first[0]) { event.preventDefault(); $last.trigger('focus'); }
      else if (!event.shiftKey && document.activeElement === $last[0]) { event.preventDefault(); $first.trigger('focus'); }
    }
  });
  $(mobile).on('change', () => closeFilters(false));
  closeFilters(false);
});
