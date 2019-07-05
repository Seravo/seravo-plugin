<?php
/*
 * Plugin name: Reports
 * Description: View various reports, e.g. HTTP request staistics from GoAccess
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname( __FILE__ ) . '/../lib/reports-ajax.php';

if ( ! class_exists('Reports') ) {
  class Reports {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_reports_page' ) );

      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_reports_scripts' ) );

      // Add AJAX endpoint for receiving data for various reports
      add_action('wp_ajax_seravo_reports', 'seravo_ajax_reports');
      add_action('wp_ajax_seravo_report_http_requests', 'seravo_ajax_report_http_requests');

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array( __CLASS__, 'register_view_reports_capability' ) );

      // Add HTTP request stats postbox
      seravo_add_postbox(
        'http-request-statistics',
        __('HTTP Request Statistics', 'seravo'),
        array( __CLASS__, 'seravo_http_request_statistics' ),
        'tools_page_reports_page',
        'normal'
      );

      // Add disk usage postbox
      seravo_add_postbox(
        'disk-usage',
        __('Disk Usage', 'seravo'),
        array( __CLASS__, 'seravo_disk_usage' ),
        'tools_page_reports_page',
        'normal'
      );

      // Add cache status postbox
      seravo_add_postbox(
        'cache-status',
        __('Cache Status', 'seravo'),
        array( __CLASS__, 'seravo_cache_status' ),
        'tools_page_reports_page',
        'normal'
      );

      seravo_add_postbox(
        'site-info',
        __('Site Information', 'seravo'),
        array( __CLASS__, 'seravo_site_info' ),
        'tools_page_reports_page',
        'normal'
      );
    }

    public static function register_reports_page() {
      add_submenu_page(
        'tools.php',
        __('Reports', 'seravo'),
        __('Reports', 'seravo'),
        'manage_options',
        'reports_page',
        'Seravo\seravo_postboxes_page' );
    }

    public static function enqueue_reports_scripts( $page ) {
      wp_register_script( 'chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js', null, Helpers::seravo_plugin_version(), true );
      wp_register_script( 'seravo_reports', plugin_dir_url( __DIR__ ) . '/js/reports.js', '', Helpers::seravo_plugin_version());
      wp_register_style( 'seravo_reports', plugin_dir_url( __DIR__ ) . '/style/reports.css', '', Helpers::seravo_plugin_version());
      if ( $page === 'tools_page_reports_page' ) {
        wp_enqueue_style( 'seravo_reports' );
        wp_enqueue_script('chart-js');
        wp_enqueue_script( 'color-hash', plugins_url( '../js/color-hash.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );
        wp_enqueue_script( 'reports-chart', plugins_url( '../js/reports-chart.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );
        wp_enqueue_script( 'seravo_reports' );

        $loc_translation = array(
          'no_data'     => __('No data returned for the section.', 'seravo'),
          'failed'      => __('Failed to load. Please try again.', 'seravo'),
          'no_reports'  => __('No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.', 'seravo'),
          'view_report' => __('View report', 'seravo'),
          'ajaxurl'     => admin_url('admin-ajax.php'),
          'ajax_nonce'  => wp_create_nonce('seravo_reports'),

        );
        wp_localize_script( 'seravo_reports', 'seravo_reports_loc', $loc_translation );
      }
    }

    public static function seravo_http_request_statistics() {
      if ( ! Helpers::is_production() ) {
        __('This feature is available only on live production sites.', 'seravo');
      }
      ?>
      <div style="padding: 0px 15px;">
        <p><?php _e('These monthly reports are generated from the HTTP access logs of your site. All HTTP requests for the site are included, with traffic from both humans and bots. Requests blocked at the firewall level (for example during a DDOS attack) are not logged. The log files can also be accessed directly on the server at <code>/data/slog/html/goaccess-*.html</code>.', 'seravo'); ?></p>
      </div>
      <div class="http-requests_info_loading" style="padding: 0px;">
        <table class="widefat striped" style="width: 100%; border: none;">
          <thead>
            <tr>
              <th style="width: 25%;"><?php _e('Month', 'seravo'); ?></th>
              <th style="width: 50%;"><?php _e('HTTP Requests', 'seravo'); ?></th>
              <th style="width: 25%;"><?php _e('Report', 'seravo'); ?></th>
            </tr>
          </thead>
          <tbody id="http-reports_table"></tbody>
        </table>
      </div>
      <pre id="http-requests_info"></pre>
      <?php
    }

    public static function seravo_cache_status() {
      ?>
      <h3><?php _e('Redis Transient and Object Cache', 'seravo'); ?></h3>
      <div class="redis_info_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <pre id="redis_info"></pre>
      <h3><?php _e('Nginx HTTP Cache', 'seravo'); ?></h3>
      <div class="front_cache_status_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <pre id="front_cache_status"></pre>
      <?php
    }

    public static function seravo_disk_usage() {
      ?>
      <p><?php _e('The total size of <code>/data</code> is', 'seravo'); ?>
        <div class="folders_chart_loading">
          <img src="/wp-admin/images/spinner.gif">
        </div>
        <pre id="total_disk_usage"></pre>
      </p>
      <p><?php _e('Disk usage by directory', 'seravo'); ?>
        <div class="folders_chart_loading">
          <img src="/wp-admin/images/spinner.gif">
        </div>
        <canvas id="pie_chart" style="width: 10%; height: 4vh;"></canvas>
      </p>
      <?php
    }

    public static function seravo_site_info() {
      if ( ! Helpers::is_production() ) {
        __('This feature is available only on live production sites.', 'seravo');
      }

      $site_info = Updates::seravo_admin_get_site_info();

      // If you are devloping locally and want to mock a api request, uncomment the code below and add a valid json response
      // $response = '{
      // }';
      // $site_info = json_decode($response, true);

      $plans = array(
        'demo'       => __('Demo', 'seravo'),
        'mini'       => __('WP Mini', 'seravo'),
        'pro'        => __('WP Pro', 'seravo'),
        'business'   => __('WP Business', 'seravo'),
        'enterprise' => __('WP Enterprise', 'seravo'),
      );

      $countries = array(
        'fi'       => __('Finland', 'seravo'),
        'se'       => __('Sweden', 'seravo'),
        'de'       => __('Germany', 'seravo'),
        'us'       => __('USA', 'seravo'),
        'anywhere' => __('No preference', 'seravo'),
      );

      $contact_emails = array();
      if ( isset($site_info['contact_emails']) ) {
        $contact_emails = $site_info['contact_emails'];
      }

      function print_item( $value, $description ) {
        if ( is_array( $value ) ) {
          echo '<p>' . $description . ': ';
          $mails = implode(', ', $value);
          echo $mails . '</p>';
        } elseif ( ! empty($value) && '1970-01-01' != $value ) {
            echo '<p>' . $description . ': ' . $value . '</p>';
        }
      }

      // Nested arrays need to be checked seperately
      $country = ! empty($site_info['country']) ? $countries[ $site_info['country'] ] : '';

      print_item( $site_info['name'], __('Site Name', 'seravo') );
      print_item( date('Y-m-d', strtotime($site_info['created'])), __('Site Created', 'seravo') );
      print_item( date('Y-m-d', strtotime($site_info['termination'])), __('Plan Termination', 'seravo') );
      print_item( $country, __('Site Location', 'seravo') );
      print_item( $plans[ $site_info['plan']['type'] ], __('Plan Type', 'seravo') );

      if ( isset($site_info['account_manager']) ) {
        print_item( htmlentities($site_info['account_manager']), __('Account Manager', 'seravo') );
      } else {
        echo '<p>' . __('No Account Manager found. Account Manager is only included in Seravo Enterprise plans.', 'seravo') . '</p>';
      }

      print_item( $contact_emails, '<a href="tools.php?page=updates_page">' . __('Technical Contacts', 'seravo') . '</a>' );
    }

    public static function seravo_data_integrity() {
      ?>
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
      <pre id="git_status"></pre>
      <?php
    }
  }

  Reports::load();
}
