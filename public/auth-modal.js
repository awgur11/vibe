jQuery(function ($) {
  'use strict';
  const $authControl = $('[data-auth-control]');
  const $desktopSlot = $('[data-auth-desktop-slot]');
  const $mobileSlot = $('[data-auth-mobile-slot]');
  if ($authControl.length && $desktopSlot.length && $mobileSlot.length) {
    const mobile = window.matchMedia('(max-width: 680px)');
    const placeAuthControl = () => {
      const $destination = mobile.matches ? $mobileSlot : $desktopSlot;
      if (!$authControl.parent().is($destination)) $authControl.appendTo($destination);
    };
    placeAuthControl();
    $(mobile).on('change', placeAuthControl);
  }
  const $account = $('[data-account-menu]');
  if ($account.length) {
    const $toggle = $account.find('[data-account-toggle]');
    const $dropdown = $account.find('.header-account-dropdown');
    const close = () => { $dropdown.prop('hidden', true); $toggle.attr('aria-expanded', 'false'); };
    $toggle.on('click', () => {
      const open = $dropdown.prop('hidden');
      $dropdown.prop('hidden', !open);
      $toggle.attr('aria-expanded', String(open));
    });
    $(document).on('click', event => { if (!$(event.target).closest($account).length) close(); })
      .on('keydown', event => {
        if (event.key === 'Escape' && !$dropdown.prop('hidden')) { close(); $toggle.trigger('focus'); }
      });
    $account.on('focusout', event => { if (!$(event.relatedTarget).closest($account).length) close(); });
  }
  const $dialog = $('[data-auth-modal]');
  if (!$dialog.length) return;
  const $forms = $dialog.find('[data-auth-form]');
  const $tabs = $dialog.find('[data-auth-tab]');
  const $errors = $dialog.find('[data-auth-errors]');
  let pending = false;
  function showErrors(messages) {
    $errors.empty().prop('hidden', messages.length === 0);
    messages.forEach(message => $('<p>').text(message).appendTo($errors));
  }
  function focusForm() {
    $forms.filter(function () { return !this.hidden; }).find('input:not([type=hidden])').first().trigger('focus');
  }
  $('[data-auth-open]').on('click', () => {
    showErrors([]);
    $dialog[0].showModal();
    focusForm();
  });
  $dialog.find('[data-auth-close]').on('click', () => $dialog[0].close());
  $dialog.on('click', event => { if (event.target === $dialog[0]) $dialog[0].close(); });
  $tabs.on('click', function () {
    if (pending) return;
    const $tab = $(this);
    showErrors([]);
    $forms.each(function () { $(this).prop('hidden', $(this).attr('data-auth-form') !== $tab.attr('data-auth-tab')); });
    $tabs.attr('aria-pressed', 'false');
    $tab.attr('aria-pressed', 'true');
    focusForm();
  });
  const initialScreen = $dialog.attr('data-initial-screen');
  if (['login', 'register'].includes(initialScreen)) {
    $tabs.filter(function () { return $(this).attr('data-auth-tab') === initialScreen; }).trigger('click');
    $dialog[0].showModal();
    focusForm();
  }
  $forms.on('submit', async function (event) {
    event.preventDefault();
    if (pending) return;
    const $form = $(this);
    const $submit = $form.find('[type=submit]');
    pending = true;
    $submit.add($tabs).prop('disabled', true);
    showErrors([]);
    try {
      const session = await $.ajax({url: $dialog.attr('data-csrf-url'), cache: false, dataType: 'json', timeout: 15000});
      if (!session.token) throw new Error('Missing token');
      $form.find('[name=_token]').val(session.token);
      const data = await $.ajax({url: $form.attr('action'), method: 'POST', data: new FormData(this),
        processData: false, contentType: false, dataType: 'json', timeout: 15000});
      if (!data.authenticated) throw new Error('Authentication failed');
      $forms.find('[type=password]').val('');
      if (data.redirect) window.location.assign(data.redirect);
      else window.location.reload();
    } catch (error) {
      showErrors(error.status === 422 ? Object.values(error.responseJSON?.errors || {}).flat() : [$dialog.attr('data-error')]);
    } finally {
      pending = false;
      $submit.add($tabs).prop('disabled', false);
    }
  });
});
