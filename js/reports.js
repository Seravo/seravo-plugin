// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';
// Generic ajax report loader function
jQuery(document).ready(function($) {

  function seravo_load_http_request_reports(){
    $.post(
      seravo_reports_loc.ajaxurl,
      { 'action': 'seravo_report_http_requests',
        'nonce': seravo_reports_loc.ajax_nonce, },
      function(rawData) {
        var data = JSON.parse(rawData);
        //test if this is still valid
        if (data.length == 0) {
          //echo '<tr><td colspan=3>' . seravo_reports.no_reports . '</td></tr>';
          jQuery('#http-requests_info').html(seravo_reports_loc.no_reports);
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
              seravo_reports_loc.view_report +
              '<span aria-hidden="true" class="dashicons dashicons-external" style="line-height: unset; padding-left: 3px;"></span></a></td></tr>'
              );
            }

          });
        }
      }
    );
  }

  function seravo_load_report(section) {
    jQuery.post(seravo_reports_loc.ajaxurl, {
      'action': 'seravo_reports',
      'section': section,
      'nonce': seravo_reports_loc.ajax_nonce,
    }, function(rawData) {
      if (rawData.length == 0) {
        jQuery('#' + section).html(seravo_reports.no_data);
      }

      if (section === 'folders_chart') {
        var allData = JSON.parse(rawData);
        jQuery('#total_disk_usage').text(allData.data.human);
        generateChart(allData.dataFolders, "pie_chart_disk");
      } else {
        var data = JSON.parse(rawData);
        jQuery('#' + section).text(data.join("\n"));
      }
      jQuery('.' + section + '_loading').fadeOut();
    }).fail(function() {
      jQuery('.' + section + '_loading').html(seravo_reports.failed);
    });
  }
  seravo_load_http_request_reports();
  seravo_load_report('folders_chart');
  seravo_load_report('wp_core_verify');
  seravo_load_report('git_status');
  seravo_load_report('redis_info');
  seravo_load_report('front_cache_status');
});
