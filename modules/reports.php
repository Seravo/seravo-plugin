<?php
/*
 * Plugin name: Reports
 * Description: View various reports, e.g. HTTP request staistics from GoAccess
 * Version: 1.0
 */

namespace WPPalvelu;

if (!class_exists('Reports')) {
  class reports {

    public static function load() {
      add_action( 'admin_menu', array(__CLASS__, 'register_reports_page') );

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array(__CLASS__, 'register_view_reports_capability') );
    }

    public static function register_reports_page() {
      add_submenu_page( 'tools.php', 'Reports', 'Reports', 'manage_options', 'reports_page', array(__CLASS__, 'load_reports_page') );
    }

    public static function load_reports_page() {
      require_once(dirname( __FILE__ ) . '/../lib/reports-page.php');
    }

  }

  reports::load();
}
