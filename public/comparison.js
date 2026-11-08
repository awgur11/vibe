jQuery(function ($) {
  'use strict';
  const $dialog = $('[data-comparison-ui]');
  if (!$dialog.length) return;
  const $buttons = $('[data-compare-url]');
  const $status = $dialog.find('[data-comparison-status]');
  const $categories = $dialog.find('[data-comparison-categories]');
  const $empty = $dialog.find('[data-comparison-empty]');
  let ids = new Set();
  let token = '';
  let pending = false;
  function render(data) {
    ids = new Set(data.product_ids.map(String));
    token = data.token;
    $('#compareCount').text(ids.size);
    $buttons.each(function () {
      const $button = $(this);
      const active = ids.has($button.attr('data-compare'));
      $button.toggleClass('is-active', active).attr({
        'aria-pressed': String(active),
        'aria-label': $button.attr(active ? 'data-remove-label' : 'data-add-label')
      });
    });
    $categories.empty();
    data.categories.forEach(category => {
      $('<a>').attr('href', category.url).text(`${category.title} (${category.count})`).appendTo($categories);
    });
    $empty.prop('hidden', data.categories.length > 0);
  }
  async function request(url, method = 'GET') {
    render(await $.ajax({url, method, cache: false, dataType: 'json', timeout: 15000,
      headers: {'X-CSRF-TOKEN': token}}));
  }
  const ready = request($dialog.attr('data-state-url')).catch(() => {});
  $('#compareButton').on('click', async () => {
    if (!$dialog.prop('open')) $dialog[0].showModal();
    $status.text('');
    try { await ready; await request($dialog.attr('data-state-url')); }
    catch (error) { $status.text($dialog.attr('data-error')); }
  });
  $dialog.find('[data-comparison-close]').on('click', () => $dialog[0].close());
  $dialog.on('click', event => { if (event.target === $dialog[0]) $dialog[0].close(); });
  $buttons.on('click', async function () {
    if (pending) return;
    const $button = $(this);
    pending = true;
    $button.prop('disabled', true);
    try {
      await ready;
      await request($dialog.attr('data-state-url'));
      const removing = $button.is('[data-compare-remove]') || ids.has($button.attr('data-compare'));
      await request($button.attr('data-compare-url'), removing ? 'DELETE' : 'POST');
      if (removing && $('[data-comparison-page]').length) window.location.reload();
    } catch (error) { window.alert($dialog.attr('data-error')); }
    finally { pending = false; $button.prop('disabled', false); }
  });
});
