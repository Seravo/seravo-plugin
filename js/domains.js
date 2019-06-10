'use strict';

jQuery(document).ready(function($) {

  var update_zone_button = jQuery('#update-zone-btn');
  var publish_zone_button = jQuery('#publish-zone-btn');

  update_zone_button.add(publish_zone_button).click(function (event) {

    event.preventDefault();

    var response_div = jQuery("#zone-edit-response");
    var spinner_div = jQuery("#zone-update-spinner");

    jQuery("#zone-fetch-response").html("");

    response_div.html("");
    spinner_div.html("<img src=\"/wp-admin/images/spinner.gif\">");

    jQuery.post(seravo_domains_loc.ajaxurl, {
      'action': 'seravo_ajax_domains',
      'section': 'update_zone',
      'domain': $("input[name='domain']").val(),
      'compulsory': $("textarea[name='compulsory']").val(),
      'zonefile': $("textarea[name='zonefile']").val(),
      'nonce': seravo_domains_loc.ajax_nonce,
    },

    function (rawData) {
      if (rawData == 0) {
        // If eg. AJAX callback not found
        response_div.html('<p><b>No data returned for the update request.</b></p>');

      } else {

        var response = JSON.parse(rawData);

        if (response['status'] && response['status'] === 400) {

          response_div.html("<p><b>" + seravo_domains_loc.zone_update_failed + ":</b></p><p>" + response['reason'] + "</p>");

        } else {

          // If the domain was published, reload the page
          if (publish_zone_button.length) {
            location.reload();
          }

          var response_html = "<p><b>" + seravo_domains_loc.zone_update_success + "</b></p>";
          // Add modifications to response_html as an ordered list
          var modifications = response['modifications'];
          if (modifications != null && modifications.length > 0) {
            response_html += "<p>" + seravo_domains_loc.zone_modifications + "</p><ol>";
            response_html += "<li>" + modifications.join("</li><li>") + "</li>";
            response_html += "</ol>";
          }

          response_div.html(response_html);
          // Refresh records for sanitized data
          fetch_dns();

        }

      }

      spinner_div.html("");

    }

    ).fail(function () {

      zone_update_failed();

    });

    function zone_update_failed() {

      response_div.html('<p><b>Failed to update the zone. Please try again.</b></p>');

    }

  });

  function fetch_dns() {

    var response_div = jQuery("#zone-fetch-response");
    var compulsory_records = jQuery("textarea[name='compulsory']");
    var editable_records = jQuery("textarea[name='zonefile']");

    if (response_div.length && compulsory_records.length && editable_records.length) {

      jQuery.post(seravo_domains_loc.ajaxurl, {
        'action': 'seravo_ajax_domains',
        'section': 'fetch_dns',
        'domain': $("input[name='domain']").val(),
        'nonce': seravo_domains_loc.ajax_nonce,
      },

      function (rawData) {

        if (rawData == 0) {
          // If eg. AJAX callback not found
          fetch_error('No data returned for the dns fetch.');

        } else {

          var response = JSON.parse(rawData);

          if (response['reason'] && response['status'] && response['status'] === 400) {
            fetch_error(response['reason']);
          } else if ( response['error'] ) {
            fetch_error(response['error']);
          } else {
            compulsory_records.val(response['compulsory']['records'].join("\n"));
            editable_records.val(response['editable']['records'].join("\n"));
            editable_records.prop('readonly', false);
          }

        }

      }

      ).fail(function () {
        fetch_error('DNS fetch failed! Please refresh the page.');
      });

      function fetch_error(error) {
        response_div.html('<p style="margin:0;"><b>' + error + '</b></p>');
        // Disable further editing when errors
        editable_records.prop('readonly', true);
        update_zone_button.attr("disabled", true);
      }

    }

  }

});
