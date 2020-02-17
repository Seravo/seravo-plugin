// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';
//Email script
function generateButtons (emails) {
  var html = [];

  if ( ! emails ) {
    return;
  }

  emails.forEach(function (email) {
    if (email) {
      html.push('<button type="button" class="button button-primary email-button"><span class="email-button-content">' + email + '</span><span class="delete-icon dashicons dashicons-no"></span></button>')
    }
  });
  jQuery('[name="technical_contacts"]').val(emails.join(', '))
  return html.join(' ');
}

function validateEmail ($emailInput) {
  var $form = jQuery('[name="seravo_updates_form"]');
  var regex = /^([ÆØÅæøåõäöüa-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})$/;
  var result = regex.test($emailInput.val());
  if ( ! result ) {
    $form.addClass('has-errors');
  } else {
    $form.removeClass('has-errors');
  }
  return result;
}

function drags(dragElement, resizeElement, container) {

  // Initialize the dragging event on mousedown.
  dragElement.on('mousedown touchstart', function(e) {

    dragElement.addClass('draggable');
    resizeElement.addClass('resizable');

    // Check if it's a mouse or touch event and pass along the correct value
    var startX = (e.pageX) ? e.pageX : e.originalEvent.touches[0].pageX;

    // Get the initial position
    var dragWidth = dragElement.outerWidth(),
      posX = dragElement.offset().left + dragWidth - startX,
      containerOffset = container.offset().left,
      containerWidth = container.outerWidth();

    // Set limits
    var minLeft = containerOffset + 10;
    var maxLeft = containerOffset + containerWidth - dragWidth - 10;

    // Calculate the dragging distance on mousemove.
    dragElement.parents().on("mousemove touchmove", function(e) {

      // Check if it's a mouse or touch event and pass along the correct value
      var moveX = (e.pageX) ? e.pageX : e.originalEvent.touches[0].pageX;

      var leftValue = moveX + posX - dragWidth;

      // Prevent going off limits
      if ( leftValue < minLeft) {
        leftValue = minLeft;
      } else if (leftValue > maxLeft) {
        leftValue = maxLeft;
      }

      // Translate the handle's left value to masked divs width.
      var widthValue = (leftValue + dragWidth / 2 - containerOffset) * 100 / containerWidth + '%';

      // Set the new values for the slider and the handle.
      // Bind mouseup events to stop dragging.
      jQuery('.draggable').css('left', widthValue).on('mouseup touchend touchcancel', function () {
        jQuery(this).removeClass('draggable');
        resizeElement.removeClass('resizable');
      });
      jQuery('.resizable').css('width', widthValue);
    }).on('mouseup touchend touchcancel', function(){
      dragElement.removeClass('draggable');
      resizeElement.removeClass('resizable');
    });
    e.preventDefault();
  }).on('mouseup touchend touchcancel', function(e){
    dragElement.removeClass('draggable');
    resizeElement.removeClass('resizable');
  });
}

jQuery(document).ready(function($) {
  var $emailInput = jQuery('.technical_contacts_input');
  var emails = $emailInput.data('emails');
  var $buttonsDiv = jQuery('.technical_contacts_buttons');
  $buttonsDiv.html(generateButtons(emails));

  // Disable form submit using enter
  jQuery('[name="seravo_updates_form"]').on('keyup keypress', function(event) {
    var keyCode = event.keyCode || event.which;
    if (keyCode === 13) {
      event.preventDefault();
      return false;
    }
  });

  jQuery('#slack_webhook_test').click(function() {
    $.ajax({
      data: 'payload=' + JSON.stringify({"text": "Seravo update notification test sent from " + window.location.href }),
      dataType: 'json',
      processData: false,
      type: 'POST',
      url: document.getElementsByName('slack_webhook')[0].value
    });
  });

  function insertEmail() {
    if (validateEmail($emailInput)) {
      emails.push($emailInput.val())
      $buttonsDiv.html(generateButtons(emails));
    }
  }

  $emailInput.keypress(function (event) {
    if (event.which == 13) {
      insertEmail();
      event.preventDefault();
      return false;
    }
  });

  jQuery('.technical_contacts_add').click(function() {
    insertEmail();
  });

  jQuery('.technical_contacts_buttons').on('click', '.email-button', function () {
    var text = jQuery(this).find('.email-button-content').html();
    emails.splice(jQuery.inArray(text, emails), 1);
    $buttonsDiv.html(generateButtons(emails));
  });

  jQuery.post(
    seravo_upkeep_loc.ajaxurl, {
      'action': 'seravo_ajax_upkeep',
      'section': 'seravo_default_config_file',
      'nonce': seravo_upkeep_loc.ajax_nonce
    },function(defaul_config_file) {
      if ( ! defaul_config_file ) {
        jQuery("#change-php-version-button").hide();
      } else {
        jQuery("#overwrite-config-files-span").hide();
      }
    }
  );

  jQuery('#overwrite-config-files').change(function() {
    if (jQuery('#overwrite-config-files').is(':checked')) {
      jQuery("#change-php-version-button").show();
    } else {
      jQuery("#change-php-version-button").hide();
    }
  });

  jQuery('#check-php-compatibility-button').click(function() {
    jQuery(this).fadeOut(400, function(){
      jQuery(this).hide();
    });
    jQuery("#check-php-compatibility-status").fadeOut(400, function() {
      jQuery(this).html('<img src="/wp-admin/images/spinner.gif" style="display:inline-block"> ' + seravo_upkeep_loc.compatibility_check_running).fadeIn(400);
    });
    checkPHPCompatibility();
  });

  function checkPHPCompatibility() {
    jQuery.post(
      seravo_upkeep_loc.ajaxurl, {
        'action': 'seravo_ajax_upkeep',
        'section': 'seravo_check_php_compatibility',
        'nonce': seravo_upkeep_loc.ajax_nonce,
      },
      function(rawData) {
        if ( rawData.length == 0 ) {
          jQuery('#check-php-compatibility-status').html(seravo_upkeep_loc.no_data);
        }
        var data = JSON.parse(rawData);

        /* Display error if wp-php-compatibility-check returns non-zero exit code
        * Else display the number of errors found.
        * If output string is empty, show green light for PHP version change
        */
        if ( data['exit_code'] != 0 ) {
          jQuery("#check-php-compatibility-button").fadeOut(400, function() {
            jQuery(this).show();
          });
          jQuery("#check-php-compatibility-status").fadeOut(400, function() {
            jQuery(this).html('<div>' + seravo_upkeep_loc.compatibility_run_fail + '</div>').fadeIn(400);
          });
        } else if ( data['output'].length ) {
          jQuery("#check-php-compatibility-status").fadeOut(400, function() {
            jQuery(this).html('<div style="color:red;font-weight:bold">' + data['output'] + seravo_upkeep_loc.compatibility_check_error + '</div>').fadeIn(400);
          });
        } else {
          jQuery("#check-php-compatibility-status").fadeOut(400, function() {
            jQuery(this).html('<div style="color:green;font-weight:bold">' + seravo_upkeep_loc.compatibility_check_clear + '</div>').fadeIn(400);
          });
        }
      }
    );
  }

  jQuery('#change-php-version-button').click(function() {
    jQuery("#change-php-version-status").fadeOut(400, function() {
      jQuery(this).show();
    });
    jQuery("#activated-line").hide();
    jQuery("#activation-failed-line").hide();
    jQuery("#overwrite-config-files-span").hide();

    jQuery.post(
      seravo_upkeep_loc.ajaxurl, {
        'action': 'seravo_ajax_upkeep',
        'section': 'check_php_config_files',
        'nonce': seravo_upkeep_loc.ajax_nonce
      });

    jQuery("#seravo-php-version").fadeOut(400, function() {
      $(this).hide();
    });

    setTimeout(function() {
      changePHPVersion();
    }, 500);
  });

  function changePHPVersion() {
    var php_version = $('[name=php-version]:checked').val();

    jQuery.post(
      seravo_upkeep_loc.ajaxurl, {
        'action': 'seravo_ajax_upkeep',
        'section': 'seravo_change_php_version',
        'nonce': seravo_upkeep_loc.ajax_nonce,
        'version': php_version
      }
    );

    var changed = false;
    var attempt = 0, max_attempts = 5;
    function check_php_version() {
      setTimeout(function() {
        jQuery.post(
          seravo_upkeep_loc.ajaxurl, {
            'action': 'seravo_ajax_upkeep',
            'section': 'seravo_php_check_version',
            'nonce': seravo_upkeep_loc.ajax_nonce,
            'version': php_version
          }, function(success) {
            if (success) {
              changed = success;
              attempt = max_attempts;
            }
          }
        ).always(function () {
          if ( ++attempt < max_attempts ) {
            check_php_version();
          } else {
            show_result();
          }
        });
      }, 5000);
    }
    check_php_version();

    function show_result() {
      jQuery("#change-php-version-status").fadeOut(400, function() {
        if ( changed ) {
          jQuery("#activated-line").fadeIn(400, function() {
            jQuery(this).show();
          });
        } else {
          jQuery("#activation-failed-line").fadeIn(400, function() {
            jQuery(this).show();
          });
        }
      });

      jQuery("#seravo-php-version").fadeIn(400, function() {
        $(this).show();
      });
    }
  }

  jQuery.post(
    seravo_upkeep_loc.ajaxurl, {
      'action': 'seravo_ajax_upkeep',
      'section': 'seravo_plugin_version_check',
      'nonce': seravo_upkeep_loc.ajax_nonce
    }, function(is_uptodate_version) {
      if (is_uptodate_version) {
        $('#uptodate_seravo_plugin_version').show();
      } else {
        $('#old_seravo_plugin_version').show();
        $("#seravo_plugin_update_button").show();
      }
    });

  jQuery('#seravo_plugin_update_button').click(function() {
    jQuery("#old_seravo_plugin_version").hide();
    jQuery("#seravo_plugin_update_button").hide();
    jQuery("#update_seravo_plugin_status").fadeIn(400, function() {
      jQuery(this).show();
    });
    update_seravo_plugin();
  });

  function update_seravo_plugin() {
    jQuery.post(
      seravo_upkeep_loc.ajaxurl, {
        'action': 'seravo_ajax_upkeep',
        'section': 'seravo_plugin_version_update',
        'nonce': seravo_upkeep_loc.ajax_nonce
      });
    setTimeout(function() {
      jQuery("#update_seravo_plugin_status").fadeOut(400, function() {
        jQuery(this).hide();
      });
      jQuery("#seravo_plugin_updated").show();
    }, 5000);
  }
});

jQuery(window).load(function(){
  jQuery('.ba-slider').each(function(){
    var cur = jQuery(this);
    // Adjust the slider
    var width = cur.width() + 'px';
    cur.find('.ba-resize img').css('width', width);
    // Bind dragging events
    drags(cur.find('.ba-handle'), cur.find('.ba-resize'), cur);
  });

  function seravo_load_test_report() {
    jQuery.post(
      seravo_upkeep_loc.ajaxurl,
      { 'action': 'seravo_ajax_upkeep',
        'section': 'seravo_tests',
        'nonce': seravo_upkeep_loc.ajax_nonce },
      function(rawData) {
        if ( rawData.length == 0 ) {
          jQuery('#seravo_tests').html(seravo_upkeep_loc.no_data);
        }
        jQuery('#seravo_tests_status').fadeOut('slow', function() {
          var data = JSON.parse(rawData);
          var data_joined = data['test_result'].join("\n");

          if ( data['exit_code'] === 0 ) {
            // No failures, if the return value from wp-test was 0
            jQuery(this).html(seravo_upkeep_loc.test_success).fadeIn('slow');
            jQuery('.seravo-test-result-wrapper').css('border-left', 'solid 0.5em #038103');
          } else {
            // At least 1 failure, if the return value was non-zero
            jQuery(this).html(seravo_upkeep_loc.test_fail).fadeIn('slow');
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
      jQuery('#seravo_tests_status').html(seravo_upkeep_loc.run_fail);
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
      jQuery(this).html('<img src="/wp-admin/images/spinner.gif" style="display:inline-block"> ' + seravo_upkeep_loc.running_tests).fadeIn(400);
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

// Update sliders on resize.
// Because we all do this: i.imgur.com/YkbaV.gif
jQuery(window).resize(function(){
  jQuery('.ba-slider').each(function(){
    var cur = jQuery(this);
    var width = cur.width() + 'px';
    cur.find('.ba-resize img').css('width', width);
  });
});
