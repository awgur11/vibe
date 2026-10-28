window.adminToast = (message, type = 'success') => {
    const container = document.getElementById('admin-toasts');
    const toast = document.createElement('div');
    toast.className = `admin-toast ${type === 'error' ? 'error' : ''}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    const text = document.createElement('span');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Закрити сповіщення');
    close.textContent = '×';
    const timer = window.setTimeout(() => toast.remove(), type === 'error' ? 7000 : 4000);
    close.addEventListener('click', () => {
        window.clearTimeout(timer);
        toast.remove();
    });
    toast.append(text, close);
    container.append(toast);
};
