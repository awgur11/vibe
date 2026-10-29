(() => {
  'use strict';
  const authControl = document.querySelector('[data-auth-control]');
  const desktopSlot = document.querySelector('[data-auth-desktop-slot]');
  const mobileSlot = document.querySelector('[data-auth-mobile-slot]');
  if (authControl && desktopSlot && mobileSlot) {
    const mobile = window.matchMedia('(max-width: 680px)');
    const placeAuthControl = () => {
      const destination = mobile.matches ? mobileSlot : desktopSlot;
      if (authControl.parentElement !== destination) destination.append(authControl);
    };
    placeAuthControl();
    mobile.addEventListener('change', placeAuthControl);
  }
  const account = document.querySelector('[data-account-menu]');
  if (account) {
    const toggle = account.querySelector('[data-account-toggle]');
    const dropdown = account.querySelector('.header-account-dropdown');
    const close = () => { dropdown.hidden = true; toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', () => {
      dropdown.hidden = !dropdown.hidden;
      toggle.setAttribute('aria-expanded', String(!dropdown.hidden));
    });
    document.addEventListener('click', event => { if (!account.contains(event.target)) close(); });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !dropdown.hidden) { close(); toggle.focus(); }
    });
    account.addEventListener('focusout', event => { if (!account.contains(event.relatedTarget)) close(); });
  }
  const dialog = document.querySelector('[data-auth-modal]');
  if (!dialog) return;
  const forms = [...dialog.querySelectorAll('[data-auth-form]')];
  const tabs = [...dialog.querySelectorAll('[data-auth-tab]')];
  const errors = dialog.querySelector('[data-auth-errors]');
  let pending = false;
  function showErrors(messages) {
    errors.replaceChildren();
    messages.forEach(message => {
      const p = document.createElement('p');
      p.textContent = message;
      errors.append(p);
    });
    errors.hidden = messages.length === 0;
  }
  document.querySelectorAll('[data-auth-open]').forEach(button => button.addEventListener('click', () => {
    showErrors([]);
    dialog.showModal();
    forms.find(form => !form.hidden)?.querySelector('input:not([type=hidden])')?.focus();
  }));
  dialog.querySelector('[data-auth-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  tabs.forEach(tab => tab.addEventListener('click', () => {
    if (pending) return;
    showErrors([]);
    forms.forEach(form => { form.hidden = form.dataset.authForm !== tab.dataset.authTab; });
    tabs.forEach(button => button.setAttribute('aria-pressed', String(button === tab)));
    forms.find(form => !form.hidden).querySelector('input:not([type=hidden])').focus();
  }));
  if (['login', 'register'].includes(dialog.dataset.initialScreen)) {
    tabs.find(tab => tab.dataset.authTab === dialog.dataset.initialScreen)?.click();
    dialog.showModal();
    forms.find(form => !form.hidden)?.querySelector('input:not([type=hidden])')?.focus();
  }
  forms.forEach(form => form.addEventListener('submit', async event => {
    event.preventDefault();
    if (pending) return;
    pending = true;
    const submit = form.querySelector('[type=submit]');
    submit.disabled = true;
    tabs.forEach(tab => { tab.disabled = true; });
    showErrors([]);
    try {
      const refresh = await fetch(dialog.dataset.csrfUrl, {credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}, signal: AbortSignal.timeout(15000)});
      if (!refresh.ok) throw new Error('Session refresh failed');
      const session = await refresh.json();
      if (!session.token) throw new Error('Missing token');
      form.querySelector('[name=_token]').value = session.token;
      const response = await fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'Accept': 'application/json'}, signal: AbortSignal.timeout(15000)});
      const data = await response.json();
      if (!response.ok) {
        showErrors(response.status === 422 ? Object.values(data.errors || {}).flat() : [dialog.dataset.error]);
        return;
      }
      if (!data.authenticated) throw new Error('Authentication failed');
      forms.forEach(item => item.querySelectorAll('[type=password]').forEach(input => { input.value = ''; }));
      if (data.redirect) window.location.assign(data.redirect);
      else window.location.reload();
    } catch (error) { showErrors([dialog.dataset.error]); }
    finally { pending = false; submit.disabled = false; tabs.forEach(tab => { tab.disabled = false; }); }
  }));
})();
