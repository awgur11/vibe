(() => {
  'use strict';
  const dialog = document.querySelector('[data-comparison-ui]');
  if (!dialog) return;
  const trigger = document.getElementById('compareButton');
  const buttons = [...document.querySelectorAll('[data-compare-url]')];
  const status = dialog.querySelector('[data-comparison-status]');
  const categories = dialog.querySelector('[data-comparison-categories]');
  const empty = dialog.querySelector('[data-comparison-empty]');
  let ids = new Set();
  let token = '';
  let pending = false;
  function render(data) {
    ids = new Set(data.product_ids.map(String));
    token = data.token;
    const counter = document.getElementById('compareCount');
    if (counter) counter.textContent = ids.size;
    buttons.forEach(button => {
      const active = ids.has(button.dataset.compare);
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
      button.setAttribute('aria-label', active ? button.dataset.removeLabel : button.dataset.addLabel);
    });
    categories.replaceChildren();
    data.categories.forEach(category => {
      const link = document.createElement('a');
      link.href = category.url;
      link.textContent = `${category.title} (${category.count})`;
      categories.append(link);
    });
    empty.hidden = data.categories.length > 0;
  }
  async function request(url, method = 'GET') {
    const response = await fetch(url, {
      method, credentials: 'same-origin', cache: 'no-store',
      headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': token},
      signal: AbortSignal.timeout(15000),
    });
    if (!response.ok) throw new Error('Comparison request failed');
    render(await response.json());
  }
  const ready = request(dialog.dataset.stateUrl).catch(() => {});
  trigger?.addEventListener('click', async () => {
    if (!dialog.open) dialog.showModal();
    status.textContent = '';
    try { await ready; await request(dialog.dataset.stateUrl); }
    catch (error) { status.textContent = dialog.dataset.error; }
  });
  dialog.querySelector('[data-comparison-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  buttons.forEach(button => button.addEventListener('click', async () => {
    if (pending) return;
    pending = true;
    button.disabled = true;
    try {
      await ready;
      await request(dialog.dataset.stateUrl);
      const removing = button.hasAttribute('data-compare-remove') || ids.has(button.dataset.compare);
      await request(button.dataset.compareUrl, removing ? 'DELETE' : 'POST');
      if (removing && document.querySelector('[data-comparison-page]')) window.location.reload();
    } catch (error) { window.alert(dialog.dataset.error); }
    finally { pending = false; button.disabled = false; }
  }));
})();
