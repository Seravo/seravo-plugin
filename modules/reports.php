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
    }

    public static function register_reports_page() {
      add_submenu_page( 'tools.php', __('Reports', 'seravo'), __('Reports', 'seravo'), 'manage_options', 'reports_page', array( __CLASS__, 'load_reports_page' ) );
    }

    public static function load_reports_page() {
      require_once dirname( __FILE__ ) . '/../lib/reports-page.php';
    }

    public static function enqueue_reports_scripts( $page ) {
      wp_register_script( 'chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js', null, null, true );
      wp_register_script( 'seravo_reports', plugin_dir_url( __DIR__ ) . '/js/reports.js');
      wp_register_style( 'seravo_reports', plugin_dir_url( __DIR__ ) . '/style/reports.css' );
      if ( $page === 'tools_page_reports_page' ) {
        wp_enqueue_style( 'seravo_reports' );
        wp_enqueue_script('chart-js');
        wp_enqueue_script( 'color-hash', plugins_url( '../js/color-hash.js', __FILE__), 'jquery', null, false );
        wp_enqueue_script( 'reports-chart', plugins_url( '../js/reports-chart.js', __FILE__), 'jquery', null, false );
        wp_enqueue_script( 'seravo_reports' );

        $loc_translation = array(
          'no_data' => __('No data returned for section.', 'seravo'),
          'failed' => __('Failed to load. Please try again.', 'seravo'),
          'no_reports' => __('No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.', 'seravo'),
          'view_report' => __('View report', 'seravo'),

        );
        wp_localize_script( 'seravo_reports', 'seravo_reports_loc', $loc_translation );
      }
    }

  }

  Reports::load();
}
