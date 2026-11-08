jQuery(function ($) {
  'use strict';
  const $gallery = $('[data-product-gallery]');
  const $images = $gallery.find('[data-gallery-image]');
  if (!$images.length) return;
  const $main = $gallery.find('[data-gallery-main]');
  const $full = $gallery.find('[data-gallery-full]');
  const $dialog = $gallery.find('[data-gallery-dialog]');
  let selected = 0;
  function select(index) {
    selected = (index + $images.length) % $images.length;
    $main.add($full).attr('src', $images.eq(selected).attr('data-gallery-image'));
    $images.attr('aria-pressed', 'false').eq(selected).attr('aria-pressed', 'true');
  }
  $images.on('click', function () { select($images.index(this)); });
  $gallery.find('[data-gallery-open]').on('click', () => $dialog[0].showModal());
  $gallery.find('[data-gallery-close]').on('click', () => $dialog[0].close());
  $gallery.find('[data-gallery-prev]').on('click', () => select(selected - 1));
  $gallery.find('[data-gallery-next]').on('click', () => select(selected + 1));
  $dialog.on('click', event => { if (event.target === $dialog[0]) $dialog[0].close(); })
    .on('keydown', event => {
      if (event.key === 'ArrowLeft') { event.preventDefault(); select(selected - 1); }
      if (event.key === 'ArrowRight') { event.preventDefault(); select(selected + 1); }
    });
});
