/**
 * @file JS for the cruft files, cruft plugins and cruft themes postboxes.
 */

/* globals seravo_ajax_request, seravo, cruftremover_l10n */

'use strict';

if (! window.Seravo) {
  window.Seravo = {};
}
const { Seravo } = window;

Seravo.Cruftbox = function(section, type) {

  this.section = section;
  this.type = type;
  this.postbox = null;
  this.cruftArea = null;
  this.dataFields = null;

  this.init = function() {
    this.postbox = this.section.closest('.seravo-postbox').data('postbox-id');
    this.cruftArea = this.section.find('.cruft-area');

    if (this.type === 'files') {
      this.dataFields = [ 'filename' ];
    }

    // Attach cruftbox to seravoAjaxSuccess event
    this.section.find('.seravo-ajax-lazy-load').on('seravoAjaxSuccess', (event, response) => {
      this.build(response.data);
    });
  };

  this.initCruftArea = function() {
    const categories = this.cruftArea.find('.category');

    // Init select all checkbox
    categories.find('.cruft-select-all').click((event) => {
      jQuery(event.target).closest('table').find('input[type=checkbox]').prop('checked', event.target.checked);
    });

    // Init delete button
    categories.find('.cruft-delete').click((event) => {
      const cruft = [];
      const category = jQuery(event.target).closest('.category');
      category.find('.cruft-checkbox:checked').each((_i, checkbox) => {
        cruft.push(jQuery(checkbox).val());
      });

      seravo.confirm_modal(this, cruftremover_l10n.confirm, `remove-cruft-${ this.type }`, () => {
        this.removeCruft(category, cruft);
      });
    });
  };

  this.build = function(data) {
    let cruftFound = false;
    const cruftArea = jQuery('<div></div>');

    data.forEach((category) => {
      if (category.cruft.length === 0) {
        return;
      }

      const cruftCategory = jQuery('<div class="category"></div>');

      // Add title
      if (category.title.length !== 0) {
        cruftCategory.append(jQuery(`<p><b>${ category.title }</b></p>`));
      }

      // Add description
      if (category.description.length !== 0) {
        cruftCategory.append(jQuery(`<p>${ category.description }</p>`));
      }

      let fieldCount = 1;
      if (this.dataFields !== null) {
        fieldCount = this.dataFields.length;
      }

      // Create table
      const table = jQuery('<table class="cruft-table"></table>');
      table.append(
        '<thead><tr>' +
        '<td class="cruft-checkbox-col"><input class="cruft-select-all" type="checkbox"></td>' +
        `<td class="cruft-title" colspan="${ fieldCount }"><b>${ cruftremover_l10n.select_all }</b></td>` +
        '</tr></thread>'
      );

      // Add table entries
      const entries = jQuery('<tbody class="cruft-entries"></tbody>');
      category.cruft.forEach((cruft) => {
        let entryRow = '<tr>';
        if (this.dataFields === null) {
          entryRow += `<td><input value="${ cruft }" class="cruft-checkbox" type="checkbox"></td><td class="seravo-tooltip" title="${ cruft }">${ cruft }</td>`;
        } else {
          entryRow += `<td><input value="${ cruft[this.dataFields[0]] }" class="cruft-checkbox" type="checkbox"></td>`;
          this.dataFields.forEach((field) => {
            entryRow += `<td class="seravo-tooltip" title="${ cruft[field] }">${ cruft[field] }</td>`;
          });
        }
        entryRow += '</tr>';
        entries.append(entryRow);
      });
      table.append(entries);
      cruftCategory.append(table);

      // Create delete button
      cruftCategory.append(`</br><button class="button-primary cruft-delete" type="button">${ cruftremover_l10n.delete }</button>`);
      // Create spinner
      cruftCategory.append(`<img class="cruft-spinner" src="/wp-admin/images/spinner.gif">`);
      // Create error area
      cruftCategory.append('<div class="cruft-error"></div><hr>');
      // Add the category to cruft area
      cruftArea.append(cruftCategory);

      cruftFound = true;
    });

    if (cruftFound === false) {
      this.cruftArea.html(`<b>${ cruftremover_l10n.no_cruft }</b>`);
      return;
    }

    // Show the new cruft area
    this.cruftArea.html('');
    this.cruftArea.append(cruftArea);

    this.initCruftArea();
  };

  this.removeCruft = function(category, cruft) {
    if (cruft.length === 0) {
      return;
    }

    const { cruftArea } = this;
    const errorField = category.find('.cruft-error');
    const deleteButton = category.find('.cruft-delete');
    const checkboxes = category.find('input[type=checkbox]');
    const spinner = category.find('.cruft-spinner');

    const onError = function(error) {
      errorField.html(`<b>${ error }</b>`);
      // Disable checkboxes and delete button on error
      checkboxes.prop('disabled', true);
      deleteButton.prop('disabled', true);
      spinner.hide();
    };

    const onSuccess = function(response) {
      // Some failed to remove
      if (response.data.length !== 0) {
        onError(cruftremover_l10n.failure);
        return;
      }

      // Delete the entries from the table
      cruft.forEach((entry) => {
        category.find(`input[value="${ entry }"]`).closest('tr').remove();
      });

      // Check if the category can be removed
      if (category.find('.cruft-checkbox').length === 0) {
        category.remove();
        // Check if there's no cruft left
        if (cruftArea.find('.category').length === 0) {
          cruftArea.html(`<b>${ cruftremover_l10n.no_cruft }</b>`);
        }
      } else {
        deleteButton.prop('disabled', false);
        spinner.hide();
      }
    };

    spinner.show();
    deleteButton.prop('disabled', true);
    seravo_ajax_request('POST', this.postbox, `remove-cruft-${ this.postbox }`, onSuccess, onError, { cruft });
  };

};

jQuery(document).ready(
  () => {
    jQuery('.seravo-cruft-section').each((_i, element) => {
      const section = jQuery(element);
      new Seravo.Cruftbox(section, section.data('cruft-type')).init();
    });
  }
);
