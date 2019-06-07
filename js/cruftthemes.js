'use strict';

jQuery(document).ready(function ($) {
  var section = 'cruftthemes_status';
  var $body = $('#' + section);
  // Entries variable fill be filled when table is appended
  var $entries;
  function appendTable() {
    // If table is appended return entries
    if ($entries) {
      return $entries;
    }
    var html =
      '<table id="' + section + '">' +
      '<thead>' +
      '<tr>' +
      '<td><input class="' + section + '_select-all" type="checkbox"></td>' +
      '<td class="cruft-tool-selector cruft-themes-td"><b>Select all files</b></td>' +
      '</tr>' +
      '</thead>' +
      '<tbody class="' + section + '_entries">' +
      '</tbody>' +
      '<tfoot class="' + section + '_less-than" style="display: none;">' +
      '<tr>' +
      '<td><input class="' + section + '_select-all" type="checkbox"></td>' +
      '<td class="cruft-tool-selector"><b>Select all files</b></td>' +
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
    html += '<td class="crufttheme-path">' + theme.name + '</td>' +
    '</tr>';
    $element.append(html)
    // Apply childs
    if (theme.childs) {
      theme.childs.forEach(function (child) {
        if ( ! child.active ) {
          appendLine($element, child, true)
        }
      })
      $element.append('<tr><td style="padding: 5px 0 0 0;"></td></tr>');
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
      $('.crufttheme-delete .crufttheme-check:checked').each(function (key, obj) {
        $.post(seravo_cruftthemes_loc.ajaxurl, {
          type: 'POST',
          action: 'seravo_remove_themes',
          removetheme: obj.dataset.pluginName,
          nonce: seravo_cruftthemes_loc.ajax_nonce
        }, function( rawData ) {
          var data = JSON.parse(rawData);
          if ( data ) {
            $('table#' + section).remove();
            $('.' + section + '_delete').remove();
            // Clear entries
            $entries = null;
            getData();
          } else {
            confirm(seravo_cruftthemes_loc.failure);
          }
        });
      })
      // Create triggers
      createOnClick();
    })
    // Select all checkbox
    var $selectAll = $('.cruftthemes_status_select-all');
    $selectAll.click(function () {
      var $checkboxes = $('.crufttheme[data-active="false"] .crufttheme-check');
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
      if (data.length == 1) {
        $('#' + section).html('<b>' + seravo_cruftthemes_loc.no_cruftthemes + '</b>');
        return
      }
      // Create table
      appendTable();
      // Create parsed array where we can create table from
      var parsedArray = [];
      data.forEach(function (theme) {
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
      // Create lines
      Object.keys(parsedArray).forEach(function (theme) {
        appendLine($entries, parsedArray[theme])
      })
      // Create delete function
      createOnClick();
    }).fail(function() {
      $('#' + section + '_loading').html(seravo_cruftthemes_loc.fail);
    });
  }
  getData();
});
