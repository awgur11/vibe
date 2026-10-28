/* KINGAVTO — интерактивный фронтенд-макет без сервера и фреймворков.
   Реальные товары, цены, поиск, оформление заказов и VIN-заявки подключаются к вашему Laravel API. */
(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const formatPrice = (price) => new Intl.NumberFormat('ru-RU').format(price) + ' грн';

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
  const dropdown = $('#catalogDropdown');
  const toggle = $('#catalogToggle');
  $('#catalogDropdownLinks').innerHTML = Object.entries(categoryNames)
    .map(([id,title])=>`<a href="#products" data-select-category="${id}">${title}</a>`).join('');
  const closeCatalog = () => { dropdown.hidden = true; toggle.setAttribute('aria-expanded','false'); };
  toggle.addEventListener('click', () => {
    dropdown.hidden = !dropdown.hidden;
    toggle.setAttribute('aria-expanded', String(!dropdown.hidden));
  });
  document.addEventListener('click', e => {
    if (!dropdown.hidden && !dropdown.contains(e.target) && !toggle.contains(e.target)) closeCatalog();
  });

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
  mobileNav.addEventListener('click', e => { if (e.target.closest('a')) { closeMobile(); closeCatalog(); } });

  // Product listing, search, and filters
  const cards = $$('.product-card');
  const filterBtns = $$('.filter-btn');
  const empty = $('#emptyProducts');
  let activeFilter = 'all';
  let searchTerm = '';
  let expanded = false;
  // Support search links from the contacts page.
  const incomingSearch = new URLSearchParams(window.location.search).get('search');
  if (incomingSearch) {
    searchTerm = incomingSearch.trim().toLocaleLowerCase('ru');
    expanded = true;
    $('#siteSearch').value = incomingSearch;
  }

  function renderProducts() {
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
    empty.hidden = count > 0;
    $('#showAllProducts').innerHTML = expanded ? 'Свернуть список <svg class="icon"><use href="#i-arrow"/></svg>' : 'Смотреть все товары <svg class="icon"><use href="#i-arrow"/></svg>';
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
  $('#showAllProducts').addEventListener('click', e => {
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
  $('#siteSearch').addEventListener('input', e => {
    searchTerm = e.target.value.trim().toLocaleLowerCase('ru');
    activeFilter='all';expanded=true;
    renderProducts();
  });
  $('#headerSearch').addEventListener('submit', e => {
    e.preventDefault();
    $('#products').scrollIntoView({behavior:'smooth'});
    if (!searchTerm) { toast('Введите название или артикул для поиска');$('#siteSearch').focus(); }
  });

  // Favorites + shopping cart (localStorage demo)
  const favorites = new Set(getJSON('kingavto-favs', []));
  const cart = getJSON('kingavto-cart', {});
  const toastElement = $('#toast');
  let toastTimeout;
  function toast(message) {
    toastElement.textContent = message;
    toastElement.classList.add('show');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => toastElement.classList.remove('show'), 2600);
  }
  function updateCounters() {
    $('#favoritesCount').textContent = favorites.size;
    $('#cartCount').textContent = Object.values(cart).reduce((sum,item) => sum + item.qty,0);
    $$('.favorite-toggle').forEach(button => {
      const active = favorites.has(button.dataset.favorite);
      button.classList.toggle('is-active',active);
      button.setAttribute('aria-label',active?'Убрать из избранного':'Добавить в избранное');
      button.setAttribute('aria-pressed',String(active));
    });
  }
  $$('.favorite-toggle').forEach(button => button.addEventListener('click', () => {
    const id = button.dataset.favorite;
    if (favorites.has(id)) { favorites.delete(id);toast('Товар удалён из избранного'); }
    else { favorites.add(id);toast('Товар добавлен в избранное'); }
    setJSON('kingavto-favs',[...favorites]);
    updateCounters();
  }));
  $$('.add-cart').forEach(button => button.addEventListener('click', () => {
    const {id, name, price} = button.dataset;
    if (!cart[id]) cart[id] = {id,name,price:Number(price),qty:0};
    cart[id].qty += 1;
    setJSON('kingavto-cart',cart);
    updateCounters();
    toast('Добавлено в корзину: ' + name);
  }));

  const drawer = $('#cartDrawer');
  const backdrop = $('#drawerBackdrop');
  let restoreFocus = null;
  function openDrawer() {
    restoreFocus = document.activeElement;
    drawer.hidden=false;backdrop.hidden=false;
    document.body.style.overflow='hidden';
    renderDrawer();
    $('#closeDrawer').focus();
  }
  function closeDrawer() {
    drawer.hidden=true;backdrop.hidden=true;
    document.body.style.overflow='';
    restoreFocus?.focus?.();
  }
  function renderDrawer() {
    const items = Object.values(cart);
    const root = $('#drawerItems');
    root.innerHTML = '';
    if (!items.length) root.textContent='В корзине пока нет товаров.';
    let total=0;
    items.forEach(item => {
      total += item.qty * item.price;
      const row = document.createElement('div');
      row.className='drawer-product';
      const details = document.createElement('div');
      const name = document.createElement('strong'); name.textContent=item.name;
      const price = document.createElement('small');price.textContent = `${item.qty} × ${formatPrice(item.price)}`;
      details.append(name,price);
      const remove = document.createElement('button');
      remove.type='button';remove.className='drawer-remove';remove.title='Удалить товар';remove.setAttribute('aria-label','Удалить '+item.name);
      remove.innerHTML='<svg class="icon"><use href="#i-trash"/></svg>';
      remove.addEventListener('click',()=> {delete cart[item.id];setJSON('kingavto-cart',cart);renderDrawer();updateCounters();});
      row.append(details,remove);root.append(row);
    });
    $('#drawerTotal').textContent=formatPrice(total);
  }
  $('#cartButton').addEventListener('click',openDrawer);
  $('#closeDrawer').addEventListener('click',closeDrawer);
  $('#closeDrawerBottom').addEventListener('click',closeDrawer);
  backdrop.addEventListener('click',closeDrawer);
  $('#favoritesButton').addEventListener('click', () => {
    activeFilter='all';searchTerm='';expanded=true;renderProducts();
    let seen=0;
    cards.forEach(card => {card.hidden = !favorites.has(card.dataset.id); if (!card.hidden) seen++;});
    empty.hidden=seen>0;
    if (!seen) empty.textContent='Список избранного пуст. Добавьте товары с помощью значка сердечка.';
    else empty.textContent='По вашему запросу товары не найдены. Попробуйте другой запрос или раздел.';
    $('#products').scrollIntoView({behavior:'smooth'});
  });
  $('#compareButton').addEventListener('click',()=>toast('Сравнение товаров подключается к каталогу в Laravel'));
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeCatalog();closeMobile();if (!drawer.hidden) closeDrawer(); }
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
    const count=maxNewsIndex()+1;
    dots.innerHTML='';
    for(let index=0;index<count;index++) {
      const dot=document.createElement('button');
      dot.type='button';
      dot.classList.toggle('active',index === newsIndex);
      dot.setAttribute('aria-label',`Показать группу новостей ${index+1}`);
      dot.setAttribute('aria-current',String(index === newsIndex));
      dot.addEventListener('click',()=>scrollNews(index));
      dots.append(dot);
    }
    $('#newsPrev').disabled=newsIndex===0;
    $('#newsNext').disabled=newsIndex>=maxNewsIndex();
  }
  function scrollNews(index) {
    newsIndex=Math.max(0,Math.min(maxNewsIndex(),index));
    track.scrollTo({left:newsIndex*newsStep(),behavior:'smooth'});
    updateNewsDots();
  }
  $('#newsPrev').addEventListener('click',()=>scrollNews(newsIndex-1));
  $('#newsNext').addEventListener('click',()=>scrollNews(newsIndex+1));
  let resizeTimer;
  window.addEventListener('resize',()=> {
    clearTimeout(resizeTimer);
    resizeTimer=setTimeout(()=>scrollNews(Math.min(newsIndex,maxNewsIndex())),120);
  });
  track.addEventListener('scroll', () => {
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
  $('#vinForm').addEventListener('submit',event => {
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
