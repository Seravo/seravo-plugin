'use strict';

(function($) {
    $(window).on('load', function() {
        $('#enable-optimize-images').click(function() {
            $('.max-resolution-field').each(function() {
                $(this).prop( "disabled", ! $(this).prop( "disabled" ) );
            });
        });
    });
})(jQuery);
