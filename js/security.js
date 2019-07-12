// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function($) {
  // Reports load
  // Generic ajax report loader function
  function seravo_load_report(section) {
    jQuery.post(
      seravo_security_loc.ajaxurl, {
        'action': 'seravo_security',
        'section': section,
        'nonce': seravo_security_loc.ajax_nonce,
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

  // Load on page load
  seravo_load_report('logins_info');

});
