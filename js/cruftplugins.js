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
        data.forEach(plugininfo => {
          if ( data[0].indexOf('Deleted') != -1 ) {
            callback();
          } else {
            confirm(seravo_cruftplugins_loc.failure);
          }
        });
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
        var data = JSON.parse(JSON.parse(rawData));
        //https://help.seravo.com/en/knowledgebase/19-themes-and-plugins/docs/51-wordpress-plugins-in-seravo-com

        var plugins_list = {
          cache_plugins: {
            name: 'cache_plugins',
            content: [
            'W3 Total Cache',
            'WP Super Cache',
            'WP File Cache',
            ]},
          security_plugins: {
            name: 'security_plugins',
            content: [
            'Better WP Security',
            'iThemes Security',
            'WordFence',
            'Limit login attempts',
            'Login wall',
            'WordFence',
            ]},
          db_plugins: {
            name: 'db_plugins',
            content: [
            'Broken Link Checker',
            'MyReviewPlugin',
            'LinkMan',
            'Fuzzy SEO Booster',
            'WP PostViews',
            'Tweet Blender',
            ]},
          backup_plugins: {
            name: 'backup_plugins',
            content: [
            'Backup Guard',
            'Backup Scheduler',
            'Backup WordPress',
            'BackWPup Free',
            'BlogVault',
            ]},
          poor_security: {
            name: 'poor_security',
            content: [
            'PHPMyAdmin',
            'Adminer',
            'File Commander',
            'Sweet Captcha',
            'Upladify',
            ]},
        }
        var plugincount = 0;
        var titles = new Array();
        $.each( data, function( i, plugin){
          if (plugin.name != '') {
            plugincount++;
            titles = seravo_print_plugin_table( titles, plugin, plugins_list );
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
          if ( ! is_user_sure) {
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
              if( container.children().length == 0 ){
                container.parents(':eq(1)').remove();
              }
            });
          });
        });

      }
    ).fail(function() {
      //$('#' + section + '_loading').html(seravo_cruftplugins_loc.fail);
    });
  }

  function seravo_print_plugin_table( titles, plugin, plugins_list ){
    var inactive = true;
    var title = '';
    for (var category in plugins_list) {
      if (plugins_list.hasOwnProperty(category)) {
        category = plugins_list[category];

        if (category.content.indexOf(plugin.title) != -1) {
          $( '#cruftplugins_status').append('<div class="cruft-plugin-set"><table><tbody class="cruft-plugin-table" id="cruftplugins_' + category.name + '"></tbody></table></div>');
          if (category.name == 'cache_plugins' && titles.indexOf(category.name) == -1) {
            $( '#cruftplugins_' + category.name ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.cache_plugins + '</b><p>' + seravo_cruftplugins_loc.cache_plugins_desc + '</p>');
          } else if (category.name == 'security_plugins' && titles.indexOf(category.name) == -1) {
            $( '#cruftplugins_' + category.name ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.security_plugins + '</b><p>' + seravo_cruftplugins_loc.security_plugins_desc + '</p>');
          } else if (category.name == 'db_plugins' && titles.indexOf(category.name) == -1) {
            $( '#cruftplugins_' + category.name ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.db_plugins + '</b><p>' + seravo_cruftplugins_loc.db_plugins_desc + '</p>');
          } else if (category.name == 'backup_plugins' && titles.indexOf(category.name) == -1) {
            $( '#cruftplugins_' + category.name ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.backup_plugins + '</b><p>' + seravo_cruftplugins_loc.backup_plugins_desc + '</p>');
          } else if (category.name == 'poor_security' && titles.indexOf(category.name) == -1) {
            $( '#cruftplugins_' + category.name ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.poor_security + '</b><p>' + seravo_cruftplugins_loc.poor_security_desc + '</p>');
          }
          inactive = false;
          var title = category.name;
          break;
        }
      }
    }
    if ( inactive && plugin.status == 'inactive' ) {
      if ( titles.indexOf('inactive') == -1 ) {
        $( '#cruftplugins_status').append('<div class="cruft-plugin-set"><table><tbody class="cruft-plugin-table" id="cruftplugins_inactive"></tbody></table></div>');
        $( '#cruftplugins_inactive' ).parents().eq(1).prepend('<b>' + seravo_cruftplugins_loc.inactive + '</b><p>' + seravo_cruftplugins_loc.inactive_desc + '</p>');
      }
      title = 'inactive';
      inactive = false;
    }
    if ( plugin.title == '' ) {
      plugin.title = plugin.name;
    }
    if ( ! inactive ) {
      $( '#cruftplugins_' + title ).append('<tr class="cruftplugin" data-plugin-name= "' + plugin.name + '"><td class="cruftplugin-delete">' +
      '<a href="" class="dashicons dashicons-trash cruftplugin-delete-button" >' +
      '</td><td class="cruftplugin-path">' + plugin.title + '</td></tr>');
      titles.push(title);
    }
    return titles;
  }

  // Load on page load
  seravo_plugin_load_report('cruftplugins_status');
});
