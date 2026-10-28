/* Shared UI and article card rendering for the static KINGAVTO blog demo. */
(() => {
  'use strict';
  const posts = window.KINGAVTO_BLOG_POSTS || [];
  const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  const dateLabel = date => new Intl.DateTimeFormat('ru-RU', {day:'numeric', month:'long', year:'numeric', timeZone:'UTC'}).format(new Date(`${date}T12:00:00Z`));
  function postCard(post) {
    const href = `blog-article.html?slug=${encodeURIComponent(post.slug)}`;
    return `<article class="blog-card">
      <a class="blog-card-image" href="${href}" aria-label="Читать: ${escape(post.title)}"><img src="assets/${escape(post.image)}" width="640" height="400" loading="lazy" alt="${escape(post.title)}"></a>
      <div class="blog-card-content">
        <div class="blog-card-info"><span class="blog-tag">${escape(post.category)}</span><time datetime="${escape(post.date)}">${dateLabel(post.date)}</time></div>
        <h3><a class="blog-card-title" href="${href}">${escape(post.title)}</a></h3>
        <p>${escape(post.excerpt)}</p>
        <div class="blog-card-footer"><span>${post.reading} мин чтения</span><a class="blog-card-read" href="${href}">Подробнее <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a></div>
      </div></article>`;
  }
  function initShell() {
    const toggle = document.getElementById('mobileNavToggle');
    const nav = document.getElementById('mainNav');
    toggle?.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    nav?.addEventListener('click', event => {
      if (event.target.closest('a')) { nav.classList.remove('is-open'); toggle?.setAttribute('aria-expanded','false'); }
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') {nav?.classList.remove('is-open');toggle?.setAttribute('aria-expanded','false');}
    });
    try {
      const favorites = JSON.parse(localStorage.getItem('kingavto-favs') || '[]');
      const cart = JSON.parse(localStorage.getItem('kingavto-cart') || '{}');
      const favLabel = document.getElementById('favoritesCount');
      const cartLabel = document.getElementById('cartCount');
      if (favLabel) favLabel.textContent = Array.isArray(favorites) ? favorites.length : 0;
      if (cartLabel) cartLabel.textContent = Object.values(cart).reduce((sum,item) => sum + (Number(item?.qty) || 0),0);
    } catch { /* Browser may disable localStorage */ }
    const year = document.getElementById('year');
    if (year) year.textContent = new Date().getFullYear();
  }
  window.KINGAVTO_BLOG = {posts,escape,dateLabel,postCard};
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',initShell);
  else initShell();
})();
