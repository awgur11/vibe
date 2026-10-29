(() => {
  'use strict';
  const root = document.querySelector('[data-site-search]');
  if (!root) return;
  const input = root.querySelector('input');
  const form = root.querySelector('form');
  const popup = root.querySelector('#searchSuggestions');
  const results = root.querySelector('[data-search-results]');
  const status = root.querySelector('[data-search-status]');
  const all = root.querySelector('[data-search-all]');
  let timer;
  let controller;
  let version = 0;
  let selected = -1;
  function close() { popup.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); selected = -1; }
  function cancel() { clearTimeout(timer); controller?.abort(); version++; }
  async function search(query, requestVersion) {
    controller = new AbortController();
    try {
      const url = new URL(root.dataset.suggestionsUrl, window.location.href);
      url.searchParams.set('q', query);
      const response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin', signal: controller.signal});
      if (!response.ok) throw new Error('Search failed');
      const data = await response.json();
      if (requestVersion !== version) return;
      results.replaceChildren();
      selected = -1;
      data.products.forEach((product, index) => {
        const link = document.createElement('a');
        link.href = product.url;
        link.id = `search-option-${index}`;
        link.setAttribute('role', 'option');
        link.setAttribute('aria-selected', 'false');
        link.tabIndex = -1;
        if (product.image) { const image = document.createElement('img'); image.src = product.image; image.alt = ''; link.append(image); }
        const title = document.createElement('span'); title.textContent = product.title;
        const price = document.createElement('strong'); price.textContent = `${product.price} грн`;
        link.append(title, price);
        results.append(link);
      });
      status.textContent = data.products.length ? '' : root.dataset.empty;
      const target = new URL(form.action); target.searchParams.set('q', query); all.href = target.href;
      popup.hidden = false; input.setAttribute('aria-expanded', 'true');
    } catch (error) {
      if (requestVersion !== version || error.name === 'AbortError') return;
      results.replaceChildren(); status.textContent = root.dataset.error;
      const target = new URL(form.action); target.searchParams.set('q', query); all.href = target.href;
      popup.hidden = false; input.setAttribute('aria-expanded', 'true');
    }
  }
  input.addEventListener('input', () => {
    cancel(); close();
    const query = input.value.trim();
    if ([...query].length < 3) return;
    const requestVersion = version;
    timer = setTimeout(() => search(query, requestVersion), 300);
  });
  input.addEventListener('keydown', event => {
    if (event.key === 'Escape') { cancel(); close(); return; }
    const links = [...results.children];
    if (popup.hidden || !links.length) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      selected = selected < 0 ? (event.key === 'ArrowDown' ? 0 : links.length - 1) : (selected + (event.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
      links.forEach((link, index) => link.setAttribute('aria-selected', String(index === selected)));
      input.setAttribute('aria-activedescendant', links[selected].id);
      links[selected].scrollIntoView({block: 'nearest'});
    }
  });
  form.addEventListener('submit', () => { cancel(); close(); });
  document.addEventListener('click', event => { if (!root.contains(event.target)) { cancel(); close(); } });
  root.addEventListener('focusout', event => { if (!root.contains(event.relatedTarget)) { cancel(); close(); } });
})();
