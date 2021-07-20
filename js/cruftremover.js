'use strict';
// The whole section containing found cruft, button etc.
const cruft_section = '.cruft-area';
// The cruft table
const cruft_table = 'cruft-entries-table';
// Table tbody part containing cruft entries
const cruft_entries = '.cruft-entries';
// Status bar for viewing results / errors
const status_bar = '.cruft-remove-status';

jQuery(document).ready(
  function($) {
    jQuery('[data-section="cruftfiles"]').on('seravoAjaxSuccess', cruft_files);
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
 * @param {object} section Postbox section.
 */
function remove_cruft_entries(remove_list, section, removed_message = '' ) {
  var table = section.find(cruft_entries);
  var cruft_area = section.find(cruft_section);

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
            cruft_area.find('.less-than').hide(400);
          }
          if ( table.children().length == 0 ) {
            section.find(status_bar).html('<b>' + removed_message + '</b>');
            cruft_area.children().remove();
            cruft_area.hide();
          }
        }
      );
    }
  );
}

/**
 * Build and initialize cruft files functionality.
 * @param {event} event Event that triggered the function call. Default seravoAjaxSuccess.
 * @param {array} response Array containing possible cruft files found.
 */
function cruft_files(event, response) {
  var section = jQuery('#seravo-postbox-cruftfiles');
  var cruft_view_section = jQuery(section).find(cruft_section);
  var tbody = jQuery(section).find(cruft_entries);
  var status_section = jQuery(section).find(status_bar);

  // generate the table etc. when there's cruft found
  var filecount = 0;

  response.data.forEach(
    function (file) {
      if (file.filename != '') {
        filecount++;
        //This row needs to print the filesizes
        tbody.append(
          '<tr class="cruft"><td class="cruft-delete"><input data-file-name="' + file.filename + '" class="cruft-check" type="checkbox"></td><td class="cruft-path">'
          + file.filename + '</td><td>' + file.mod_date + '</td><td style="float:right;">' + file.size + 'B</td></tr>'
        );
      }
    }
  );

  if ( filecount == 0 ) {
    status_section.append('<b>' + cruftremover_l10n.no_cruftfiles + '</b>');
  } else {
    cruft_view_section.show();
    tbody.parents(':eq(0)').prepend('<thead><tr><td><input class="cruft-select-all" type="checkbox" ></td><td class="cruft-tool-selector"><b>' + cruftremover_l10n.select_all + '</b></td><td class="cruft-tool-selector">' + cruftremover_l10n.mod_date + '</td><td class="cruft-tool-selector">' + cruftremover_l10n.filesize + '</td></tr></thead>');
    tbody.parents(':eq(0)').append('<tfoot class="less-than"><tr><td><input class="cruft-select-all" type="checkbox" ></td><td class="cruft-tool-selector"><b>' + cruftremover_l10n.select_all + '</b></td><td class="cruft-tool-selector">' + cruftremover_l10n.mod_date + '</td><td class="cruft-tool-selector">' + cruftremover_l10n.filesize + '</td></tr></tfoot>');
    cruft_view_section.append('<br><button class="button-primary" type="button">' + cruftremover_l10n.delete + '</b>');
    if ( filecount < 30 ) {
      section.find('.less-than').hide();
    }
    // Init checkbox functionality
    init_cruft_selects(section);

    section.find('.button-primary').click(
      function () {
        tb_show(cruftremover_l10n.confirmation_title, '#TB_inline?width=600&height=120&inlineId=remove-cruft-files-modal');
      }
    );

    jQuery('#remove-cruft-files-modal-proceed').click(
      function() {
        on_confirm();
        tb_remove();
      }
    );

    jQuery('#remove-cruft-files-modal-cancel').click(
      function() {
        tb_remove();
      }
    );
  }

  function on_confirm() {
    var cruft_list = new Array();
    var remove_rows = new Array();
    section.find('.cruft-check').each(
      function () {
        if (jQuery(this).is(":checked")) {
          remove_rows.push(jQuery(this).parents(':eq(1)'));
          cruft_list.push(jQuery(this).attr('data-file-name'));
        }
      }
    );

    if (cruft_list.length > 0) {
      var data = {
        deletefile: cruft_list,
      }
      seravo_ajax_request('post', 'cruftfiles', 'remove-cruft-files', on_success, on_error, data);
      remove_cruft_entries(remove_rows, section, cruftremover_l10n.no_cruftfiles);
    }
  }

  function on_success(response) {
    status_section.html('');

    if ( response.data.length !== 0 ) {
      status_section.append('<b>' + cruftremover_l10n.failed_to_remove + '</b><br>');

      for ( var failure in response.data ) {
        status_section.append('<p>' + response.data[failure] + '</p>');
      }
    }
  }

  function on_error(error) {
    status_section.html('<b>' + error + '</b>');
  }
}
