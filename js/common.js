"use strict";

jQuery(document).ready(
  function () {
    jQuery('.seravo-show-more-wrapper').click(on_show_more_wrapper_click);

    jQuery('.email-list-input').find('button').click(on_email_list_add_click);
    jQuery('.email-list-input').each(
      function () {
        update_email_list(this);
      }
    );
  }
);

/**
 * Function to be called on show-more click.
 */
function on_show_more_wrapper_click(event) {
  event.preventDefault();

  var link = jQuery(this).find('a');
  var icon = jQuery(this).find('.dashicons');
  var form = jQuery(this).closest('.seravo-ajax-fancy-form');
  var output = jQuery('#' + jQuery(form).attr('data-section') + '-output');

  if (icon.hasClass('dashicons-arrow-down-alt2')) {
    icon.slideDown(
      'fast',
      function () {
        icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        link.html(link.html().replace(seravo_ajax_l10n.show_more, seravo_ajax_l10n.show_less));
      }
    );
    output.slideDown(
      'fast',
      function () {
        output.show();
      }
    );
  } else if (icon.hasClass('dashicons-arrow-up-alt2')) {
    icon.slideDown(
      function () {
        icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        link.html(link.html().replace(seravo_ajax_l10n.show_less, seravo_ajax_l10n.show_more));
      }
    );
    output.slideUp(
      'fast',
      function () {
        output.hide();
      }
    );
  }
}

/**
 * Function to be called on email-list "add" button click.
 */
function on_email_list_add_click(event) {
  event.preventDefault();

  var table = jQuery(this).closest('.email-list-input');
  var input = table.find("input[type='email']");
  var hidden_input = table.find("input[type='text']");

  var email = input.val();
  var emails = hidden_input.val().split(',');

  if (emails.indexOf(email) != -1) {
    return;
  } else if (! is_email_valid(email)) {
    return;
  }

  input.val('');

  emails.push(email);
  hidden_input.val(emails.join(','));

  update_email_list(table);
}

/**
 * Function to be called when emails in email-list have changed.
 */
function update_email_list(table) {
  var hidden_input = jQuery(table).find("input[type='text']");
  var emails = hidden_input.val();

  jQuery(table).find('.email-list-row').remove();

  if (emails === undefined) {
    return;
  }

  emails.split(',').forEach(
    function (email) {
      if (email === "") {
        return;
      }

      var content = '<p class="email-button-content" title="' + email + '">' + email + '</p><span class="delete-icon dashicons dashicons-no"></span>';
      var button = '<button type="button" value="' + email + '" class="button email-button">' + content + '</button>';
      jQuery(table).find('tr:last').after('<tr class="email-list-row"><td colspan="2">' + button + '</td></tr>')
    }
  );

  jQuery(table).find('.email-button').click(
    function () {
      var email = jQuery(this).val();

      var emails = hidden_input.val().split(',');
      emails = emails.filter(
        function (n) {
          return n !== email;
        }
      )
      hidden_input.val(emails.join(','));

      update_email_list(table);
    }
  )
}

function is_email_valid(email) {
  var regex = /^([ÆØÅæøåõäöüa-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})$/;
  return regex.test(email);
}

/**
 * Get form input values for the request.
 */
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

  // Checkboxes
  jQuery(section).find("input[type='checkbox']").each(
    function () {
      var name = jQuery(this).attr('name');
      var value = jQuery(this).prop('checked');
      data[name] = value;
    }
  );

  return data;
}
