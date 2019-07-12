// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function ($) {
  var section = 'cruftthemes_status';
  var $body = $('#' + section);
  // Entries variable fill be filled when table is appended
  var $entries;
  function appendTable() {
    $body.empty();
    // If table is appended return entries
    if ($entries) {
      return $entries;
    }
    var html =
      '<table id="' + section + '">' +
      '<thead>' +
      '<tr>' +
      '<td><input class="' + section + '_select-all" type="checkbox"></td>' +
      '<td class="cruft-tool-selector cruft-themes-td"><b>' + seravo_cruftfiles_loc.select_all + '</b></td>' +
      '</tr>' +
      '</thead>' +
      '<tbody class="' + section + '_entries">' +
      '</tbody>' +
      '<tfoot class="' + section + '_less-than" style="display: none;">' +
      '<tr>' +
      '<td><input class="' + section + '_select-all" type="checkbox"></td>' +
      '<td class="cruft-tool-selector"><b>' + seravo_cruftfiles_loc.select_all + '</b></td>' +
      '</tr>' +
      '</tfoot>' +
      '</table>' +
      '<button class="crufttheme-delete-button ' + section + '_delete" type="button">' + seravo_cruftfiles_loc.delete + '</button>';
    $body.append(html)
    $entries = $('.' + section + '_entries');
    return $entries;
  }

  function appendLine($element, theme, isChild) {
    var html =
    '<tr class="crufttheme" data-plugin-name="' + theme.name + '" data-active="' + ( theme.active && ! theme.childs ) + '" data-childs="' + (theme.childs ? 'true' : 'false') + '"  data-is-child="' + (isChild ? 'true' : 'false') + '">';
      html += '<td class="crufttheme-delete">' +
      '<input data-plugin-name="' + theme.name + '" class="crufttheme-check" type="checkbox">' +
      '</td>';
    html += '<td class="crufttheme-path">' + theme.name + ( theme.childs ? ' ' + seravo_cruftthemes_loc.isparentto : '') + '</td>' +
    '</tr>';
    $element.append(html)
    // Apply childs
    if (theme.childs) {
      theme.childs.forEach(function (child) {
        if ( ! child.active ) {
          appendLine($element, child, true)
        } else {
          $element.append('<tr><td></td><td>' + child.name + '</td></tr>');
        }
      })
      $element.append('<tr><td colspan="2"><hr></td></tr>');
    }
  }

  function createOnClick() {
    // Delete button
    $('.cruftthemes_status_delete').click(function (event) {
      event.preventDefault();
      var is_user_sure = confirm(seravo_cruftthemes_loc.confirm);
      if ( ! is_user_sure ) {
        return;
      }
      var remove_themes = [];
      $('.crufttheme-delete .crufttheme-check:checked').each(function (key, obj) {
        remove_themes.push( obj.dataset.pluginName );
      });
      if ( remove_themes.length ) {
        $.post(seravo_cruftthemes_loc.ajaxurl, {
          type: 'POST',
          action: 'seravo_remove_themes',
          removethemes: JSON.stringify(remove_themes),
          nonce: seravo_cruftthemes_loc.ajax_nonce
        }, function( rawData ) {
          if ( rawData ) {
            var data = JSON.parse(rawData);
            var errors = false;
            remove_themes.forEach(function(theme) {
              if ( data[theme] !== true ) {
                errors = true;
              }
            });
            // Tell if errors deleting themes
            if ( errors ) {
              $('#' + section).html('<b>' + seravo_cruftthemes_loc.failure + '</b>');
            } else {
              // Refresh the table
              getData();
            }
          } else {
            $('#' + section).html('<b>' + seravo_cruftfiles_loc.no_data + '</b>');
          }
        });
      }
    });
    // Select all checkbox
    var $selectAll = $('.cruftthemes_status_select-all');
    $selectAll.click(function () {
      var $checkboxes = $('.crufttheme[data-active="false"][data-childs="false"] .crufttheme-check');
      if ($selectAll.attr('checked')) {
        $checkboxes.prop( "checked", true );
      } else {
        $checkboxes.prop( "checked", false );
      }
    })
  }

  function getData()  {
    // Create by default
    $.post(seravo_cruftthemes_loc.ajaxurl, {
      'action': 'seravo_list_cruft_themes',
      'section': section,
      'nonce': seravo_cruftthemes_loc.ajax_nonce
    }, function(rawData) {
      var data = JSON.parse(rawData);
      $('#' + section + '_loading').fadeOut();
      // Remove old data
      $entries = null;
      // Create table
      appendTable();
      // Create parsed array where we can create table from
      var parsedArray = [];
      var allActive = true;
      data.forEach(function (theme) {
        allActive = allActive && theme.active;
        if ( ! theme.parent.length) {
          if (parsedArray[theme.name] == undefined) {
            parsedArray[theme.name] = {}
          }
          parsedArray[theme.name] = Object.assign({}, parsedArray[theme.name], theme);
        } else {
          // Create array if not yet created'
          if ( ! parsedArray[theme.parent]) {
            parsedArray[theme.parent] = {}
          }
          if ( ! $.isArray(parsedArray[theme.parent]['childs'])) {
            parsedArray[theme.parent]['childs'] = [];
          }
          parsedArray[theme.parent]['childs'].push(theme)
        }
      })
      if (allActive) {
        $('#' + section).html('<b>' + seravo_cruftthemes_loc.no_cruftthemes + '</b>');
      }
      // Create lines
      Object.keys(parsedArray).forEach(function (theme) {
        appendLine($entries, parsedArray[theme])
      })
      // Create delete function
      createOnClick();
    }).fail(function() {
      $('#' + section + '_loading').html(seravo_cruftfiles_loc.fail);
    });
  }
  // Create triggers
  createOnClick();
  // Populate table
  getData();
});
