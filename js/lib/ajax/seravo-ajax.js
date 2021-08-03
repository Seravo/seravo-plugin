"use strict";

function seravo_ajax_request(method, postbox_id, section, on_success, on_error, data, retry = 0) {
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

        if (response !== null && 'success' in response && response['success'] === true) {
          if ('poller_id' in response) {
            // Polling requested
            setTimeout(
              function () {
                seravo_poller(method, postbox_id, section, on_success, on_error, response['poller_id']);
              },
              5000
            );
            return;
          } else {
            // Success
            if ( 'refresh' in response ) {
              location.reload();
            }
            on_success(response);
          }
          return;
        } else if (response !== null && 'success' in response && response['success'] === false && 'error' in response) {
          // Failure
          on_error(response['error']);
          return;
        }
      } catch (error) {
        // Failed to parse JSON / or error in code
        console.log("Error: ", error);
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

function seravo_poller(method, postbox_id, section, on_success, on_error, poller_id, retry = 0) {
  if (retry == 10) {
    on_error(seravo_ajax_l10n.server_timeout);
    return;
  }

  jQuery.ajax(
    {
      url: seravo_ajax_l10n.ajax_url,
      method: method,
      data: {
        'action': 'seravo_ajax_' + postbox_id,
        'section': section,
        'nonce': SERAVO_AJAX_NONCE,
        'poller_id': poller_id,
      },
    },
  ).done(
    function (response) {
      try {
        response = jQuery.parseJSON(response);

        if ('poller_id' in response) {
          // Poll again
          setTimeout(
            function () {
              seravo_poller(method, postbox_id, section, on_success, on_error, poller_id, 0)
            },
            2000
          );
          return;
        } else {
          if (response !== null && 'success' in response && response['success'] === true) {
            // Success
            on_success(response);
            return;
          } else if (response !== null && 'success' in response && response['success'] === false && 'error' in response) {
            // Failure
            on_error(response['error']);
            return;
          }
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
    function () {
      // Poll again
      setTimeout(
        function () {
          seravo_poller(method, postbox_id, section, on_success, on_error, poller_id, retry + 1)
        },
        3000
      );
    }
  );

}
