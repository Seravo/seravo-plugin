// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

(function($) {
  $(window).on('load', function() {
    $('#enable-optimize-images').click(function() {
      $('.max-resolution-field').each(function() {
        $(this).prop( "disabled", ! $(this).prop( "disabled" ) );
      });
    });
  });
})(jQuery);

jQuery(document).ready(function($) {

  function seravo_load_report(section) {
    jQuery.post(seravo_site_status_loc.ajaxurl, {
      'action': 'seravo_ajax_site_status',
      'section': section,
      'nonce': seravo_site_status_loc.ajax_nonce,
    }, function(rawData) {
      if (rawData.length == 0) {
        jQuery('#' + section).html(seravo_site_status_loc.no_data);
      }
      jQuery('.' + section + '_loading').fadeOut();
    }).fail(function() {
      jQuery('.' + section + '_loading').html(seravo_site_status_loc.failed);
    });
  }
  seravo_load_report('wp_core_verify');
  seravo_load_report('git_status');

  $('.reset').click(function(event) {
    var shadow_id = $(this).closest('tbody').attr('id');
    var is_user_sure = confirm(seravo_site_status_loc.confirm);
    if ( ! is_user_sure) {
      return;
    }
    // Hide any old alert messages
    $('.alert').hide();
    seravo_ajax_reset_shadow(shadow_id,
      function( status ) {
        if ( status == 'progress' ) {
          event.target.disabled = true;
          // Select only row with a certain shadow id
          $('#' + shadow_id).find('#shadow-reset-status').html("<img src=\"/wp-admin/images/spinner.gif\">");
        } else if ( status == 'success' ) {
          $('#' + shadow_id).find('#shadow-reset-status').empty();
          $('.alert#alert-success').show();
          $('.closebtn').show();
          // Check if the shadow has domains and show instructions to search-replace domain if necessary
          var shadow_domain = $('#' + shadow_id).find('input[name=shadow-domain]').val();
          if ( ! (shadow_domain.length === 0) ) {
            $('.alert#alert-success').find('.shadow-reset-sr-alert').show();
            $('#shadow-primary-domain').text(shadow_domain);
          }
        } else if ( status == 'failure' ) {
          $('#' + shadow_id).find('#shadow-reset-status').empty();
          $('.alert#alert-failure').show();
          $('.closebtn').show();
        } else if ( status = 'timeout') {
          $('#' + shadow_id).find('#shadow-reset-status').empty();
          $('.alert#alert-timeout').show();
          $('.closebtn').show();
        } else {
          $('#' + shadow_id).find('#shadow-reset-status').empty();
          $('.alert#alert-error').show();
          $('.closebtn').show();
        }
      }
    );
  });

  $('.closebtn').click(function() {
    $(this).parent().hide();
  });

  // Open/fold the row containing additional information
  $('#shadow-table td.open-folded').on('click', function() {
    // Get shadow id of the row's shadow
    var shadow_id = $(this).closest('tbody').attr('id');
    if ($('#' + shadow_id).find('.fold').hasClass('open')) {
      // Fold row and turn icon
      $('#' + shadow_id).find('.fold').removeClass('open');
      $('#' + shadow_id + ' td.open-icon').addClass('closed-icon').removeClass('open-icon');
    } else {
      // Open row and turn icon
      $('#' + shadow_id).find('.fold').addClass('open');
      $('#' + shadow_id + ' td.closed-icon').addClass('open-icon').removeClass('closed-icon');
      // Get production domain and shadow domain
      var shadow_domain = $('#' + shadow_id).find('input[name=shadow-domain]').val();
      // Show the right message depending whether the shadow needs search-replace after reset or not
      // (whether it has domain or not)
      if ( ! (shadow_domain.length === 0)) {
        $('#' + shadow_id).find('.shadow-reset-sr-alert').removeClass('shadow-hidden');
      }
    }
  });

  function seravo_ajax_reset_shadow(shadow, animate) {
    animate('progress');
    $.post(
      seravo_site_status_loc.ajaxurl,
      { type: 'POST',
        'action': 'seravo_ajax_site_status',
        'section': 'seravo_reset_shadow',
        'shadow': shadow,
        'nonce': seravo_site_status_loc.ajax_nonce, },
      function( rawData ) {
        var data = JSON.parse(rawData);
        // If the last row of rawData does not begin with SUCCESS:
        if ( data[data.length - 1].search('Success') ) {
          animate('success');
        } else {
          animate('failure');
        }
      }
      ).fail(function(msg) {
        if (msg.status === 503) {
          animate('timeout');
        } else {
          animate('error');
        }
      });
  }
});
