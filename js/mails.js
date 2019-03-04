jQuery(document).ready(function($) {
  jQuery('.ui-sortable-handle').on('click', function () {
    jQuery(this).parent().toggleClass("closed");
  });
  jQuery('.postbox > .button-link').on('click', function () {
    jQuery(this).parent().toggleClass("closed");
  });
});
