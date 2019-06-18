'use strict';

jQuery(document).ready(function($) {
  console.log("moi");
  function launch_carinbot() {
    jQuery.post(
      seravo_carinbot_loc.ajaxurl, {
        'action': 'seravo_carinbot',
        'nonce': seravo_carinbot_loc.ajax_nonce,
      },
      function () {
        jQuery('#carinbot_loading').fadeOut();
        jQuery('#carinbot').append("moi");
      }
    ).fail(function () {
      jQuery('#carinbot_loading').html('Failed to load. Please try again.');
    });
  }

  // Launch when clicked
  jQuery('#carinbot_button').click(function () {
    console.log("moimoi");
    jQuery('#carinbot_loading img').show();
    jQuery('#carinbot_button').hide();
    launch_carinbot();
  });
});