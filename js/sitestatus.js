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
  function seravo_load_http_request_reports(){
    $.post(
      seravo_site_status_loc.ajaxurl,
      { 'action': 'seravo_report_http_requests',
        'nonce': seravo_site_status_loc.ajax_nonce, },
      function(rawData) {
        var data = JSON.parse(rawData);
        //test if this is still valid
        if (data.length == 0) {
          //echo '<tr><td colspan=3>' . seravo_site_status.no_reports . '</td></tr>';
          jQuery('#http-requests_info').html(seravo_site_status_loc.no_reports);
        } else {
          // take months separately

          //max value is in it's own little container
          var result = data.filter(function( obj ) {
            return obj.hasOwnProperty('max_requests');
          });
          var max_requests = result[0].max_requests;

          data.forEach( function(month) {
            if (month.hasOwnProperty('date')) {
              var bar_size = month.requests / max_requests * 100;
              if ( bar_size <= 10 ) {
                var bar_css = 'auto';
              } else {
                var bar_css = bar_size + '%';
              }
              jQuery( '#http-reports_table' ).prepend('<tr><td><a href="?report=' +
                month.date +
                '.html" target="_blank"> ' +
                month.date +
                ' </a> </td> <td><div style="background: #44A1CB; color: #fff; padding: 3px; width: ' +
                bar_css +
                '; display: inline-block;"><div style="white-space: nowrap;">' +
                month.requests +
                '</div></div></td> <td><a href="?report=-' +
                month.date +
                '.html" target="_blank" class="button hideMobile">' +
                seravo_site_status_loc.view_report +
                '<span aria-hidden="true" class="dashicons dashicons-external" style="line-height: 1.4; padding-left: 3px;"></span></a></td></tr>'
              );
            }

          });
        }
      }
    );
  }

  function seravo_load_report(section) {
    jQuery.post(seravo_site_status_loc.ajaxurl, {
      'action': 'seravo_ajax_site_status',
      'section': section,
      'nonce': seravo_site_status_loc.ajax_nonce,
    }, function(rawData) {
      if (rawData.length == 0) {
        jQuery('#' + section).html(seravo_site_status_loc.no_data);
      }

      if (section === 'folders_chart') {
        var allData = JSON.parse(rawData);
        // Calculate the used disk space; deduct the size of backups from the total
        // space used. allData.data.size is in bytes, so divide into MB.
        var used_disk = (allData.data.size - allData.dataFolders['/data/backups/'].size) / 1000000;

        // Read plan's maximum disk space and transform into bytes
        var max_disk = $("#maximum_disk_space").html() * 1000;

        allData.dataFolders = Object.fromEntries(
          Object.entries(allData.dataFolders)
            .filter(([key]) => ! key.startsWith('/data/backups/'))
        )

        jQuery('#total_disk_usage').text(Math.round(used_disk) + 'MB');
        generateDiskDonut(used_disk, max_disk);
        generateDiskBars(allData.dataFolders);
      } else if (section === 'front_cache_status') {
        var data = JSON.parse(rawData);
        var data_joined = data['test_result'].join("\n");

        if ( data['success'] === true ) {
          jQuery('.seravo_cache_tests_status').html(seravo_site_status_loc.cache_success).fadeIn('slow');
          jQuery('.seravo-cache-test-result-wrapper').css('border-left', 'solid 0.5em #038103');
        } else {
          jQuery('.seravo_cache_tests_status').html(seravo_site_status_loc.cache_failure).fadeIn('slow');
          jQuery('.seravo-cache-test-result-wrapper').css('border-left', 'solid 0.5em #e74c3c');
        }

        jQuery('#seravo_cache_tests').append(data_joined);
        jQuery('#run-cache-tests').prop('disabled', false);

        jQuery(this).fadeIn('slow', function() {
          jQuery('.seravo_cache_test_show_more_wrapper').fadeIn('slow');
        });
      } else if (section === 'redis_info') {
        var data = JSON.parse(rawData);
        var expired_keys = data[0];
        var evicted_keys = data[1];
        var keyspace_hits = data[2];
        var keyspace_misses = data[3];
        generateRedisHitChart(keyspace_hits, keyspace_misses);
        jQuery('#redis-expired-keys').text(expired_keys);
        jQuery('#redis-evicted-keys').text(evicted_keys);
      } else if (section === 'longterm_cache') {
        var data = JSON.parse(rawData);
        var hits = data[0];
        var misses = data[1];
        var stales = data[2];
        var bypasses = data[3];
        generateHTTPHitChart(hits, misses, stales);
        jQuery('#http-cache-bypass').text(bypasses);
      } else {
        var data = JSON.parse(rawData);
        jQuery('#' + section).text(data.join("\n"));
      }
      jQuery('.' + section + '_loading').fadeOut();
    }).fail(function() {
      jQuery('.' + section + '_loading').html(seravo_site_status_loc.failed);
    });
  }
  seravo_load_http_request_reports();
  seravo_load_report('folders_chart');
  seravo_load_report('wp_core_verify');
  seravo_load_report('git_status');
  seravo_load_report('redis_info');
  seravo_load_report('longterm_cache');

  jQuery('#run-cache-tests').click(function() {
    jQuery('#seravo_cache_tests').html('');
    jQuery('.seravo-cache-test-result-wrapper').css('border-left', 'solid 0.5em #e8ba1b');
    jQuery('.seravo_cache_test_show_more_wrapper').hide();

    if ( jQuery('#seravo_arrow_cache_show_more').hasClass('dashicons-arrow-up-alt2') ) {
      jQuery('.seravo-cache-test-result').hide(function() {
        jQuery('#seravo_arrow_cache_show_more').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
      });
    }

    jQuery('.seravo_cache_tests_status').fadeOut(400, function() {
      jQuery(this).html('<div class="front_cache_status_loading"><img src="/wp-admin/images/spinner.gif" style="display:inline-block"> ' + seravo_site_status_loc.running_cache_tests + '</div>').fadeIn(400);
    });

    jQuery(this).prop('disabled', true);
    seravo_load_report('front_cache_status');
  });

  jQuery('.seravo_cache_test_show_more').click(function(event) {
    event.preventDefault();

    if ( jQuery('#seravo_arrow_cache_show_more').hasClass('dashicons-arrow-down-alt2') ) {
      jQuery('.seravo-cache-test-result').slideDown('fast', function() {
        jQuery('#seravo_arrow_cache_show_more').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
      });
    } else if ( jQuery('#seravo_arrow_cache_show_more').hasClass('dashicons-arrow-up-alt2') ) {
      jQuery('.seravo-cache-test-result').hide(function() {
        jQuery('#seravo_arrow_cache_show_more').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
      });
    }
  });

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
    );
  }
});
