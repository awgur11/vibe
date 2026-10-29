(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const dropdown = $('#catalogDropdown');
  const toggle = $('#catalogToggle');
  if (!dropdown || !toggle) return;
  const closeCatalog = () => { dropdown.hidden = true; toggle.setAttribute('aria-expanded','false'); };
  const selectChild = (link) => {
    const branch = link.closest('[data-catalog-branch]');
    $$('[data-catalog-child]', branch).forEach(item => item.classList.toggle('is-active', item === link));
    $$('[data-catalog-tiles]', branch).forEach(panel => { panel.hidden = panel.dataset.catalogTiles !== link.dataset.catalogChild; });
  };
  const selectRoot = (link) => {
    $$('[data-catalog-root]', dropdown).forEach(item => item.classList.toggle('is-active', item === link));
    $$('[data-catalog-branch]', dropdown).forEach(panel => {
      panel.hidden = panel.dataset.catalogBranch !== link.dataset.catalogRoot;
      if (!panel.hidden) {
        const firstChild = $('[data-catalog-child]', panel);
        if (firstChild) selectChild(firstChild);
      }
    });
  };
  $$('[data-catalog-root], [data-catalog-child]', dropdown).forEach(link => {
    const activate = () => link.hasAttribute('data-catalog-root') ? selectRoot(link) : selectChild(link);
    link.addEventListener('pointerenter', event => { if (event.pointerType === 'mouse') activate(); });
    link.addEventListener('focus', () => { if (link.matches(':focus-visible')) activate(); });
    link.addEventListener('click', event => {
      const hasChildren = link.querySelector('.catalog-chevron');
      if (hasChildren && !link.classList.contains('is-active')) {
        event.preventDefault();
        event.stopPropagation();
        activate();
      }
    });
  });
  toggle.addEventListener('click', event => {
    if (window.matchMedia('(max-width: 680px)').matches) return;
    event.preventDefault();
    dropdown.hidden = !dropdown.hidden;
    toggle.setAttribute('aria-expanded', String(!dropdown.hidden));
    if (!dropdown.hidden && !$('.catalog-roots .is-active', dropdown)) {
      const firstRoot = $('[data-catalog-root]', dropdown);
      if (firstRoot) selectRoot(firstRoot);
    }
  });
  document.addEventListener('click', e => {
    if (!dropdown.hidden && !dropdown.contains(e.target) && !toggle.contains(e.target)) closeCatalog();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !dropdown.hidden) { closeCatalog(); toggle.focus(); }
  });

})();
