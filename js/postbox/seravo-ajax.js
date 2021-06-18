"use strict";

jQuery(document).ready(
  function () {

    /**
     * Javascript for AutoCommand.
     * Makes request automatically on page load
     * and shows the output.
     */
    jQuery('.seravo-ajax-auto-command').each(
      function () {
        var section = jQuery(this).attr('data-section');
        var postbox_id = jQuery(this).closest('.seravo-postbox').attr('data-postbox-id');

        // Make the request
        jQuery.get(
          seravo_ajax_l10n.ajax_url,
          {
            'action': 'seravo_ajax_' + postbox_id,
            'section': section,
            'nonce': SERAVO_AJAX_NONCE,
          },
          function (response) {
            var error = null;
            var output = null;

            try {
              response = jQuery.parseJSON(response);

              if (response !== null && 'success' in response && response['success'] === true && 'output' in response) {
                // Success
                output = response['output'];
              } else if (response !== null && 'success' in response && response['success'] === false && 'error' in response) {
                // Failure
                error = response['error'];
              }
            } catch (error) {
              // Failed to parse JSON
              error = seravo_ajax_l10n.server_invalid_response;
            }

            jQuery('#' + section + '-spinner').hide();

            if (output !== null) {
              jQuery('#' + section + '-output').html(output);
              jQuery('#' + section + '-output').show();
            } else if (error !== null) {
              jQuery('#' + section + '-output').replaceWith('<p><b>' + error + '</b></p>');
            }
          }
        ).fail(
          // Called on failed request (invalid HTTP code or timeout)
          function (jqxhr, text_status) {
            if (text_status === 'timeout') {
              var error = seravo_ajax_l10n.server_timeout;
            } else {
              var error = seravo_ajax_l10n.server_error;
            }

            jQuery('#' + section + '-spinner').hide();
            jQuery('#' + section + '-output').replaceWith('<p><b>' + error + '</b></p>');
          }
        );
      }
    );

  }
);
