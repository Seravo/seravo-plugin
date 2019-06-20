'use strict';
//Email script
function generateButtons (emails) {
  var html = [];

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

  jQuery('#change-php-version-button').click(function() {
    jQuery("#change-php-version-status").fadeOut(400, function() {
      jQuery(this).show();
    });
    jQuery("#activated-line").hide();
    jQuery("#activation-failed-line").hide();
    changePHPVersion();
  });

  function changePHPVersion() {
    var php_version = $('[name=php-version]:checked').val();

    jQuery("#seravo-php-version").fadeOut(400, function() {
      $(this).hide();
    });

    jQuery.post(
      seravo_updates_loc.ajaxurl, {
        'action': 'seravo_ajax_updates',
        'section': 'seravo_change_php_version',
        'nonce': seravo_updates_loc.ajax_nonce,
        'version': php_version
    });
    setTimeout(function() {
      jQuery.post(
        seravo_updates_loc.ajaxurl, {
          'action': 'seravo_ajax_updates',
          'section': 'seravo_php_check_version',
          'nonce': seravo_updates_loc.ajax_nonce,
          'version': php_version
      }, function(success) {
        jQuery("#change-php-version-status").fadeOut(400, function() {
          if (success) {
            jQuery("#activated-line").fadeIn(400, function() {
              jQuery(this).show();
            });
          } else {
            jQuery("#activation-failed-line").fadeIn(400, function() {
              jQuery(this).show();
            });
          }
        });

        jQuery("#seravo-php-version").fadeIn(400, function() {
          $(this).show();
        });
      });
    }, 5000);
  }

  jQuery.post(
    seravo_updates_loc.ajaxurl, {
      'action': 'seravo_ajax_updates',
      'section': 'seravo_plugin_version_check',
      'nonce': seravo_updates_loc.ajax_nonce
    }, function(is_uptodate_version) {
      if (is_uptodate_version) {
        $('#uptodate_seravo_plugin_version').show();
        console.log("Up to date");
      } else {
        $('#old_seravo_plugin_version').show();
        $("#seravo_plugin_update_button").show();
        console.log("Old version");
      }
    });

    function update_seravo_plugin() {
      jQuery.post(
        seravo_updates_loc.ajaxurl, {
          'action': 'seravo_ajax_updates',
          'section': 'seravo_plugin_version_update',
          'nonce': seravo_updates_loc.ajax_nonce
        }
      );
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
