jQuery(function ($) {
  'use strict';
  const $root = $('[data-site-search]');
  if (!$root.length) return;
  const $input = $root.find('input');
  const $form = $root.find('form');
  const $popup = $root.find('#searchSuggestions');
  const $results = $root.find('[data-search-results]');
  const $status = $root.find('[data-search-status]');
  const $all = $root.find('[data-search-all]');
  let timer;
  let request;
  let version = 0;
  let selected = -1;
  function close() {
    $popup.prop('hidden', true);
    $input.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
    selected = -1;
  }
  function cancel() {
    clearTimeout(timer);
    version++;
    request?.abort();
  }
  function showPopup(query) {
    const target = new URL($form.prop('action'));
    target.searchParams.set('q', query);
    $all.attr('href', target.href);
    $popup.prop('hidden', false);
    $input.attr('aria-expanded', 'true');
  }
  async function search(query, requestVersion) {
    request = $.ajax({url: $root.attr('data-suggestions-url'), data: {q: query}, dataType: 'json'});
    try {
      const data = await request;
      if (requestVersion !== version) return;
      $results.empty();
      selected = -1;
      data.products.forEach((product, index) => {
        const $link = $('<a>').attr({href: product.url, id: `search-option-${index}`,
          role: 'option', 'aria-selected': 'false', tabindex: -1});
        if (product.image) $('<img>').attr({src: product.image, alt: ''}).appendTo($link);
        $('<span>').text(product.title).appendTo($link);
        $('<strong>').text(`${product.price} грн`).appendTo($link);
        $link.appendTo($results);
      });
      $status.text(data.products.length ? '' : $root.attr('data-empty'));
      showPopup(query);
    } catch (error) {
      if (requestVersion !== version || error.statusText === 'abort') return;
      $results.empty();
      $status.text($root.attr('data-error'));
      showPopup(query);
    }
  }
  $input.on('input', () => {
    cancel();
    close();
    const query = $input.val().trim();
    if ([...query].length < 3) return;
    const requestVersion = version;
    timer = setTimeout(() => search(query, requestVersion), 300);
  }).on('keydown', event => {
    if (event.key === 'Escape') { cancel(); close(); return; }
    const $links = $results.children();
    if ($popup.prop('hidden') || !$links.length) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      selected = selected < 0 ? (event.key === 'ArrowDown' ? 0 : $links.length - 1)
        : (selected + (event.key === 'ArrowDown' ? 1 : -1) + $links.length) % $links.length;
      $links.attr('aria-selected', 'false').eq(selected).attr('aria-selected', 'true');
      $input.attr('aria-activedescendant', $links.eq(selected).attr('id'));
      $links[selected].scrollIntoView({block: 'nearest'});
    }
  });
  $form.on('submit', () => { cancel(); close(); });
  $(document).on('click', event => { if (!$(event.target).closest($root).length) { cancel(); close(); } });
  $root.on('focusout', event => { if (!$(event.relatedTarget).closest($root).length) { cancel(); close(); } });
});
