(() => {
  'use strict';
  const root = document.querySelector('[data-favorites-ui]');
  if (!root) return;
  const buttons = [...document.querySelectorAll('[data-favorite-url]')];
  let ids = new Set();
  let token = '';
  let pending = false;
  function render() {
    const count = document.getElementById('favoritesCount');
    if (count) count.textContent = ids.size;
    buttons.forEach(button => {
      const active = ids.has(String(button.dataset.favorite));
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
      button.setAttribute('aria-label', active ? button.dataset.removeLabel : button.dataset.addLabel);
    });
  }
  async function request(url, method = 'GET') {
    const response = await fetch(url, {
      method, credentials: 'same-origin', cache: 'no-store',
      headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': token},
      signal: AbortSignal.timeout(15000),
    });
    if (!response.ok) throw new Error('Favorites request failed');
    const data = await response.json();
    ids = new Set(data.product_ids.map(String));
    token = data.token;
    render();
  }
  let ready = request(root.dataset.stateUrl).catch(() => {});
  buttons.forEach(button => button.addEventListener('click', async () => {
    if (pending) return;
    pending = true;
    button.disabled = true;
    try {
      await ready;
      await request(root.dataset.stateUrl);
      const removing = ids.has(String(button.dataset.favorite));
      await request(button.dataset.favoriteUrl, removing ? 'DELETE' : 'POST');
      if (removing && document.querySelector('[data-favorites-page]')) window.location.reload();
    } catch (error) {
      window.alert(root.dataset.error);
    } finally {
      pending = false;
      button.disabled = false;
    }
  }));
})();
