"use strict";

jQuery(document).ready(
  function () {
    jQuery('.seravo-show-more-wrapper').click(on_show_more_wrapper_click);
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

/*
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
