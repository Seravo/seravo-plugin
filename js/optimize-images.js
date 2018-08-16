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
    });
})(jQuery);
