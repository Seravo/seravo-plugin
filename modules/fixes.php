<?php
/*
 * Plugin name: Seravo.com minor customizations
 * Description: Contains small custom fixes
 */

namespace Seravo;

if ( ! class_exists('Fixes') ) {

  class Fixes {

    /**
     * Loads Seravo features
     */
    public static function load() {

      /**
       * Show Seravo.com notifications if this is Seravo.com instance
       */
      if ( Helpers::isProduction() or Helpers::isStaging() ) {
        add_action( 'admin_notices', array( __CLASS__, 'showAdminNotification' ) );
      }

      /**
       * Hide update nofications if this is not development
       */
      if ( ! Helpers::isDevelopment() ) {
        add_action( 'admin_menu', array( __CLASS__, 'hideUpdateNotifications' ) );
        add_filter( 'wp_get_update_data', array( __CLASS__, 'hideUpdateData' ) );
      }

      /**
       * Ask browser not cache WordPress/PHP output if blog is not in production or if
       * WP_DEBUG is set (which happens in wp-config.php by default in non-production).
       */
      if ( ! Helpers::isProduction() || WP_DEBUG) {
        add_action( 'send_headers', array(__CLASS__, 'sendNoCacheHeaders') );
      }

      /**
       * Send proper headers after unsuccesful login
       */
      add_action( 'wp_login_failed', array( __CLASS__, 'changeHttpCodeToUnauthorized' ) );
    }

    /**
     * This is used to add notifications from Seravo.com for users
     */
    public static function showAdminNotification() {
      // get notification
      if ( false === ( $response = get_transient( 'seravo_notification' ) ) || ( isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] === 'no-cache' ) ) {

        // Download notification
        $response = self::getGlobalNotification();
        set_transient( 'seravo_notification', $response, HOUR_IN_SECONDS );
        // allow some html tags but strip most
      }

      $message = '';
      if ( isset($response->message) ) {
        $message = $response->message;
        $message = strip_tags( trim($message),'<br><br/><a><b><strong><i>' );
      }
      // control alert type
      $type = '';
      if ( isset($response->type) ) {
        $type = $response->type;
      }
      if ( ! empty($message) ) {
      ?>
        <div class="<?php esc_attr_e($type) ?> notice is-dismissible">
          <p><?php echo $message; ?> <button type="button" class="notice-dismiss"></button></p>
        </div>
      <?php
      }
    }

    /**
     * Removes core update notifications
     */
    public static function hideUpdateNotifications() {
      remove_action( 'admin_notices', 'update_nag', 3 );
    }

    /**
     * Removes red update bubbles from admin menus
     */
    public static function hideUpdateData( $update_data, $titles = '' ) {
      return array(
        'counts' => array(
          'plugins' => 0,
          'themes' => 0,
          'wordpress' => 0,
          'translations' => 0,
          'total' => 0,
        ),
        'title' => '',
      );
    }

    /**
     * Return better http status code (401 unauthorized) after failed login.
     * Then failed login attempts (brute forcing) can be noticed in access.log
     * WP core ticket: https://core.trac.wordpress.org/ticket/25446
     */
    public static function changeHttpCodeToUnauthorized() {
      status_header( 401 );
    }

    /**
     * Loads global Notification message from central server
     */
    public static function getGlobalNotification() {
      // use @file_get_contents to suppress the warning when network is down.
      // Usually this happens on offline local development
      return json_decode( @file_get_contents('https://wp-palvelu.fi/ilmoitus/') );
    }

    public static function sendNoCacheHeaders() {
      // Use WP function for this
      nocache_headers();
    }
  }

  Fixes::load();
}
