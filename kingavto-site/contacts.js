/* Контакты KINGAVTO — без внешних зависимостей */
(() => {
  'use strict';
  const nav = document.getElementById('mainNav');
  const toggle = document.getElementById('mobileNavToggle');
  toggle?.addEventListener('click', () => {
    const expanded = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(expanded));
  });
  nav?.addEventListener('click', (event) => {
    if (event.target.closest('a')) {
      nav.classList.remove('is-open');
      toggle?.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      nav?.classList.remove('is-open');
      toggle?.setAttribute('aria-expanded', 'false');
    }
  });
  try {
    const favorites = JSON.parse(localStorage.getItem('kingavto-favs') || '[]');
    const cart = JSON.parse(localStorage.getItem('kingavto-cart') || '{}');
    document.getElementById('favoritesCount').textContent = Array.isArray(favorites) ? favorites.length : 0;
    document.getElementById('cartCount').textContent = Object.values(cart).reduce((sum,item) => sum + (Number(item.qty) || 0), 0);
  } catch { /* LocalStorage может быть отключен */ }
  document.getElementById('year').textContent = new Date().getFullYear();
  const copy = document.getElementById('copyAddress');
  let restoreLabel;
  copy?.addEventListener('click', async () => {
    const address = copy.dataset.address;
    try {
      await navigator.clipboard.writeText(address);
      clearTimeout(restoreLabel);
      copy.textContent = 'Адрес скопирован!';
      restoreLabel = setTimeout(() => { copy.textContent = 'Скопировать адрес'; }, 2600);
    } catch {
      // На file:// и при закрытых разрешениях Clipboard API недоступен.
      const hint = document.getElementById('toast');
      hint.textContent = 'Не удалось скопировать автоматически — выделите адрес на странице.';
      hint.classList.add('show');
      setTimeout(() => hint.classList.remove('show'), 3500);
    }
  });
})();
