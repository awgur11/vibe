(() => {
  const dialog = document.querySelector('[data-callback-modal]');
  if (!dialog) return;
  const form = dialog.querySelector('form');
  const status = dialog.querySelector('[data-callback-status]');
  let pending = false;
  document.querySelectorAll('[data-callback-open]').forEach(button => button.addEventListener('click', () => {
    dialog.showModal();
    if (!form.hidden) form.querySelector('input:not([type=hidden])').focus();
  }));
  dialog.querySelector('[data-callback-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (pending) return;
    pending = true;
    const submit = form.querySelector('[type=submit]');
    submit.disabled = true; status.hidden = true;
    try {
      const refresh = await fetch(dialog.dataset.csrfUrl, {credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}, signal: AbortSignal.timeout(15000)});
      if (!refresh.ok) throw new Error();
      const session = await refresh.json();
      if (!session.token) throw new Error();
      form.querySelector('[name=_token]').value = session.token;
      const response = await fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {'Accept': 'application/json'}, signal: AbortSignal.timeout(15000)});
      const data = await response.json();
      status.hidden = false;
      if (!response.ok) {
        status.className = 'auth-reg-errors';
        status.textContent = response.status === 422 ? Object.values(data.errors || {}).flat().join(' ') : dialog.dataset.error;
        return;
      }
      status.className = 'product-review-status'; status.textContent = data.message;
      form.reset(); form.hidden = true;
    } catch (error) { status.hidden = false; status.className = 'auth-reg-errors'; status.textContent = dialog.dataset.error; }
    finally { pending = false; submit.disabled = false; }
  });
})();
