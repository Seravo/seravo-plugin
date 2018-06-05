'use strict';

jQuery(document).ready(function($) {
  function seravo_ajax_delete_file(filepath, callback) {
    $.post(
      ajaxurl,
      { type: 'POST',
        'action': 'seravo_delete_file',
        'deletefile': filepath },
      function( rawData ) {
        var data = JSON.parse(rawData);
        data.forEach(function( fileinfo ) {
          if ( fileinfo.success ) {
            callback();
          }
        });
      });
  }

  // Generic ajax report loader function
  function seravo_load_report(section) {
    $.post(
      ajaxurl,
      { 'action': 'seravo_cruftfiles',
        'section': section },
      function(rawData) {
        if (rawData.length == 0) {
          $('#' + section).html(seravo_cruftfiles_loc.no_data);
        }
        $('#' + section + '_loading').fadeOut();
        var data = JSON.parse(rawData);
        var filecount = 0;
        $.each( data, function( i, file){
          if (file != '') {
            filecount++;
            $( '#cruftfiles_entries' ).append('<tr class="cruftfile"><td class="cruftfile-delete"><a href="" class="dashicons dashicons-trash cruftfile-delete-button"></td><td class="cruftfile-path">'
                                                + file + '</td></tr>');
          }
        });
        if (filecount == 0) {
          $( '#cruftfiles_status' ).append('<b>' + seravo_cruftfiles_loc.no_cruftfiles + '</b>');
        }
        $( '#cruftfiles_status_loading img' ).fadeOut
        $('.cruftfile-delete-button').click(function(event) {
          event.preventDefault();
          var is_user_sure = confirm(seravo_cruftfiles_loc.confirm);
          if ( ! is_user_sure) {
            return;
          }
          var parent_row = $(this).parents(':eq(1)');
          var filepath = parent_row.find('.cruftfile-path').html();

          seravo_ajax_delete_file(filepath, function() {
            parent_row.animate({
              opacity: 0
            }, 600, function() {
              parent_row.remove();
            });
          });
        });

      }
    ).fail(function() {
      $('#' + section + '_loading').html(seravo_cruftfiles_loc.fail);
    });
  }

  // Load on page load
  seravo_load_report('cruftfiles_status');
});
