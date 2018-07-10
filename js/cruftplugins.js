'use strict';




jQuery(document).ready(function($) {
  function seravo_ajax_delete_plugin(plugin_name, callback) {
    $.post(
      ajaxurl,
      { type: 'POST',
        'action': 'seravo_remove_plugins',
        'removeplugin': plugin_name },
      function( rawData ) {
        var data = JSON.parse(rawData);
        if ( data[ data.length - 1 ].indexOf('Success: Deleted 1 of 1 plugins.') != -1 ) {
          callback();
        } else {
          confirm(seravo_cruftplugins_loc.failure);
        }
      });
  }

  // Generic ajax report loader function
  function seravo_plugin_load_report(section) {
    $.post(
      ajaxurl,
      { 'action': 'seravo_list_cruft_plugins',
        'section': section },
      function(rawData) {
        if (rawData.length == 0) {
          $('#' + section).html(seravo_cruftplugins_loc.no_data);
        }
        $('#' + section + '_loading').fadeOut();
        var data = JSON.parse(rawData);
        // title, name, status
        var titles = new Array();
        $.each( data, function( i, plugin ){
          if (plugin.name != '') {
            titles = seravo_print_plugin_table( titles, plugin );
          }
        });
        if ( titles.length != 0 ) {
          $( '#cruftplugins_status' ).prepend('<p>' + seravo_cruftplugins_loc.cruftplugins + '</p>');
        } else {
          $( '#cruftplugins_status' ).prepend('<b>' + seravo_cruftplugins_loc.no_cruftplugins + '</b>');
        }
        $( '#cruftplugins_status_loading img' ).fadeOut
        $('.cruftplugin-delete-button').click(function(event) {
          event.preventDefault();
          var is_user_sure = confirm(seravo_cruftplugins_loc.confirm);
          if ( ! is_user_sure ) {
            return;
          }
          var parent_row = $(this).parents(':eq(1)');
          var plugin_name = parent_row.attr('data-plugin-name');
          seravo_ajax_delete_plugin(plugin_name, function() {
            parent_row.animate({
              opacity: 0
            }, 600, function() {
              var container = parent_row.parents().eq(0);
              parent_row.remove();
              // purge empty lists
              if ( container.children().length == 0 ) {
                container.parents(':eq(1)').remove();
              }
            });
          });
        });

      }
    ).fail(function() {
      $('#' + section + '_loading').html(seravo_cruftplugins_loc.fail);
    });
  }
  // titles for the titles that have been printed. plugin for the one which is to be printed.
  function seravo_print_plugin_table( titles, plugin) {
    if (titles.indexOf(plugin.status) == -1) {
      $( '#cruftplugins_status').append('<div class="cruft-plugin-set"><table><tbody class="cruft-plugin-table" id="cruftplugins_' + plugin.status + '"></tbody></table></div>');
    }
    if (plugin.status == 'cache_plugins' && titles.indexOf(plugin.status) == -1) {
      $( '#cruftplugins_' + plugin.status ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.cache_plugins + '</b><p>' + seravo_cruftplugins_loc.cache_plugins_desc + '</p>');
    } else if (plugin.status == 'security_plugins' && titles.indexOf(plugin.status) == -1) {
      $( '#cruftplugins_' + plugin.status ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.security_plugins + '</b><p>' + seravo_cruftplugins_loc.security_plugins_desc + '</p>');
    } else if (plugin.status == 'db_plugins' && titles.indexOf(plugin.status) == -1) {
      $( '#cruftplugins_' + plugin.status ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.db_plugins + '</b><p>' + seravo_cruftplugins_loc.db_plugins_desc + '</p>');
    } else if (plugin.status == 'backup_plugins' && titles.indexOf(plugin.status) == -1) {
      $( '#cruftplugins_' + plugin.status ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.backup_plugins + '</b><p>' + seravo_cruftplugins_loc.backup_plugins_desc + '</p>');
    } else if (plugin.status == 'poor_security' && titles.indexOf(plugin.status) == -1) {
      $( '#cruftplugins_' + plugin.status ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.poor_security + '</b><p>' + seravo_cruftplugins_loc.poor_security_desc + '</p>');
    } else if (plugin.status == 'inactive' && titles.indexOf(plugin.status) == -1) {
      $( '#cruftplugins_' + plugin.status ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.inactive + '</b><p>' + seravo_cruftplugins_loc.inactive_desc + '</p>');
    }
    titles.push(plugin.status);
    $( '#cruftplugins_' + plugin.status ).append('<tr class="cruftplugin" data-plugin-name= "' + plugin.name + '"><td class="cruftplugin-delete">' +
      '<a href="" class="dashicons dashicons-trash cruftplugin-delete-button" >' +
      '</td><td class="cruftplugin-path">' + plugin.title + '</td></tr>');

    return titles;
  }

  // Load on page load
  seravo_plugin_load_report('cruftplugins_status');
});
