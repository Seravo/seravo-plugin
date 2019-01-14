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

    /*
      Parameters for errors etc
      The class notice-error will display the message with a white background and a red left border.
      Use notice-warning for a yellow/orange, and notice-info for a blue left border.
    */
    public function add_notification( $notification_content ) {
      ?>

  		<div class="notice notice-error seravo-notice">
        <div class="seravo-banner">
          <div class="seravo-emblem">
            <a href="https://seravo.com">
              <svg class="seravo-logo" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" viewBox="0 0 34 25" width="34" height="25" >
                <sodipodi:namedview pagecolor="#ffffff" bordercolor="#666666" borderopacity="1" objecttolerance="10" gridtolerance="10" guidetolerance="10" id="namedview132" />
                <defs id="defs113">
                  <style id="style111">.cls-2{fill:#fff}</style>
                </defs>
                <path d="M0 9.3006C0 2.5719 10.44057 1.6086 10.44057 1.6086L33.97477.0 33.85747 19.5789c0 4.9585-11.7241 7.0158-22.89 4.0416v.016L.00653 20.6037z" id="path115" inkscape:connector-curvature="0" style="fill:#00a9d9;fill-rule:evenodd;stroke-width:.01675605" />
                <path class="cls-2" d="m17.56337 21.276c-1.5818.0-3.7674-.2301-5.2344-.633-.345-.086-.5464-.3166-.5464-.6621v-2.3318c0-.2878.201-.5467.5464-.5467h.1151c1.5529.2011 3.969.4021 4.8892.4021 1.3806.0 1.6969-.3741 1.6969-1.1226.0-.4318-.259-.7483-1.0642-1.2088l-3.7387-2.1578c-1.6106-.9201-2.5597-2.3878-2.5597-4.2584.0-2.9067 1.927-4.4607 5.8958-4.4607 2.2719.0 3.6528.2879 5.1191.6621.3454.086.5464.3165.5464.6617v2.3311c0 .3453-.201.5467-.4887.5467h-.086c-.834-.1151-3.3073-.3741-4.7742-.3741-1.1216.0-1.5248.1727-1.5248.8346.0.4316.3164.662.8915 1.0072l3.5663 2.0442c2.3871 1.3812 2.9049 2.8775 2.9049 4.4315 5e-4 2.7044-1.9553 4.8348-6.1542 4.8348z" id="path117" inkscape:connector-curvature="0" style="fill:#fff;stroke-width:.01675605" />
                <path class="cls-2" d="M26.65517 10.1155z" id="path129" style="fill:#fff;stroke-width:.01675605"/>
              </svg>
            </a>
          </div>
        </div>
        <div class="seravo-notice-content">
          <span>
            <?php call_user_func_array($notification_content['callback'], $notification_content['callback_args']); ?>
          </span>
        </div>
  		</div>
  	  <?php
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
     * Enqueue necessary scripts and styles for Seravo notification functionality.
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
global $seravo_notification_factory;
if ( ! isset($seravo_notification_factory) ) {
  $seravo_notification_factory = Seravo_Notification::get_instance();
}

/**
 * Add a Seravo notification. This function is only a wrapper for Seravo_notification_Factory::add_notification.
 * @param string       $id            Unique id/slug of the notification.
 * @param string       $title         Display title of the notification.
 * @param callable     $callback      A function that outputs the notification content.
 * @param string       $screen        Admin screen id where the notification should be displayed in.
 * @param string       $context       Default admin dashboard context where the notification should be displayed in.
 * @param array[mixed] $callback_args Array of arguments that will get passed to the callback function.
 */
function seravo_add_notification( $id, $callback, $callback_args = array() ) {
  global $seravo_notification_factory;
  error_log( 'pppspdp');
  $seravo_notification_factory->add_notification($id, $callback, $callback_args);
}
