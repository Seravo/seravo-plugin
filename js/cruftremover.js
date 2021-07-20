'use strict';
// The whole section containing found cruft, button etc.
const cruft_section = '.cruft-area';
// Table tbody part containing cruft entries
const cruft_entries = '.cruft-entries';
// Status bar for viewing results / errors
const status_bar = '.cruft-remove-status';

jQuery(document).ready(
  function($) {
    jQuery('[data-section="cruftfiles"]').on('seravoAjaxSuccess', init_cruft_section);
    jQuery('[data-section="cruftplugins"]').on('seravoAjaxSuccess', init_cruft_section);
  }
);

/**
 * Initialize select events for cruft-select elements.
 * @param {object} section Section to init for.
 */
function init_cruft_selects(section) {
  section.find('.cruft-select-all').click(
    function (event) {
      if (this.checked) {
        // Iterate each checkbox
        section.find('.cruft-check, .cruft-select-all').each(
          function () {
            this.checked = true;
          }
        );
      } else {
        section.find('.cruft-check, .cruft-select-all').each(
          function () {
            this.checked = false;
          }
        );
      }
    }
  );

  section.find('.cruft-check').click(
    function () {
      if (section.find('.cruft-check:checked').length == section.find('.cruft-check').length) {
        section.find('.cruft-select-all').each(
          function () {
            this.checked = true;
          }
        );
      } else {
        section.find('.cruft-select-all').each(
          function () {
            this.checked = false;
          }
        );
      }
    }
  );
}

/**
 * Remove the given cruft entries on a section with animation.
 * @param {array} remove_list List of the content to remove.
 * @param {object} table Table part containing cruft entries.
 * @param {object} cruft_area The section containing cruft table and entries.
 * @param {bool} hide_area Control whether the selected area should be hidden when there are no entries left.
 */
function remove_cruft_entries(remove_list, table, cruft_area, hide_area) {
  remove_list.forEach(
    function(row) {
      row.animate(
        {
        opacity: 0
        },
        600,
        function() {
          row.remove();

          if ( table.children().length < 30 ) {
            jQuery(cruft_area).find('.less-than').hide(400);
          }

          if ( table.children().length == 0 ) {
            if (hide_area) {
              jQuery(cruft_area).children().remove();
              jQuery(cruft_area).hide();
            } else {
              jQuery(cruft_area).remove();
            }
          }
        }
      );
    }
  );
}

/**
 * Build the given cruft section based on the data.
 * @param {object} build_data Build data to use on build.
 */
function build_cruft_section(build_data) {
  if (build_data.type === 'cruftfiles') {
    // build cruftfiles
    // generate the table etc. when there's cruft found
    var filecount = 0;
    build_data.rawdata.forEach(
      function (file) {
        if (file.filename != '') {
          filecount++;
          //This row needs to print the filesizes
          build_data.tbody.append(
            '<tr class="cruft"><td class="cruft-delete"><input data-file-name="' + file.filename + '" class="cruft-check" type="checkbox"></td><td class="cruft-path">'
            + file.filename + '</td><td>' + file.mod_date + '</td><td style="float:right;">' + file.size + 'B</td></tr>'
          );
        }
      }
    );

    if (filecount == 0) {
      build_data.status_section.append('<b>' + cruftremover_l10n.no_cruftfiles + '</b>');
    } else {
      build_data.cruft_view_section.show();
      build_data.tbody.parents(':eq(0)').prepend('<thead><tr><td><input class="cruft-select-all" type="checkbox" ></td><td class="cruft-tool-selector"><b>' + cruftremover_l10n.select_all + '</b></td><td class="cruft-tool-selector">' + cruftremover_l10n.mod_date + '</td><td class="cruft-tool-selector">' + cruftremover_l10n.filesize + '</td></tr></thead>');
      build_data.tbody.parents(':eq(0)').append('<tfoot class="less-than"><tr><td><input class="cruft-select-all" type="checkbox" ></td><td class="cruft-tool-selector"><b>' + cruftremover_l10n.select_all + '</b></td><td class="cruft-tool-selector">' + cruftremover_l10n.mod_date + '</td><td class="cruft-tool-selector">' + cruftremover_l10n.filesize + '</td></tr></tfoot>');
      build_data.cruft_view_section.append('<br><button class="button-primary" type="button">' + cruftremover_l10n.delete + '</b>');
      if (filecount < 30) {
        build_data.section.find('.less-than').hide();
      }
      // Init checkbox functionality
      init_cruft_selects(build_data.section);
    }

  } else if (build_data.type === 'cruftplugins') {
    if (Object.keys(build_data.rawdata).length == 0) {
      build_data.status_section.html('<b>' + cruftremover_l10n.no_cruftplugins + '</b>');
    }

    jQuery.each(
      Object.keys(build_data.rawdata),
      function(index, status) {
        build_data.cruft_view_section.append(
          '<div class="cruft-entry-area"><p><b>' + cruftremover_l10n[status] + '</b></p><p>' + cruftremover_l10n[status + '_desc'] + '</p>' +
          '<table id="cruftplugins_' + status + '">' +
          '<thead>' +
          '<tr>' +
          '<td><input class="cruftplugin-select-all_' + status + '" type="checkbox"></td>' +
          '<td class="cruft-tool-selector cruft-plugin-td"><b>' + cruftremover_l10n.select_all + '</b></td>' +
          '</tr>' +
          '</thead>' +
          '<tbody class="cruftplugins_entries_' + status + '">' +
          '</tbody>' +
          '<tfoot class="cruftplugins_less-than_' + status + '" style="display: none;">' +
          '<tr>' +
          '<td><input class="cruftplugin-select-all_' + status + '" type="checkbox"></td>' +
          '<td class="cruft-tool-selector cruft-plugin-td"><b>' + cruftremover_l10n.select_all + '</b></td>' +
          '</tr>' +
          '</tfoot>' +
          '</table>' +
          '<br><button class="button-primary" data-status="' + status + '" type="button">' + cruftremover_l10n.delete + '</button>' +
          '<div id="cruftplugins_status_' + status + '"></div><br><hr></div>'
        );

        var entries = jQuery('#cruftplugins_' + status + ' .cruftplugins_entries_' + status);
        jQuery.each(
          build_data.rawdata[status],
          function (index) {
            var plugin = build_data.rawdata[status][index];
            var html = '<tr class="cruftplugin">' +
            '<td class="cruftplugin-delete"><input data-plugin-name="' + plugin + '" class="cruftplugin-check_' + status + '" type="checkbox"></td>' +
            '<td class="cruftplugin-path">' + plugin + '</td>' +
            '</tr>';
            entries.append(html);
          }
        );

        if (jQuery('#cruftplugins_' + status).find('.cruftplugins_entries_' + status).children().length >= 30) {
          jQuery('#cruftplugins_' + status).find('.cruftplugins_less-than').show(400);
        }
        // Add checkbox click events
        jQuery('.cruftplugin-select-all_' + status).click(
          function (event) {
            if (this.checked) {
              // Iterate each checkbox
              jQuery('.cruftplugin-check_' + status + ', .cruftplugin-select-all_' + status).each(
                function () {
                  this.checked = true;
                }
              );
            } else {
              jQuery('.cruftplugin-check_' + status + ', .cruftplugin-select-all_' + status).each(
                function () {
                  this.checked = false;
                }
              );
            }
          }
        );
        jQuery('.cruftplugin-check_' + status).click(
          function () {
            if (jQuery('.cruftplugin-check_' + status + ':checked').length == jQuery('.cruftplugin-check_' + status).length) {
              jQuery('.cruftplugin-select-all_' + status).each(
                function () {
                  this.checked = true;
                }
              );
            } else {
              jQuery('.cruftplugin-select-all_' + status).each(
                function () {
                  this.checked = false;
                }
              );
            }
          }
        );
      }
    );

  } else {
    // nothing to build because the section is not defined
    return;
  }
}

/**
 * Build and initialize cruft functionality.
 * @param {event} event Event that triggered the function call. Default seravoAjaxSuccess.
 * @param {array} response Array containing possible cruft entries found.
 */
function init_cruft_section(event, response) {
  var section;
  var tbody;
  var ajax_section;
  var data = {
    cruft_type: response.section,
  };
  var loc = {};

  if (response.section === 'cruftfiles') {
    section = jQuery('#seravo-postbox-cruftfiles');
    tbody = jQuery(section).find(cruft_entries);
    ajax_section = 'remove-cruft-files';
    // init loc messages
    loc.failed = cruftremover_l10n.failed_to_remove;
    loc.no_cruft = cruftremover_l10n.no_cruftfiles;

  } else if (response.section === 'cruftplugins') {
    section = jQuery('#seravo-postbox-cruftplugins');
    ajax_section = 'remove-cruft-plugins';
    // init loc messages
    loc.failed = cruftremover_l10n.plugins_remove_failed;
    loc.no_cruft = cruftremover_l10n.no_cruftplugins;

  } else {
    return;
  }

  var cruft_view_section = section.find(cruft_section);
  var status_section = section.find(status_bar);

  var build_data = {
    type: response.section,
    rawdata: response.data,
    tbody: tbody,
    status_section: status_section,
    cruft_view_section: cruft_view_section,
    section: section,
  }

  build_cruft_section(build_data);

  var modal = '#' + ajax_section + '-modal';
  var proceed_button = modal + '-proceed';
  var cancel_button = modal + '-cancel';
  // Common elements
  var status;
  var body;
  var table;
  var cruft_area;
  var hide_area = true;
  var cruft_list = new Array();
  var remove_rows = new Array();

  section.find('.button-primary').click(
    function () {
      status = jQuery(this).data('status');
      body = jQuery('#cruftplugins_' + status);
      tb_show(cruftremover_l10n.confirmation_title, '#TB_inline?width=600&height=120&inlineId=' + ajax_section + '-modal');
    }
  );

  jQuery(proceed_button).click(
    function() {
      on_confirm();
      tb_remove();
    }
  );

  jQuery(cancel_button).click(
    function() {
      tb_remove();
    }
  );

  function on_confirm() {

    if (response.section === 'cruftfiles') {
      table = tbody;
      cruft_area = cruft_view_section;

      section.find('.cruft-check').each(
        function () {
          if (jQuery(this).is(":checked")) {
            remove_rows.push(jQuery(this).parents(':eq(1)'));
            cruft_list.push(jQuery(this).attr('data-file-name'));
          }
        }
      );

    } else if (response.section === 'cruftplugins') {
      table = body.find('.cruftplugins_entries_' + status);
      cruft_area = body.parent();
      hide_area = false;

      // Get selected checkboxes
      body.find('.cruftplugin-check_' + status).each(
        function () {
          var selected = jQuery(this);
          if (selected.is(":checked")) {
            remove_rows.push(selected.parents(':eq(1)'));
            cruft_list.push(selected.attr('data-plugin-name'));
          }
        }
      );
    }

    if (cruft_list.length > 0) {
      data.cruft = cruft_list;
      seravo_ajax_request('post', response.section, ajax_section, on_success, on_error, data);
      remove_cruft_entries(remove_rows, table, cruft_area, hide_area);
    }
  }

  function on_success(response) {
    status_section.html('');
    if ( response.data.length !== 0 ) {
      status_section.append('<b>' + loc.failed + '</b><br>');

      for ( var failure in response.data ) {
        status_section.append('<p>' + response.data[failure] + '</p>');
      }
    }

    if (cruft_view_section.children().length == 0) {
      status_section.html('<b>' + loc.no_cruft + '</b>');
    }

  }

  function on_error(error) {
    status_section.html('<b>' + error + '</b>');
  }
}
