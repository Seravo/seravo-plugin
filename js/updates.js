'use strict';

/**
 * Generate email buttons to the view.
 * @param {Array} emails Emails in Array.
 * @return Email buttons in HTML.
 */
function generate_buttons (emails) {
  var html = [];

  if ( ! emails ) {
    return;
  }

  emails.forEach(
    function (email) {
      if (email) {
        html.push('<button type="button" class="button email-button"><span class="email-button-content">' + email + '</span><span class="delete-icon dashicons dashicons-no"></span></button>')
      }
    }
  );
  jQuery('[name="technical-contacts"]').val(emails.join(', '))
  return html.join(' ');
}

jQuery(document).ready(
  function() {
    var email_input = jQuery('.technical-contacts-input');
    var emails = email_input.data('emails');
    var buttons_div = jQuery('.technical-contacts-buttons');

    buttons_div.html(generate_buttons(emails));

    jQuery('.slack-webhook-test').click(
      function() {
        jQuery.ajax(
          {
          data: 'payload=' + JSON.stringify({"text": "Seravo update notification test sent from " + window.location.href }),
          dataType: 'json',
          processData: false,
          type: 'POST',
          url: document.getElementsByName('slack-webhook')[0].value
          }
        );
      }
    );

    /**
     * Validate and insert email or display format error nag.
     */
    function insert_email() {
      var form = jQuery('[name="seravo-updates-form"]');

      if (seravo.is_email_valid(email_input.val())) {
        emails.push(email_input.val())
        buttons_div.html(generate_buttons(emails));
        email_input.val('');
        form.removeClass('has-errors');
      } else {
        form.addClass('has-errors');
      }
    }

    email_input.keypress(
      function (event) {
        if (event.which == 13) {
          insert_email();
          event.preventDefault();
          return false;
        }
      }
    );

    jQuery('.technical-contacts-add').click(insert_email);

    jQuery('.technical-contacts-buttons').on(
      'click',
      '.email-button',
      function () {
        var text = jQuery(this).find('.email-button-content').html();
        emails.splice(jQuery.inArray(text, emails), 1);
        buttons_div.html(generate_buttons(emails));

        if (emails.length === 0) {
          buttons_div.html("<p class='failure bold'>" + seravo_upkeep_loc.email_fail + "</p>");
        }
      }
    );
  }
);
