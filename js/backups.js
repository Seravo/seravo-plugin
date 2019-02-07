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
        jQuery('#' + section).append(data.join("\n"));
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

  // Load when clicked
  jQuery('#download_backup').click(function () {

    var increment = $('#backup_increment').val();

    jQuery.get(
        seravo_backups_loc.ajaxurl, {
          'action': 'seravo_backup_download',
          'increment': increment,
          'nonce': seravo_backups_loc.ajax_nonce,
        },
        function (response) {
          alert("noted");
          jQuery('#backup_downloading img').show();
          console.log(response);
        }
      )
      .done(function(response) {
        alert("done");
        console.log(response);/*
        var file_path = response[0];
        var a = document.createElement('A');
        a.href = file_path;
        a.download = file_path.substr(file_path.lastIndexOf('/') + 1);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        */
      })
      .fail(function() {
        alert("failed");
      })
      .always(function() {
        alert("finished");
        jQuery('#backup_downloading img').hide();
      });
  });

});
