<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
?>

<div id="wpbody" role="main">
  <div id="wpbody-content" aria-label="Main content" tabindex="0">
    <div class="wrap">
      <div id="dashboard-widgets" class="metabox-holder">
        <div class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <!--First postbox: HTTP request statistics-->
            <div id="dashboard_right_now" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: <?php _e('HTTP request statistics', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span><?php _e('HTTP request statistics', 'seravo'); ?></span>
              </h2>
              <div class="inside">
                <div style="padding: 0px 15px;">
                  <p><?php _e('These monthly reports are generated from the site\'s HTTP access logs. They show every HTTP request of the site, including traffic from both humans and bots. Requests blocked at the firewall level (for example during a DDOS attack) are not logged. Log files can be accessed also directly on the server at <code>/data/slog/html/goaccess-*.html</code>.', 'seravo'); ?></p>
                </div>
                <div style="padding: 0px;">
                  <table class="widefat fixed striped" style="width: 100%; border: none;">
                    <thead>
                      <tr>
                        <th style="width: 25%;"><?php _e('Month', 'seravo'); ?></th>
                        <th style="width: 50%;"><?php _e('HTTP requests', 'seravo'); ?></th>
                        <th style="width: 25%;"><?php _e('Report', 'seravo'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $reports = glob('/data/slog/html/goaccess-*.html');
                      // Create array of months with total request sums
                      $months = array();

                      // Track max request value to calculate relative bar widths
                      $max_requests = 0;

                      if ( empty($reports) ) {
                        echo '<tr><td colspan=3>' . __('No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.', 'seravo') . '</td></tr>';
                      } else {
                        foreach ( $reports as $report ) {
                          $total_requests_string = exec("grep -oE 'total_requests\": ([0-9]+),' $report");
                          preg_match('/([0-9]+)/', $total_requests_string, $total_requests_match);
                          $total_requests = intval($total_requests_match[1]);
                          if ( $total_requests > $max_requests ) {
                            $max_requests = $total_requests;
                          }
                          array_push(
                            $months,
                            array(
                              'date' => substr($report, 25, 7),
                              'requests' => $total_requests,
                            )
                          );
                        }
                      }

                      // List months in reverse order with newest first
                      rsort($months);

                      foreach ( $months as $month ) {
                        $bar_size = intval( $month['requests'] / $max_requests * 100 );
                        if ( $bar_size <= 10 ) {
                          $bar_css = 'auto';
                        } else {
                          $bar_css = $bar_size . '%';
                        }
                        ?>
                        <tr>
                          <td>
                            <a href='?report=" <?php echo $month['date']; ?> ".html' target='_blank'>
                              <?php echo $month['date']; ?>
                            </a>
                          </td>
                          <td>
                            <div style='background: #44A1CB; color: #fff; padding: 3px; width: " <?php echo $bar_css; ?> "; display: inline-block;'>
                              <?php echo $month['requests']; ?>
                            </div>
                          </td>
                          <td>
                            <a href='?report=" <?php echo $month['date']; ?> ".html' target='_blank' class='button hideMobile'>
                              <?php echo __('View report', 'seravo'); ?>
                              <span aria-hidden="true" class="dashicons dashicons-external" style="line-height: unset; padding-left: 3px;"></span>
                            </a>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          <!--First postbox: end-->

          <!--Second postbox: Cache status-->
            <div id="dashboard_activity" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: Cache status</span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle"><span>Cache status</span></h2>
              <div class="inside" style="padding: 10px 15px;">
                <h3>Redis transient and object cache</h3>
                <div class="redis_info_loading">
                  <img src="/wp-admin/images/spinner.gif">
                </div>
                <pre id="redis_info"></pre>
                <h3>Nginx HTTP cache</h3>
                <div class="front_cache_status_loading">
                  <img src="/wp-admin/images/spinner.gif">
                </div>
                <pre id="front_cache_status"></pre>
              </div>
            </div>
          <!--Second postbox: end-->
          </div>
        </div>

        <div class="postbox-container">
          <div id="side-sortables" class="meta-box-sortables ui-sortable">
            <!--Third postbox: Disk usage-->
            <div id="dashboard_quick_press" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: <?php _e('Disk usage', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span>
                  <span class="hide-if-no-js"><?php _e('Disk usage', 'seravo'); ?></span>
                </span>
              </h2>
              <div class="inside" style="padding: 10px 15px;">
                <p><?php _e('Total size of <code>/data</code> is', 'seravo'); ?>
                  <div class="folders_chart_loading">
                    <img src="/wp-admin/images/spinner.gif">
                  </div>
                  <pre id="total_disk_usage"></pre>
                </p>
                <p><?php _e('Biggest directories:', 'seravo'); ?>
                  <div class="folders_chart_loading">
                    <img src="/wp-admin/images/spinner.gif">
                  </div>
                  <canvas id="pie_chart" style="width: 10%; height: 4vh;"></canvas>
                </p>
              </div>
            </div>
            <!--Third postbox: end-->

            <!--Fourth postbox: Data integrity-->
            <div id="dashboard_primary" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: Data Integrity</span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span>Data Integrity</span>
              </h2>
              <div class="inside" style="padding: 10px;">
                <h3>WordPress core</h3>
                <div class="wp_core_verify_loading">
                  <img src="/wp-admin/images/spinner.gif">
                </div>
                <pre id="wp_core_verify"></pre>
                <h3>Git</h3>
                <div class="git_status_loading">
                  <img src="/wp-admin/images/spinner.gif">
                </div>
                <pre id="git_status"></pre  >
              </div>
            </div>
            <!--Fourth postbox: end-->
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php
    wp_register_script( 'chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js', null, null, true );
    wp_enqueue_script('chart-js');
    wp_enqueue_script( 'color-hash', plugins_url( '../js/color-hash.js', __FILE__), 'jquery', null, false );
    wp_enqueue_script( 'reports-chart', plugins_url( '../js/reports-chart.js', __FILE__), 'jquery', null, false );
  ?>

  <script>
    // Generic ajax report loader function
    function seravo_load_report(section) {
      jQuery.post(ajaxurl, { 'action': 'seravo_reports', 'section': section }, function(rawData) {
        if (rawData.length == 0) {
          jQuery('#' + section).html('No data returned for section.');
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
        jQuery('.' + section + '_loading').html('Failed to load. Please try again.');
      });
    }

    seravo_load_report('folders_chart');
    seravo_load_report('wp_core_verify');
    seravo_load_report('git_status');
    seravo_load_report('redis_info');
    seravo_load_report('front_cache_status');

    // Accordion script
    jQuery('.ui-sortable-handle').on('click', function () {
      jQuery(this).parent().toggleClass("closed");
    });
    jQuery('.toggle-indicator').on('click', function () {
      jQuery(this).parent().parent().toggleClass("closed");
    });
  </script>
</div>

<style>
  @media only screen and (min-width: 1500px) {
    #wpbody-content #dashboard-widgets .postbox-container {
      width: 50%;
    }
  }
  .js .widget .widget-top, .js .postbox .hndle {
    cursor: pointer;
  }
  pre {
    overflow: auto;
    padding-bottom: 15px;
  }
  @media only screen and (max-width: 1430px) {
    .hideMobile {
      font-size: 0px!important;
    }
  }
  /* Scrollbar on mobile*/
  ::-webkit-scrollbar {
    -webkit-appearance: none;
  }
  ::-webkit-scrollbar:horizontal {
    height: 12px;
  }
  ::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, .5);
    border-radius: 10px;
    border: 2px solid #dddede;
  }
  ::-webkit-scrollbar-track {
    border-radius: 10px;
    background-color: #dddede;
  }
</style>
