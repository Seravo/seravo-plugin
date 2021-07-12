"use strict";

jQuery(document).ready(
  function () {

    jQuery('.seravo-ajax-lazy-load').each(
      function () {
        init_lazy_loader(this);
      }
    );

    jQuery('.seravo-ajax-simple-form').each(
      function () {
        init_simple_form(this);
      }
    );

    jQuery('.seravo-ajax-fancy-form').each(
      function () {
        init_fancy_form(this);
      }
    );

  }
);

/**
 * Javascript for AutoCommand AjaxHandler.
 * Makes request automatically on page load
 * and shows the output.
 */
function init_lazy_loader(section) {
  var section_element = jQuery(section);
  var section_name = section_element.attr('data-section');
  var postbox_id = section_element.closest('.seravo-postbox').attr('data-postbox-id');

  var spinner = jQuery('#' + section_name + '-spinner').closest('.seravo-spinner-wrapper');
  var output = jQuery('#' + section_name + '-output');

  function on_success(response) {
    // Show result
    spinner.hide();
    output.html(response['output']);
    output.show();

    common_after_success(section_element, response);
  }

  function on_error(error) {
    // Show error
    spinner.hide();
    output.replaceWith('<p><b>' + error + '</b></p>');

    common_after_error(section_element, error);
  }

  function execute() {
    spinner.show();

    common_on_request(section_element, null);
    seravo_ajax_request('get', postbox_id, section_name, on_success, on_error);
  }

  execute();
}

/**
 * Javascript for SimpleForm AjaxHandler.
 * Makes request with form data on button click
 * and shows the output.
 */
function init_simple_form(section) {
  var section_element = jQuery(section);
  var section_name = section_element.attr('data-section');
  var postbox_id = section_element.closest('.seravo-postbox').attr('data-postbox-id');

  var button = jQuery(section).find('#' + section_name + '-button');
  var dryrun_button = jQuery(section).find('#' + section_name + '-dryrun-button');
  var spinner = jQuery('#' + section_name + '-spinner').closest('.seravo-spinner-wrapper');
  var output = jQuery('#' + section_name + '-output');

  function on_success(response) {
    // Show output
    spinner.hide();
    output.html(response['output']);
    output.show();

    // Maybe enable button?
    if (! ('dryrun-only' in response) || response['dryrun-only'] === false) {
      button.prop('disabled', false);
    }

    // Enable dry-run button
    if (dryrun_button !== undefined) {
      dryrun_button.prop('disabled', false);
    }

    common_after_success(section_element, response)
  }

  function on_error(error) {
    // Show error
    spinner.hide();
    output.replaceWith('<p><b>' + error + '</b></p>');

    common_after_error(section_element, error);
  }

  function execute(data) {
    button.prop('disabled', true);
    output.hide();
    spinner.show();

    if (dryrun_button !== undefined) {
      dryrun_button.prop('disabled', true);
    }

    common_on_request(section_element, data);
    seravo_ajax_request('get', postbox_id, section_name, on_success, on_error, data);
  }

  // On regular button click
  button.click(
    function () {
      execute(get_form_data(section));
    }
  );

  if (dryrun_button !== undefined) {
    // On dry-run button click
    dryrun_button.click(
      function () {
        execute(
          {
            'dryrun': true,
            ...get_form_data(section)
          }
        );
      }
    );
  }
}

/**
 * Javascript for FancyForm AjaxHandler.
 * Makes request on button click and
 * shows the output in fancy wrapper.
 */
function init_fancy_form(section) {
  var section_element = jQuery(section);
  var section_name = section_element.attr('data-section');
  var postbox_id = section_element.closest('.seravo-postbox').attr('data-postbox-id');

  var wrapper = jQuery(section).find('.seravo-result-wrapper');
  var output = jQuery('#' + section_name + '-output');
  var status = jQuery('#' + section_name + '-status');
  var spinner = jQuery('#' + section_name + '-spinner').closest('.seravo-spinner-wrapper');

  var show_more = jQuery(wrapper).find('.seravo-show-more-wrapper');
  var button = jQuery(section).find('#' + section_name + '-button');
  var dryrun_button = jQuery(section).find('#' + section_name + '-dryrun-button');

  function on_success(response) {
    // Show status and output

    if ('color' in response) {
      wrapper.css('border-color', response['color']);
    }
    if ('output' in response) {
      output.html(response['output']);
      show_more.show();
    }

    spinner.hide();
    status.html(response['title']);
    status.show();

    if (! ('dryrun-only' in response) || response['dryrun-only'] === false) {
      button.prop('disabled', false);
    }
    if (dryrun_button !== undefined) {
      dryrun_button.prop('disabled', false);
    }

    common_after_success(section_element, response)
  }

  function on_error(error) {
    // Show error
    status.html(error);
    wrapper.css('border-color', 'red');
    spinner.hide();
    status.show();

    common_after_error(section_element, error);
  }

  function execute(data) {
    button.prop('disabled', true);
    wrapper.css('border-color', '#e8ba1b');

    status.hide();
    spinner.show();

    if (dryrun_button !== undefined) {
      dryrun_button.prop('disabled', true);
    }

    common_on_request(section_element, data);
    seravo_ajax_request('get', postbox_id, section_name, on_success, on_error, data);
  }

  // On regular button click
  button.click(
    function () {
      execute(get_form_data(section));
    }
  );

  if (dryrun_button !== undefined) {
    // On dry-run button click
    dryrun_button.click(
      function () {
        execute(
          {
            'dryrun': true,
            ...get_form_data(section)
          }
        );
      }
    );
  }
}

// Called before making a request
function common_on_request(section, data) {
  section.trigger("seravoAjaxRequest", [data]);
}

// Called after succesful response
function common_after_success(section, response) {
  section.find('.ajax-autohide').remove();
  section.trigger("seravoAjaxSuccess", [response]);
}

// Called after an error
function common_after_error(section, error) {
  section.trigger("seravoAjaxError", [error]);
}
