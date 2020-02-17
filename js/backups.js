// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function($) {
  // Reports load
  // Generic ajax report loader function
  function seravo_load_report(section) {
    jQuery.post(
      seravo_backups_loc.ajaxurl, {
        'action': 'seravo_backups',
        'section': section,
        'nonce': seravo_backups_loc.ajax_nonce,
      },
      function (rawData) {
        if (rawData.length == 0) {
          jQuery('#' + section).html('No data returned for section.');
        }

        jQuery('#' + section + '_loading').fadeOut();
        var data = JSON.parse(rawData);
        if ( data.length > 0 ) {
          jQuery('#' + section).append(data.join("\n"));
        } else {
          jQuery('#' + section).append('<p>' + seravo_backups_loc.no_entries + '</p>');
        }
      }
    ).fail(function () {
      jQuery('#' + section + '_loading').html('Failed to load. Please try again.');
    });
  }

  // Load on page load
  seravo_load_report('backup_status');
  seravo_load_report('backup_exclude');

  // Load when clicked
  jQuery('#create_backup_button').click(function () {
    jQuery('#create_backup_loading img').show();
    jQuery('#create_backup_button').hide();
    seravo_load_report('create_backup');
  });
});
