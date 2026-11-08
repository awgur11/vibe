jQuery(function ($) {
  'use strict';
  const $dialog = $('[data-callback-modal]');
  if (!$dialog.length) return;
  const $form = $dialog.find('form');
  const $status = $dialog.find('[data-callback-status]');
  let pending = false;
  $('[data-callback-open]').on('click', () => {
    $dialog[0].showModal();
    if (!$form.prop('hidden')) $form.find('input:not([type=hidden])').first().trigger('focus');
  });
  $dialog.find('[data-callback-close]').on('click', () => $dialog[0].close());
  $dialog.on('click', event => { if (event.target === $dialog[0]) $dialog[0].close(); });
  $form.on('submit', async function (event) {
    event.preventDefault();
    if (pending) return;
    pending = true;
    const $submit = $form.find('[type=submit]').prop('disabled', true);
    $status.prop('hidden', true);
    try {
      const session = await $.ajax({url: $dialog.attr('data-csrf-url'), cache: false, dataType: 'json', timeout: 15000});
      if (!session.token) throw new Error('Missing token');
      $form.find('[name=_token]').val(session.token);
      const data = await $.ajax({url: $form.attr('action'), method: 'POST', data: new FormData(this),
        processData: false, contentType: false, dataType: 'json', timeout: 15000});
      $status.prop('hidden', false).attr('class', 'product-review-status').text(data.message);
      this.reset();
      $form.prop('hidden', true);
    } catch (error) {
      $status.prop('hidden', false).attr('class', 'auth-reg-errors')
        .text(error.status === 422 ? Object.values(error.responseJSON?.errors || {}).flat().join(' ') : $dialog.attr('data-error'));
    } finally {
      pending = false;
      $submit.prop('disabled', false);
    }
  });
});
