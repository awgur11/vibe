jQuery(function ($) {
  'use strict';
  const $dropdown = $('#catalogDropdown');
  const $toggle = $('#catalogToggle');
  if (!$dropdown.length || !$toggle.length) return;

  function closeCatalog() {
    $dropdown.prop('hidden', true);
    $toggle.attr('aria-expanded', 'false');
  }
  function selectChild($link) {
    const $branch = $link.closest('[data-catalog-branch]');
    $branch.find('[data-catalog-child]').removeClass('is-active');
    $link.addClass('is-active');
    $branch.find('[data-catalog-tiles]').each(function () {
      $(this).prop('hidden', $(this).attr('data-catalog-tiles') !== $link.attr('data-catalog-child'));
    });
  }
  function selectRoot($link) {
    $dropdown.find('[data-catalog-root]').removeClass('is-active');
    $link.addClass('is-active');
    $dropdown.find('[data-catalog-branch]').each(function () {
      const $branch = $(this);
      const active = $branch.attr('data-catalog-branch') === $link.attr('data-catalog-root');
      $branch.prop('hidden', !active);
      if (active) selectChild($branch.find('[data-catalog-child]').first());
    });
  }
  $dropdown.find('[data-catalog-root], [data-catalog-child]').each(function () {
    const $link = $(this);
    const activate = () => $link.is('[data-catalog-root]') ? selectRoot($link) : selectChild($link);
    $link.on('pointerenter', event => {
      if (event.originalEvent.pointerType === 'mouse') activate();
    }).on('focus', () => {
      if ($link.is(':focus-visible')) activate();
    }).on('click', event => {
      if ($link.find('.catalog-chevron').length && !$link.hasClass('is-active')) {
        event.preventDefault();
        event.stopPropagation();
        activate();
      }
    });
  });
  $toggle.on('click', event => {
    if (window.matchMedia('(max-width: 680px)').matches) return;
    event.preventDefault();
    const open = $dropdown.prop('hidden');
    $dropdown.prop('hidden', !open);
    $toggle.attr('aria-expanded', String(open));
    if (open && !$dropdown.find('.catalog-roots .is-active').length) {
      const $first = $dropdown.find('[data-catalog-root]').first();
      if ($first.length) selectRoot($first);
    }
  });
  $(document).on('click', event => {
    if (!$(event.target).closest($dropdown.add($toggle)).length) closeCatalog();
  }).on('keydown', event => {
    if (event.key === 'Escape' && !$dropdown.prop('hidden')) {
      closeCatalog();
      $toggle.trigger('focus');
    }
  });
});
