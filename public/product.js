(() => {
  const gallery = document.querySelector('[data-product-gallery]');
  if (!gallery) return;
  const images = [...gallery.querySelectorAll('[data-gallery-image]')];
  if (!images.length) return;
  const main = gallery.querySelector('[data-gallery-main]');
  const full = gallery.querySelector('[data-gallery-full]');
  const dialog = gallery.querySelector('[data-gallery-dialog]');
  let selected = 0;
  function select(index) {
    selected = (index + images.length) % images.length;
    main.src = full.src = images[selected].dataset.galleryImage;
    images.forEach((image, i) => image.setAttribute('aria-pressed', String(i === selected)));
  }
  images.forEach((image, index) => image.addEventListener('click', () => select(index)));
  gallery.querySelector('[data-gallery-open]').addEventListener('click', () => dialog.showModal());
  gallery.querySelector('[data-gallery-close]').addEventListener('click', () => dialog.close());
  gallery.querySelector('[data-gallery-prev]').addEventListener('click', () => select(selected - 1));
  gallery.querySelector('[data-gallery-next]').addEventListener('click', () => select(selected + 1));
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  dialog.addEventListener('keydown', event => {
    if (event.key === 'ArrowLeft') { event.preventDefault(); select(selected - 1); }
    if (event.key === 'ArrowRight') { event.preventDefault(); select(selected + 1); }
  });
})();
