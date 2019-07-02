// phpcs:disable PEAR.Functions.FunctionCallSignature
jQuery(window).on('load', function() {

  function seravo_load_test_report() {
    jQuery.post(
      seravo_tests_loc.ajaxurl,
      { 'action': 'seravo_tests_ajax',
        'section': 'seravo_tests',
        'nonce': seravo_tests_loc.ajax_nonce },
      function(rawData) {
        if ( rawData.length == 0 ) {
          jQuery('#seravo_tests').html(seravo_tests_loc.no_data);
        }
        jQuery('#seravo_tests_status').fadeOut('slow', function() {
          var data = JSON.parse(rawData);
          var data_joined = data.join("\n");

          if ( ! (/Failed examples.*/g).test(data_joined) ) {
            // No failures, if the string "Failed examples" was not found in data
            jQuery(this).html(seravo_tests_loc.test_success).fadeIn('slow');
            jQuery('.seravo-test-result-wrapper').css('border-left', 'solid 0.5em #038103');
          } else {
            // At least 1 failure, if the string "Failed examples" was found
            jQuery(this).html(seravo_tests_loc.test_fail).fadeIn('slow');
            jQuery('.seravo-test-result-wrapper').css('border-left', 'solid 0.5em #e74c3c');
          }
          // Display the retrieved data and re-enable the run tests button
          jQuery('#seravo_tests').append(data_joined);
          jQuery('#run-wp-tests').prop('disabled', false);

          jQuery(this).fadeIn('slow', function() {
            jQuery('#seravo_test_show_more_wrapper').fadeIn('slow');
          });
        });
      }
    ).fail(function() {
      jQuery('#seravo_tests_status').html(seravo_tests_loc.run_fail);
      jQuery('#run-wp-tests').prop('disabled', false);
    });
  }

  jQuery('#run-wp-tests').click(function() {
    jQuery('#seravo_tests').html('');
    jQuery('.seravo-test-result-wrapper').css('border-left', 'solid 0.5em #e8ba1b');
    jQuery('#seravo_test_show_more_wrapper').hide();

    if ( jQuery('#seravo_arrow_show_more').hasClass('dashicons-arrow-up-alt2') ) {
      jQuery('.seravo-test-result').hide(function() {
        jQuery('#seravo_arrow_show_more').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
      });
    }

    jQuery('#seravo_tests_status').fadeOut(400, function() {
      jQuery(this).html('<img src="/wp-admin/images/spinner.gif" style="display:inline-block"> ' + seravo_tests_loc.running_tests).fadeIn(400);
    });

    jQuery(this).prop('disabled', true);
    seravo_load_test_report();
  });

  jQuery('#seravo_test_show_more').click(function(event) {
    event.preventDefault();

    if ( jQuery('#seravo_arrow_show_more').hasClass('dashicons-arrow-down-alt2') ) {
      jQuery('.seravo-test-result').slideDown('fast', function() {
        jQuery('#seravo_arrow_show_more').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
      });
    } else if ( jQuery('#seravo_arrow_show_more').hasClass('dashicons-arrow-up-alt2') ) {
      jQuery('.seravo-test-result').hide(function() {
        jQuery('#seravo_arrow_show_more').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
      });
    }
  });

});
