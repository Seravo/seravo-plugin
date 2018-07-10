<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
?>

<div id="wpbody" role="main">
  <div id="wpbody-content" aria-label="Main content" tabindex="0">
    <div class="wrap">
    <div class="dashboard-widgets-wrap">
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
                <div class="http-requests_info_loading" style="padding: 0px;">
                  <table class="widefat fixed striped" style="width: 100%; border: none;">
                    <thead>
                      <tr>
                        <th style="width: 25%;"><?php _e('Month', 'seravo'); ?></th>
                        <th style="width: 50%;"><?php _e('HTTP requests', 'seravo'); ?></th>
                        <th style="width: 25%;"><?php _e('Report', 'seravo'); ?></th>
                      </tr>
                    </thead>
                    <tbody id="http-reports_table">
                      
                    </tbody>
                  </table>


                </div>
                <pre id="http-requests_info"></pre>
              </div>
            </div>
          <!--First postbox: end-->

          <!--Second postbox: Cache status-->
            <div id="dashboard_activity" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text"><?php _e('Cache status', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span><?php _e('Cache status', 'seravo'); ?></span>
              </h2>
              <div class="inside" style="padding: 10px 15px;">
                <h3><?php _e('Redis transient and object cache', 'seravo'); ?></h3>
                <div class="redis_info_loading">
                  <img src="/wp-admin/images/spinner.gif">
                </div>
                <pre id="redis_info"></pre>
                <h3><?php _e('Nginx HTTP cache', 'seravo'); ?></h3>
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
                <span class="screen-reader-text">Toggle panel: <?php _e('Data Integrity', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span>
                  <?php _e('Data Integrity', 'seravo'); ?>
                </span>
              </h2>
              <div class="inside" style="padding: 10px;">
                <h3>
                  <?php _e('WordPress core', 'seravo'); ?>
                </h3>
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
  </div>
</div>
