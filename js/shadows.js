'use strict';

jQuery(document).ready(function($) {
  $('.shadow-reset').click(function(event) {
    seravo_ajax_reset_shadow($(this).attr("data-shadow-name"),
      function( status ){
        if( status == 'progress' ) {
          event.target.disabled = true;
        }
        else if( status == 'success' ){
          event.target.innerHTML = 'DONE';
        }
        else {
          console.log('Unknown status');
        }
      });
  });

  function seravo_ajax_reset_shadow(shadow, animate) {
    animate('progress');
    $.post(
      ajaxurl,
      { type: 'POST',
        'action': 'seravo_reset_shadow',
        'resetshadow': shadow },
        function( rawData ) {
          var data = JSON.parse(rawData);
          console.log(data);
          animate('success');
      }
    );
  }
});
    