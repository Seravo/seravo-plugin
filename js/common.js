"use strict";

jQuery(document).ready(
  function() {
    // Init shadow banner exit button click
    jQuery('#shadow-indicator > a.shadow-exit').click(seravo.shadow.exit);
  }
);

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
    var regex = /^([ÆØÅæøåõäöüa-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})$/;
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

  shadow: {

    /**
     * Function called on instance switcher shadow button click.
     * @param {Event} event The click event.
     */
    switch_instance: function(event) {
      event.preventDefault();

      var new_location = location.href;
      var target = jQuery(this).attr('href');
      if (target.startsWith('#')) {
        // Shadow doesn't have a domain

        // Match all strings eg. #abc123 (shadow ID)
        var instance = target.match(/#([a-z0-9]+)/);
        // instance[0] = #abc123, instance[1] = abc123
        if (instance && instance[1] && instance[1].length === 6) {
          // Set the cookies, 43200 seconds is 12 hours
          document.cookie = "seravo_shadow=" + instance[1] + "; Max-Age=43200; Path=/";
          // Clear potential old shadow query string
          new_location = new_location.replace(/[a-z]+_shadow=[a-z0-9]+/, '');
        }

      } else  {
        // Shadow has a domain

        var current_host = location.protocol + '//' + location.hostname;
        new_location = new_location.replace(current_host, target);
        // Clean away potential old seravo_production param.
        new_location = new_location.replace(/(\?|\b)seravo_production=.*?(?=&|$)/, '');
        // Add seravo_production param with current hostname
        new_location = new_location.replace(/#.*/, '');
        new_location += new_location.indexOf('?') != -1 ? '&' : '?';
        new_location += 'seravo_production=' + location.hostname;

      }

      // Reload / redirect page
      location.href = new_location;
    },

    /**
     * Function called on instance switcher or banner exit button click.
     * @param {Event} event The click event.
     */
    exit: function(event) {
      event.preventDefault();

      var new_location = location.href;
      var target = jQuery(this).attr('href');
      if (target === '#exit') {
        // Shadow doesn't have a domain

        document.cookie = "seravo_shadow=; Max-Age=0; Path=/";
        // Clear potential old shadow query string
        new_location = new_location.replace(/[a-z]+_shadow=[a-z0-9]+/, '');

      } else {
        // Shadow has a domain

        if (target.endsWith('/')) {
          // Only use hostname so redirecting works
          // Used with DEFAULT_DOMAIN
          new_location = target;
        } else {
          // Otherwise keep protocol, path and query string
          // Clean away potential old seravo_production param.
          new_location = new_location.replace(/(\?|\b)seravo_production=.*?(?=&|$)/, '');
          new_location = new_location.replace(location.protocol + '//' + location.hostname, target);
        }

      }

      // Reload / redirect page
      location.href = new_location;
    },

  },

}
