// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

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
              var bar_size = month.requests / max_requests;
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
                '; display: inline-block;">' +
                month.requests +
                '</div></td> <td><a href="?report=-' +
                month.date +
                '.html" target="_blank" class="button hideMobile">' +
                seravo_site_status_loc.view_report +
                '<span aria-hidden="true" class="dashicons dashicons-external" style="line-height: unset; padding-left: 3px;"></span></a></td></tr>'
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
        jQuery('#total_disk_usage').text(allData.data.human);
        generateChart(allData.dataFolders);
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
  seravo_load_report('front_cache_status');

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
    var is_user_sure = confirm(seravo_site_status_loc.confirm);
    if ( ! is_user_sure) {
      return;
    }
    seravo_ajax_reset_shadow($('#shadow-selector').val(),
      function( status ){
        if ( status == 'progress' ) {
          event.target.disabled = true;
          $('#shadow-reset-status').html("<img src=\"/wp-admin/images/spinner.gif\">");
        } else if ( status == 'success' ) {
          $('#shadow-reset-status').html(seravo_site_status_loc.success);
        } else if ( status == 'failure' ) {
          $('#shadow-reset-status').html(seravo_site_status_loc.failure);
        } else {
          $('#shadow-reset-status').html(seravo_site_status_loc.error);
        }
      });
  });

  function seravo_ajax_reset_shadow(shadow, animate) {
    animate('progress');
    $.post(
      seravo_shadows_loc.ajaxurl,
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
