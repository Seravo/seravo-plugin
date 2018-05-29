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

      // Add AJAX endpoint for receiving data for various reports
      add_action('wp_ajax_seravo_reports', 'seravo_ajax_reports');

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array( __CLASS__, 'register_view_reports_capability' ) );
    }

    public static function register_reports_page() {
      add_submenu_page( 'tools.php', __('Reports', 'seravo'), __('Reports', 'seravo'), 'manage_options', 'reports_page', array( __CLASS__, 'load_reports_page' ) );
    }

    public static function load_reports_page() {
      require_once(dirname( __FILE__ ) . '/../lib/reports-page.php');
    }

  }

  Reports::load();
}
