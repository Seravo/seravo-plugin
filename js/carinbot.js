'use strict';

jQuery(document).ready(function($) {
  function seravo_load_report(section) {
    jQuery.post(
      seravo_carinbot_loc.ajaxurl, {
        'action': 'seravo_carinbot',
        'section': section,
        'nonce': seravo_carinbot_loc.ajax_nonce,
      },
      function (rawData) {
        if (rawData.length == 0) {
          jQuery('#' + section).html('No data returned for section.');
        }

        jQuery('#' + section + '_loading').fadeOut();
        var data = JSON.parse(rawData);
        jQuery('#' + section).append(data.join("\n"));
      }
    ).fail(function () {
      jQuery('#' + section + '_loading').html('Failed to load. Please try again.');
    });
  }

  // Load when clicked
  jQuery('#carinbot_button').click(function () {
    jQuery('#carinbot_loading img').show();
    jQuery('#carinbot_button').hide();
    seravo_load_report('carinbot');
  });
});