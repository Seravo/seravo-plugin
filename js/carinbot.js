'use strict';

jQuery(document).ready(function($) {
  console.log("js");
  function launch_carinbot() {
    console.log("launch")
    jQuery.post(
      seravo_carinbot_loc.ajaxurl, {
        'action': 'seravo_carinbot',
        'nonce': seravo_carinbot_loc.ajax_nonce,
      },
      function () {
        jQuery('#carinbot_loading').fadeOut();
        jQuery('#carinbot').append("Carin Bot activated.");

        //redirect -> sama url + ?carin_bot=true
        
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

/*
<script src='//helpy.io/js/helpybot.js'></script>
<script>
var Helpy = Helpy || {};
Helpy.domain = '//help.seravo.com';

Helpy.botIcon = '';
Helpy.botBackground = '#f0b40e';

// Use the following attributes to identify the user in your app/store
// Unidentified users will appear as anonymous users
Helpy.email_address = '';
Helpy.customer_name = '';

$script(['//helpy.io/js/bot.v5.js'], function() {
 Helpy.initBot('d02VpXBADWPR31G8kQMjK8waxEvO4r');
});</script>
*/