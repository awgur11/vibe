/* KINGAVTO — интерактивный фронтенд-макет без сервера и фреймворков.
   Реальные товары, цены, поиск, оформление заказов и VIN-заявки подключаются к вашему Laravel API. */
(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...(root ?? document).querySelectorAll(selector)];

  const getJSON = (key, fallback) => {
    try { return JSON.parse(localStorage.getItem(key)) ?? fallback; }
    catch { return fallback; }
  };
  const setJSON = (key, val) => {
    try { localStorage.setItem(key, JSON.stringify(val)); } catch { /* private mode */ }
  };

  // Catalog menu
  const categoryNames = {
    parts:'Запчасти', tires:'Шины', wheels:'Диски', tools:'Инструменты',
    accessories:'Автоаксессуары', oils:'Масла', batteries:'Аккумуляторы',
    brakes:'Тормозная система', suspension:'Подвеска', filters:'Фильтры',
    lighting:'Освещение', electrics:'Электрика'
  };
  const closeCatalog = () => {
    const dropdown = $('#catalogDropdown');
    const toggle = $('#catalogToggle');
    if (dropdown) dropdown.hidden = true;
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  };

  const mobileNav = $('#mainNav');
  const mobileToggle = $('#mobileNavToggle');
  const closeMobile = () => {
    mobileNav.classList.remove('is-open');
    mobileToggle.setAttribute('aria-expanded','false');
  };
  mobileToggle.addEventListener('click', () => {
    const isOpen = mobileNav.classList.toggle('is-open');
    mobileToggle.setAttribute('aria-expanded',String(isOpen));
  });
  mobileNav.addEventListener('click', e => {
    if (e.defaultPrevented) return;
    if (e.target.closest('a')) { closeMobile(); closeCatalog(); }
  });

  // Product listing, search, and filters
  const productsGrid = $('#productsGrid');
  const cards = productsGrid ? $$('.product-card', productsGrid) : [];
  const filterBtns = $$('.filter-btn');
  const empty = $('#emptyProducts');
  let activeFilter = 'all';
  let searchTerm = '';
  let expanded = true;

  function renderProducts() {
    if (!productsGrid) return;
    let count = 0;
    const matches = cards.filter(card => {
      const categories = (card.dataset.category || '').split(' ');
      const name = (card.dataset.name || '').toLocaleLowerCase('ru');
      const categoryMatch = activeFilter === 'all' || categories.includes(activeFilter);
      const queryMatch = !searchTerm || name.includes(searchTerm) || categories.some(c => (categoryNames[c] || '').toLocaleLowerCase('ru').includes(searchTerm));
      return categoryMatch && queryMatch;
    });
    cards.forEach(card => { card.hidden = true; });
    const visible = (activeFilter === 'all' && !searchTerm && !expanded) ? matches.slice(0,5) : matches;
    visible.forEach(card => { card.hidden = false; count++; });
    if (empty) empty.hidden = count > 0;
    const showAll = $('#showAllProducts');
    if (showAll) {
      showAll.textContent = expanded ? showAll.dataset.collapseLabel : showAll.dataset.expandLabel;
      showAll.insertAdjacentHTML('beforeend', ' <svg class="icon"><use href="#i-arrow"/></svg>');
    }
    filterBtns.forEach(button => {
      const pressed = button.dataset.filter === activeFilter;
      button.classList.toggle('is-active',pressed);
      button.setAttribute('aria-pressed',String(pressed));
    });
  }

  function selectCategory(category) {
    activeFilter = category;
    expanded = true;
    searchTerm = '';
    $('#siteSearch').value = '';
    renderProducts();
  }
  filterBtns.forEach(btn => btn.addEventListener('click', () => selectCategory(btn.dataset.filter)));
  $('#showAllProducts')?.addEventListener('click', e => {
    e.preventDefault();
    if (activeFilter !== 'all' || searchTerm) { activeFilter='all';searchTerm='';$('#siteSearch').value='';expanded=true; }
    else { expanded = !expanded; }
    renderProducts();
  });
  document.addEventListener('click', e => {
    const cat = e.target.closest('[data-select-category], .category-card[data-category]');
    if (cat) {
      const category = cat.dataset.selectCategory || cat.dataset.category;
      if (category) {
        selectCategory(category);
        closeCatalog();
        // Other links can target #categories; navigate to results if selected.
        e.preventDefault();
        $('#products').scrollIntoView({behavior:'smooth'});
      }
    }
  });
  // Favorites and comparisons
  const toastElement = $('#toast');
  let toastTimeout;
  function toast(message) {
    if (!toastElement) return;
    toastElement.textContent = message;
    toastElement.classList.add('show');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => toastElement.classList.remove('show'), 2600);
  }
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeCatalog();closeMobile(); }
  });

  // News carousel: native horizontal scroll and accessible buttons
  const track = $('#newsTrack');
  const news = $$('.news-card',track);
  const dots = $('#newsDots');
  let newsIndex = 0;
  function visibleNews() { return window.innerWidth <= 680 ? 1 : window.innerWidth <= 900 ? 2 : 3; }
  function maxNewsIndex() {return Math.max(0,news.length - visibleNews());}
  function newsStep() { return news.length > 1 ? news[1].offsetLeft-news[0].offsetLeft : 1; }
  function updateNewsDots() {
    if (!dots || !track) return;
    const count=maxNewsIndex()+1;
    dots.innerHTML='';
    for(let index=0;index<count;index++) {
      const dot=document.createElement('button');
      dot.type='button';
      dot.classList.toggle('active',index === newsIndex);
      dot.setAttribute('aria-label', dots.dataset.groupLabel.replace(':number', String(index + 1)));
      dot.setAttribute('aria-current',String(index === newsIndex));
      dot.addEventListener('click',()=>scrollNews(index));
      dots.append(dot);
    }
    $('#newsPrev').disabled=newsIndex===0;
    $('#newsNext').disabled=newsIndex>=maxNewsIndex();
  }
  function scrollNews(index) {
    if (!track) return;
    newsIndex=Math.max(0,Math.min(maxNewsIndex(),index));
    track.scrollTo({left:newsIndex*newsStep(),behavior:'smooth'});
    updateNewsDots();
  }
  $('#newsPrev')?.addEventListener('click',()=>scrollNews(newsIndex-1));
  $('#newsNext')?.addEventListener('click',()=>scrollNews(newsIndex+1));
  let resizeTimer;
  window.addEventListener('resize',()=> {
    clearTimeout(resizeTimer);
    resizeTimer=setTimeout(()=>scrollNews(Math.min(newsIndex,maxNewsIndex())),120);
  });
  track?.addEventListener('scroll', () => {
    const estimated=Math.max(0,Math.min(maxNewsIndex(), Math.round(track.scrollLeft/newsStep())));
    if (estimated!==newsIndex) {newsIndex=estimated;updateNewsDots();}
  },{passive:true});

  // VIN form: client-side validation only. No submission to a server.
  let formMode='vin';
  $$('.vin-tab').forEach(button => button.addEventListener('click',()=> {
    formMode=button.dataset.mode;
    $$('.vin-tab').forEach(btn => {
      const selected=btn === button;
      btn.classList.toggle('active',selected);
      btn.setAttribute('aria-pressed',String(selected));
    });
    const input=$('#vinInput');
    input.value='';
    input.maxLength=formMode==='vin'?17:100;
    input.placeholder=formMode==='vin'?'Введите VIN-код вашего автомобиля':'Марка, модель и год выпуска';
    $('#vinInputLabel').textContent=formMode==='vin'?'VIN-код автомобиля':'Параметры автомобиля';
    $('#vinMessage').textContent='';
  }));
  $('#vinForm')?.addEventListener('submit',event => {
    event.preventDefault();
    const input=$('#vinInput');
    const value=input.value.trim().toUpperCase();
    const message=$('#vinMessage');
    const valid=formMode==='vin'?/^[A-HJ-NPR-Z0-9]{17}$/.test(value):value.length>=4;
    message.className='form-message '+(valid?'is-ok':'is-error');
    if (!valid) {
      message.textContent=formMode==='vin'?'Укажите VIN из 17 символов (без I, O и Q).':'Укажите марку, модель и год автомобиля.';
      input.focus();
    } else message.textContent='Данные введены корректно. Для получения заявок подключите форму к обработчику Laravel.';
  });

  $('#year').textContent = new Date().getFullYear();
  renderProducts();
  updateCounters();
  updateNewsDots();
})();


(() => {
  'use strict';
  const button = document.getElementById('copyAddress');
  if (!button) return;
  const status = document.getElementById('copyAddressStatus');
  let restoreLabel;
  button.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(button.dataset.address);
      clearTimeout(restoreLabel);
      button.textContent = button.dataset.successLabel;
      if (status) status.textContent = button.dataset.successLabel;
      restoreLabel = setTimeout(() => {
        button.textContent = button.dataset.defaultLabel;
        if (status) status.textContent = '';
      }, 2600);
    } catch {
      if (status) status.textContent = button.dataset.errorLabel;
    }
  });
})();


(() => {
  'use strict';
  const form = document.querySelector('[data-catalog-filters]');
  if (!form) return;
  form.addEventListener('change', event => {
    if (event.target.matches('input[type="checkbox"]')) form.requestSubmit();
  });
  document.querySelector('[data-catalog-sort]')?.addEventListener('change', () => form.requestSubmit());
})();


(() => {
  'use strict';
  const panel = document.getElementById('catalog-filter-panel');
  const toggle = document.querySelector('[data-catalog-filter-toggle]');
  const closeButton = document.querySelector('[data-catalog-filter-close]');
  const backdrop = document.querySelector('[data-catalog-filter-backdrop]');
  if (!panel || !toggle || !closeButton || !backdrop) return;
  const mobile = window.matchMedia('(max-width:680px)');
  let isOpen = false;
  let previousOverflow = '';
  function closeFilters(restoreFocus = true) {
    if (isOpen) document.body.style.overflow = previousOverflow;
    isOpen = false;
    panel.classList.remove('is-open');
    panel.removeAttribute('role');
    panel.removeAttribute('aria-modal');
    panel.inert = mobile.matches;
    backdrop.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    if (restoreFocus && mobile.matches) toggle.focus();
  }
  toggle.addEventListener('click', () => {
    if (!mobile.matches) return;
    if (isOpen) { closeFilters(); return; }
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    isOpen = true;
    panel.inert = false;
    panel.classList.add('is-open');
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    backdrop.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    closeButton.focus();
  });
  closeButton.addEventListener('click', () => closeFilters());
  backdrop.addEventListener('click', () => closeFilters());
  document.addEventListener('keydown', event => {
    if (!isOpen) return;
    if (event.key === 'Escape') { event.preventDefault(); closeFilters(); }
    if (event.key === 'Tab') {
      const controls = [...panel.querySelectorAll('button, summary, input:not([disabled]), a[href], select')].filter(element => element.getClientRects().length > 0);
      const first = controls[0];
      const last = controls[controls.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
  });
  mobile.addEventListener('change', () => closeFilters(false));
  closeFilters(false);
})();
