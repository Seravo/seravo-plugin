'use strict';

(function($) {
    $(window).on('load', function() {
        $('#enable-optimize-images').click(function() {
            $('.max-resolution-field').each(function() {
                $(this).stop();
                $(this).fadeToggle(500);
                $(this).removeClass('.max-resolution-field');
                $(this).addClass('.max-resolution-field-disabled');
            });
            $('.max-resolution-field-disabled').each(function() {
                $(this).stop();
                $(this).fadeToggle(100);
                $(this).removeClass('.max-resolution-field-disabled');
                $(this).addClass('.max-resolution-field');
            });
        });
        //Postbox animations
        jQuery('.ui-sortable-handle').on('click', function () {
            jQuery(this).parent().toggleClass("closed");
          if (jQuery(this).parent().hasClass("closed")) {
            jQuery(this).parents().eq(3).height(60);
          } else {
            jQuery(this).parents().eq(3).height('auto');
          }
        });
        jQuery('.toggle-indicator').on('click', function () {
            jQuery(this).parent().parent().toggleClass("closed");
          if (jQuery(this).parent().hasClass("closed")) {
            jQuery(this).parents().eq(4).height(60);
          } else {
            jQuery(this).parents().eq(4).height('auto');
          }
        });
    });
})(jQuery);
