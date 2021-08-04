"use strict";

var seravo = {

  /**
   * Add or replace an URL parameter.
   * @param {String} name The parameter name.
   * @param {String} value The parameter value.
   */
  add_url_param: function(name, value) {
    var url_params = new URLSearchParams(window.location.search);
    url_params.set(name, value);

    var new_url = '?' + url_params.toString();
    window.history.pushState({ path: new_url }, '', new_url);
  },

  /**
   * Check if an email address is valid.
   * @param {String} email The email address to validate.
   * @returns Whether the address is valid or not.
   */
  is_email_valid: function(email) {
    var regex = /^([ÆØÅæøåõäöüa-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})$/;
    return regex.test(email);
  },

  /**
   * Get data of a form.
   * @param {Object} section Element to find inputs of.
   * @returns Name => Value array of the form data.
   */
  get_form_data: function(section) {
    var data = [];

    // Inputs
    jQuery(section).find('input').each(
      function () {
        var name = jQuery(this).attr('name');
        var value = jQuery(this).val();
        data[name] = value;
      }
    );

    // Radio inputs
    jQuery(section).find("input[type='radio']:checked").each(
      function () {
        var name = jQuery(this).attr('name');
        var value = jQuery(this).val();
        data[name] = value;
      }
    );

    // Checkboxes
    jQuery(section).find("input[type='checkbox']").each(
      function () {
        var name = jQuery(this).attr('name');
        var value = jQuery(this).prop('checked');
        data[name] = value;
      }
    );

    return data;
  },

  /**
   * Show modal to ask user for confirmation.
   * @param {Object}   caller           The caller of the function. May be anything, passed as parameter to proceed_callback.
   * @param {String}   caption          Title for the modal.
   * @param {String}   modal_id         ID of the modal.
   * @param {Callable} proceed_callback Function called on proceed button click.
   */
  confirm_modal: function(caller, caption, modal_id, proceed_callback) {
    tb_remove();

    // Init cancel button
    jQuery('#' + modal_id + '-cancel').off('click').click(
      function() {
        tb_remove();
      }
    );

    // Init proceed button
    jQuery('#' + modal_id + '-proceed').off('click').click(
      function() {
        tb_remove();
        proceed_callback(caller);
      }
    );

    tb_show(caption, '#TB_inline?width=600&height=120&inlineId=' + modal_id);
  },

}
