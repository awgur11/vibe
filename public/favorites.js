jQuery(function ($) {
  'use strict';
  const $root = $('[data-favorites-ui]');
  if (!$root.length) return;
  const $buttons = $('[data-favorite-url]');
  let ids = new Set();
  let token = '';
  let pending = false;
  function render() {
    $('#favoritesCount').text(ids.size);
    $buttons.each(function () {
      const $button = $(this);
      const active = ids.has($button.attr('data-favorite'));
      $button.toggleClass('is-active', active).attr({
        'aria-pressed': String(active),
        'aria-label': $button.attr(active ? 'data-remove-label' : 'data-add-label')
      });
    });
  }
  async function request(url, method = 'GET') {
    const data = await $.ajax({url, method, cache: false, dataType: 'json', timeout: 15000,
      headers: {'X-CSRF-TOKEN': token}});
    ids = new Set(data.product_ids.map(String));
    token = data.token;
    render();
  }
  const ready = request($root.attr('data-state-url')).catch(() => {});
  $buttons.on('click', async function () {
    if (pending) return;
    const $button = $(this);
    pending = true;
    $button.prop('disabled', true);
    try {
      await ready;
      await request($root.attr('data-state-url'));
      const removing = ids.has($button.attr('data-favorite'));
      await request($button.attr('data-favorite-url'), removing ? 'DELETE' : 'POST');
      if (removing && $('[data-favorites-page]').length) window.location.reload();
    } catch (error) {
      window.alert($root.attr('data-error'));
    } finally {
      pending = false;
      $button.prop('disabled', false);
    }
  });
});
