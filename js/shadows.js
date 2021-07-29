'use strict';

jQuery(document).ready(
  function() {
    jQuery('.shadow-section').each(SeravoShadows.init_shadow_section);
  }
);

var SeravoShadows = {

  /**
   * Init shadow tbody sections.
   * @param {Integer} _index  Shadow section index.
   * @param {Object}  section The shadow section element.
   */
  init_shadow_section: function(_index, section) {
    var section = jQuery(section);
    // Init reset buttons
    section.find('.reset').click(
      function() {
        seravo_confirm(this, shadow_loc.confirm, 'remove-shadow-modal', SeravoShadows.reset_shadow);
      }
    );

    // Init result banner close buttons
    section.find('.alert').find('button').click(
      function() {
        SeravoShadows.toggle_banner(section);
      }
    );

    // Init folding info rows
    section.find('td.open-folded').click(SeravoShadows.toggle_info_arrow);
  },

  /**
   * Make a shadow reset request.
   * @param {Object} reset_button The reset button clicked.
   */
  reset_shadow: function(reset_button) {
    var section = jQuery(reset_button).closest('.shadow-section');
    var instance = jQuery(reset_button).closest('.shadow-instance');

    // Shadow details
    var id = instance.attr('id');
    var domain = instance.find('input[name=shadow-domain]').val();

    // Hide the previous result banner, disable reset buttons and show a spinner
    SeravoShadows.toggle_banner(section);
    section.find('.reset').prop('disabled', true);
    instance.find('.reset-status').html('<img src="/wp-admin/images/spinner.gif">');

    // Make the reset request
    seravo_ajax_request('POST', 'shadows', 'reset-shadows', on_success, on_error, { shadow: id });

    /**
     * Function on_success is called if the reset was a success.
     */
    function on_success() {
      // Show success banner, re-enable reset buttons and hide spinners
      SeravoShadows.toggle_banner(section, false, true, domain);
      section.find('.reset').prop('disabled', false);
      section.find('.reset-status').html('');
    }

    /**
     * Function on_error is called if something went wrong.
     */
    function on_error() {
      // Show error banner, re-enable reset buttons and hide spinners
      SeravoShadows.toggle_banner(section, true);
      section.find('.reset').prop('disabled', false);
      section.find('.reset-status').html('');
    }
  },

  /**
   * Show or hide the result banner.
   * @param {Object}  section The shadow section element.
   * @param {Boolean} error   Whether to show error banner.
   * @param {Boolean} success Whether to show success banner.
   * @param {String}  domain  Domain of the shadow or empty to hide.
   */
  toggle_banner: function(section, error = false, success = false, domain = '') {
    var error_banner = section.find('.alert#alert-error');
    var success_banner = section.find('.alert#alert-success');
    var sr_instructions = success_banner.find('.shadow-reset-sr-alert');

    // Show / hide error banner
    if ( error === true ) {
      error_banner.show();
    } else {
      error_banner.hide();
    }

    // Show / hide success banner
    if ( success === true ) {
      success_banner.show();
    } else {
      success_banner.hide();
    }

    // Show / hide success banner
    if ( success === true && domain !== '' ) {
      jQuery(section).find('#shadow-primary-domain').text(domain);
      sr_instructions.show();
    } else {
      sr_instructions.hide();
    }
  },

  /**
   * Toggle folding info row.
   */
  toggle_info_arrow: function() {
    var instance = jQuery(this).closest('.shadow-instance');
    var view_row = instance.find('tr.view');
    var fold_row = instance.find('tr.fold');

    if ( fold_row.hasClass('open') ) {
      // Close the row
      view_row.css('border-bottom', '1.5px solid #ccd0d4');
      fold_row.removeClass('open');
      instance.find('.open-icon').addClass('closed-icon').removeClass('open-icon');
    } else {
      // Open the row
      view_row.css('border-bottom', 'none');
      fold_row.addClass('open');
      instance.find('.closed-icon').addClass('open-icon').removeClass('closed-icon');
    }
  }
};
