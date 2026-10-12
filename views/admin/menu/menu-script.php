<?php
/**
 * View: the script of the admin page of a list of links. It adds, opens, removes and drags the items;
 * the form itself saves them. Printed in the footer of that page, after jQuery and its sortable script.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<script>
jQuery(function ($) {
  var list = $('#trigrexam-items');
  var template = $('#trigrexam-item-template').html();
  var added = 0;

  // The list is empty for its "nothing in this list yet" text only without white space in it.
  list.contents().filter(function () {
    return this.nodeType === 3;
  }).remove();

  function add(title, pageId, url, pageTitle) {
    added++;
    var item = $($.parseHTML($.trim(template.replace(/__KEY__/g, 'new-' + added))));
    item.toggleClass('trigrexam-item-page', pageId > 0).toggleClass('trigrexam-item-custom', !pageId);
    item.find('.trigrexam-item-title').text(title);
    item.find('.trigrexam-item-type').text(pageId ? 'Page' : 'Custom Link');
    item.find('.trigrexam-item-title-input').val(title);
    item.find('.trigrexam-item-page-id').val(pageId);
    if (pageId) {
      item.find('.trigrexam-item-page-link').attr('href', url).text(pageTitle);
    } else {
      item.find('.trigrexam-item-url-input').val(url);
    }
    list.append(item);
  }

  $('#trigrexam-pages-search').on('input', function () {
    var search = $.trim($(this).val()).toLowerCase();
    $('.trigrexam-pages li').each(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
    });
  });

  $('#trigrexam-add-pages').on('click', function () {
    $('.trigrexam-pages input:checked').each(function () {
      var page = $(this);
      add(page.attr('data-title'), parseInt(page.val(), 10), page.attr('data-url'), page.attr('data-title'));
      page.prop('checked', false);
    });
  });

  $('#trigrexam-add-custom').on('click', function () {
    var url = $('#trigrexam-custom-url');
    var title = $('#trigrexam-custom-title');
    if ($.trim(title.val()) === '') {
      title.trigger('focus');
      return;
    }
    add($.trim(title.val()), 0, $.trim(url.val()), '');
    url.val('');
    title.val('');
  });

  list.on('click', '.trigrexam-item-toggle', function () {
    var settings = $(this).closest('.trigrexam-item').find('.trigrexam-item-settings');
    var open = settings.prop('hidden');
    settings.prop('hidden', !open);
    $(this).attr('aria-expanded', open ? 'true' : 'false').text(open ? 'Close' : 'Edit');
  });

  list.on('click', '.trigrexam-item-remove', function () {
    $(this).closest('.trigrexam-item').remove();
  });

  list.on('input', '.trigrexam-item-title-input', function () {
    $(this).closest('.trigrexam-item').find('.trigrexam-item-title').text($(this).val());
  });

  list.sortable({
    handle: '.trigrexam-item-bar',
    cancel: '.trigrexam-item-toggle',
    placeholder: 'trigrexam-item-placeholder',
    axis: 'y'
  });
});
</script>
