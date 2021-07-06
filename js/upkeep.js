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
      html.push('<button type="button" class="button email-button"><span class="email-button-content">' + email + '</span><span class="delete-icon dashicons dashicons-no"></span></button>')
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
      $emailInput.val("");
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

    if (emails.length === 0) {
      $buttonsDiv.html("<p style='color:red;'>" + seravo_upkeep_loc.email_fail + "</p>");
    }
  });

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
            jQuery('#tests-wrapper').css('border-left', 'solid 0.5em #038103');
          } else {
            // At least 1 failure, if the return value was non-zero
            jQuery(this).html(seravo_upkeep_loc.test_fail).fadeIn('slow');
            jQuery('#tests-wrapper').css('border-left', 'solid 0.5em #e74c3c');
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
    jQuery('#tests-wrapper').css('border-left', 'solid 0.5em #e8ba1b');
    jQuery('#seravo_test_show_more_wrapper').hide();

    if ( jQuery('#seravo_arrow_show_more').hasClass('dashicons-arrow-up-alt2') ) {
      jQuery('#test-result').hide(function() {
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
      jQuery('#test-result').slideDown('fast', function() {
        jQuery('#seravo_arrow_show_more').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
      });
    } else if ( jQuery('#seravo_arrow_show_more').hasClass('dashicons-arrow-up-alt2') ) {
      jQuery('#test-result').hide(function() {
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
