// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function($) {
  $('.shadow-reset').click(function(event) {
    var is_user_sure = confirm(seravo_shadows_loc.confirm);
    if ( ! is_user_sure) {
      return;
    }
    seravo_ajax_reset_shadow($(this).attr("data-shadow-name"),
      function( status ){
        if ( status == 'progress' ) {
          event.target.disabled = true;
        } else if ( status == 'success' ) {
          event.target.innerHTML = seravo_shadows_loc.success;
        } else if ( status == 'failure' ) {
          event.target.innerHTML = seravo_shadows_loc.failure;
        } else {
          event.target.innerHTML = seravo_shadows_loc.error;
        }
      });
  });

  function seravo_ajax_reset_shadow(shadow, animate) {
    animate('progress');
    $.post(
      seravo_shadows_loc.ajaxurl,
      { type: 'POST',
        'action': 'seravo_reset_shadow',
        'resetshadow': shadow,
        'nonce': seravo_shadows_loc.ajax_nonce, },
        function( rawData ) {
          var data = JSON.parse(rawData);
          // If the last row of rawData does not begin with SUCCESS:
          if ( data[data.length - 1].search('Success') ) {
            animate('success');
          } else {
            animate('failure');
          }
        }
    );
  }
});
