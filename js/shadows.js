// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function($) {
  $('#shadow-selector').change(function() {
    var $shadow = $("[data-shadow=" + $(this).val() + "]");
    if ($shadow) {
      $('.shadow-row').addClass('shadow-hidden');
      $shadow.removeClass('shadow-hidden');
      $('#shadow-reset').prop('disabled', false);
      $('#shadow-reset-status').html('');
      update_data_gui();
    }
  });
  $('.action-link').click(function(event) {
    event.preventDefault();
    if (! $(this).hasClass('closed')) {
      $(this).addClass('closed');
      $('#shadow-data-actions').addClass('shadow-hidden');
    } else {
      $(this).removeClass('closed');
      $('#shadow-data-actions').removeClass('shadow-hidden');
      update_data_gui();
    }
  });

  function update_data_gui() {
    var instance = $('#shadow-selector').val();
    $('#shadow-reset-instance').html(instance);
    var production_domain = $('input[name=shadow-reset-production]').val();
    var shadow_domain = $('[data-shadow=' + instance + ']').attr('data-domain');
    if (shadow_domain) {
      $('input[name=shadow-reset-sr-from]').val(production_domain);
      $('input[name=shadow-reset-sr-to]').val('://' + shadow_domain);
      $('#shadow-reset-sr-alert').removeClass('shadow-hidden');
      $('#shadow-reset-nosr-alert').addClass('shadow-hidden');
    } else {
      $('input[name=shadow-reset-sr-from]').val('');
      $('input[name=shadow-reset-sr-to]').val('');
      $('#shadow-reset-sr-alert').addClass('shadow-hidden');
      $('#shadow-reset-nosr-alert').removeClass('shadow-hidden');
    }
    $('#shadow-reset').prop('disabled', false);
    $('#shadow-reset-status').html('');
  }

  $('#shadow-reset').click(function(event) {
    var is_user_sure = confirm(seravo_shadows_loc.confirm);
    if ( ! is_user_sure) {
      return;
    }
    seravo_ajax_reset_shadow($('#shadow-selector').val(),
      function( status ){
        if ( status == 'progress' ) {
          event.target.disabled = true;
          $('#shadow-reset-status').html("<img src=\"/wp-admin/images/spinner.gif\">");
        } else if ( status == 'success' ) {
          $('#shadow-reset-status').html(seravo_shadows_loc.success);
        } else if ( status == 'failure' ) {
          $('#shadow-reset-status').html(seravo_shadows_loc.failure);
        } else {
          $('#shadow-reset-status').html(seravo_shadows_loc.error);
        }
      });
  });

  function seravo_ajax_reset_shadow(shadow, animate) {
    animate('progress');
    $.post(
      seravo_shadows_loc.ajaxurl,
      { type: 'POST',
        'action': 'seravo_ajax_shadows',
        'section': 'seravo_reset_shadow',
        'shadow': shadow,
        'nonce': seravo_shadows_loc.ajax_nonce, },
        function( rawData ) {
          var data = JSON.parse(rawData);
          // If the last row of rawData does not begin with SUCCESS:
          if ( data[data.length - 1].search('Success') ) {
            animate('success');
          } else {
            animate('failure');
          }
        }
    );
  }
});
