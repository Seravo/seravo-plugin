'use strict';

jQuery(document).ready(function($) {
  console.log("js");
  function launch_carinbot() {
    console.log("funktio")
    jQuery.post(
      seravo_carinbot_loc.ajaxurl, {
        'action': 'seravo_carinbot',
        'nonce': seravo_carinbot_loc.ajax_nonce,
      },
      function () {
        jQuery('#carinbot_loading').fadeOut();
        jQuery('#carinbot').append("Carin Bot activated.");
        console.log("success")
      }
    ).fail(function () {
      console.log("fail")
      jQuery('#carinbot_loading').html('Carin Bot failed to load.');
    });
  }

  // Launch when clicked
  jQuery('#carinbot_button').click(function () {
    console.log("nappi");
    jQuery('#carinbot_loading img').show();
    jQuery('#carinbot_button').hide();
    launch_carinbot();
  });
});