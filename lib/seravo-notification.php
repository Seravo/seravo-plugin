<?php
/**
 * File for Seravo custom notification functionality.
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Seravo_Notification') ) {
  class Seravo_Notification {

    /**
     * Instance of this class.
     */
    private static $instance = null;

    /**
     * Class constructor.
     */
    private function __construct() {
      $this->load();
    }

    /**
     * Get singleton instance.
     * @return Seravo_Notification Instance of the Seravo_Notification class
     */
    public static function get_instance() {
      if ( self::$instance === null ) {
        self::$instance = new Seravo_Notification();
      }
      return self::$instance;
    }

    /**
     * Enqueue necessary scripts and styles for Seravo postbox functionality.
     */
    public static function enqueue_notification_scripts() {
        wp_enqueue_style('seravo_notification', plugin_dir_url(__DIR__) . 'style/seravo-notification.css', array(), Helpers::seravo_plugin_version());
    }

    public static function load() {
      if ( is_admin() ) {
        add_action( 'admin_enqueue_scripts', array(  __CLASS__,  'enqueue_notification_scripts' ) );
      }
    }
  }
}

/**
 * Create singleton class for Seravo notifications if not set.
 */
global $seravo_notification;
if ( ! isset($seravo_notification) ) {
  $seravo_notification = Seravo_Notification::get_instance();
}
