"use strict";

jQuery(document).ready(
  function () {

    /**
     * Javascript for AutoCommand.
     * Makes request automatically on page load
     * and shows the output.
     */
    jQuery('.seravo-ajax-lazy-load').each(
      function () {
        var section = jQuery(this).attr('data-section');
        var postbox_id = jQuery(this).closest('.seravo-postbox').attr('data-postbox-id');

        var spinner = jQuery('#' + section + '-spinner');
        var output = jQuery('#' + section + '-output');

        function on_success(response) {
          spinner.hide();
          output.html(response);
          output.show();
        }

        function on_error(error) {
          spinner.hide();
          output.replaceWith('<p><b>' + error + '</b></p>');
        }

        // Make the request
        seravo_ajax_request('get', postbox_id, section, 'output', on_success, on_error);
      }
    );

    /**
     * Javascript for ButtonCommand.
     * Makes request on button click
     * and shows the output.
     */
    jQuery('.seravo-ajax-simple-form').each(
      function () {
        var form = this;
        var section = jQuery(this).attr('data-section');
        var postbox_id = jQuery(this).closest('.seravo-postbox').attr('data-postbox-id');

        var button = jQuery(this).find('#' + section + '-button');
        var dryrun_button = jQuery(this).find('#' + section + '-dryrun-button');
        var spinner = jQuery('#' + section + '-spinner');
        var output = jQuery('#' + section + '-output');

        function on_success(response) {
          spinner.hide();
          output.html(response);
          output.show();
          button.prop('disabled', false);

          if (dryrun_button !== undefined) {
            dryrun_button.prop('disabled', false);
          }
        }

        function on_error(error) {
          spinner.hide();
          output.replaceWith('<p><b>' + error + '</b></p>');
        }

        button.click(
          function () {
            button.prop('disabled', true);
            output.hide();
            spinner.show();

            // Make the request
            seravo_ajax_request('get', postbox_id, section, 'output', on_success, on_error, get_form_data(form));
          }
        );

        dryrun_button.click(
          function () {
            button.prop('disabled', true);
            dryrun_button.prop('disabled', true);
            output.hide();
            spinner.show();

            var data = {
              'dryrun': true,
              ...get_form_data(form)
            }

            // Make the request
            seravo_ajax_request('get', postbox_id, section, 'output', on_success, on_error, data);
          }
        );
      }
    );
  }
);

function seravo_ajax_request(method, postbox_id, section, data_field, on_success, on_error, data) {
  var request_data = {
    'action': 'seravo_ajax_' + postbox_id,
    'section': section,
    'nonce': SERAVO_AJAX_NONCE,
  };
  var request_data = { ...request_data, ...data };

  jQuery.ajax(
    {
      url: seravo_ajax_l10n.ajax_url,
      method: method,
      data: request_data,
    }
  ).done(
    function (response) {
      var error = null;
      var output = null;

      try {
        response = jQuery.parseJSON(response);

        if (response !== null && 'success' in response && response['success'] === true && data_field in response) {
          // Success
          on_success(response[data_field]);
          return;
        } else if (response !== null && 'success' in response && response['success'] === false && 'error' in response) {
          // Failure
          on_error(response['error']);
          return;
        }
      } catch (error) {
        // Failed to parse JSON
        on_error(seravo_ajax_l10n.server_invalid_response);
        return;
      }

      on_error(seravo_ajax_l10n.server_invalid_response);
      return;

    }
  ).fail(
    // Called on failed request (invalid HTTP code(?) or timeout)
    function (jqxhr, text_status) {
      if (text_status === 'timeout') {
        on_error(seravo_ajax_l10n.server_timeout);
      } else {
        on_error(seravo_ajax_l10n.server_error);
      }
    }
  );
}

function get_form_data(section) {
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

  return data;
}
