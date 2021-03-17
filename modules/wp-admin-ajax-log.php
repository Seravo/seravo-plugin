<?php
/**
 * Plugin name: WP Admin Ajax Log
 * Description: Logs admin ajax actions
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('AdminAjaxLog') ) {
  class AdminAjaxLog {

    public static function load() {
      add_filter('admin_init', array( __CLASS__, 'log_admin_ajax_posts' ), 10, 3);
    }

    public static function log_admin_ajax_posts() {
      // Check that we're doing an actual ajax call, if not bail out
      if ( defined('DOING_AJAX') && DOING_AJAX ) {
        // Check that it's a post call
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
          // Log the action
          $message = 'Admin-ajax action: ' . ($_REQUEST['action']);
          $time_local = date('j/M/Y:H:i:s O');
          $log_fp = fopen('/data/log/admin-ajax.log', 'a');
          fwrite($log_fp, "$time_local $message\n");
          fclose($log_fp);
        }
      }
    }
  }

  AdminAjaxLog::load();
}
