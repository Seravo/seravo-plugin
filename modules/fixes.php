<?php
/*
 * Plugin name: Seravo.com minor customizations
 * Description: Contains small custom fixes
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Fixes') ) {

  class Fixes {

    /**
     * Loads Seravo features
     */
    public static function load() {

      /**
       * Show Seravo.com notifications if this is Seravo.com instance
       */
      if ( Helpers::is_production() || Helpers::is_staging() ) {
        add_action( 'admin_notices', array( __CLASS__, 'show_admin_notification' ) );
      }

      /**
       * Hide update nofications if this is not development
       */
      if ( ! Helpers::is_development() ) {
        add_action( 'admin_menu', array( __CLASS__, 'hide_update_notifications' ) );
        add_filter( 'wp_get_update_data', array( __CLASS__, 'hide_update_data' ) );
      }

      /**
       * Ask browser not cache WordPress/PHP output if blog is not in production or if
       * WP_DEBUG is set (which happens in wp-config.php by default in non-production).
       */
      if ( ! Helpers::is_production() || WP_DEBUG ) {
        add_action( 'send_headers', array( __CLASS__, 'send_no_cache_headers' ) );
      }

      /**
       * Send proper headers after unsuccesful login
       */
      add_action( 'wp_login_failed', array( __CLASS__, 'change_http_code_to_unauthorized' ) );

      /**
       * Additional hooks to option updates to ensure they get refreshed in the
       * Redis object-cache when they change.
       */
      add_action( 'added_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ) );
      add_action( 'updated_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ) );
      add_action( 'deleted_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ) );

    }


    /**
     * Fix a race condition in options caching
     *
     * See https://core.trac.wordpress.org/ticket/31245
     * and https://github.com/tillkruss/redis-cache/issues/58
     *
     */
    public static function maybe_clear_alloptions_cache( $option ) {

      // error_log("added/updated/deleted option: $option");

      if ( wp_installing() === false ) {
        $alloptions = wp_load_alloptions(); // alloptions should be cached at this point

        // If option is part of the alloptions collection then clear it.
        if ( array_key_exists($option, $alloptions) ) {
          wp_cache_delete( $option, 'options' );
          // error_log("deleted from cache group 'options': $option");
        }
      }
    }

    /**
     * This is used to add notifications from Seravo.com for users
     */
    public static function show_admin_notification() {
      // get notification
      $response = get_transient( 'seravo_notification' );
      if ( false === ( $response ) || ( isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] === 'no-cache' ) ) {

        // Download notification
        $response = self::get_global_notification();
        set_transient( 'seravo_notification', $response, HOUR_IN_SECONDS );
        // allow some html tags but strip most
      }

      $message = '';
      if ( isset($response->message) ) {
        $message = $response->message;
        $message = strip_tags( trim($message), '<br><br/><a><b><strong><i>' );
      }
      // control alert type
      $type = '';
      if ( isset($response->type) ) {
        $type = $response->type;
      }
      if ( ! empty($message) ) {
        ?>
        <div class="<?php echo esc_attr($type); ?> notice is-dismissible">
          <p><?php echo $message; ?> <button type="button" class="notice-dismiss"></button></p>
        </div>
        <?php
      }
    }

    /**
     * Removes core update notifications
     */
    public static function hide_update_notifications() {
      remove_action( 'admin_notices', 'update_nag', 3 );
    }

    /**
     * Removes red update bubbles from admin menus
     */
    public static function hide_update_data( $update_data, $titles = '' ) {
      return array(
        'counts' => array(
          'plugins'      => 0,
          'themes'       => 0,
          'wordpress'    => 0,
          'translations' => 0,
          'total'        => 0,
        ),
        'title'  => '',
      );
    }

    /**
     * Return better http status code (401 unauthorized) after failed login.
     * Then failed login attempts (brute forcing) can be noticed in access.log
     * WP core ticket: https://core.trac.wordpress.org/ticket/25446
     */
    public static function change_http_code_to_unauthorized() {
      status_header( 401 );
    }

    /**
     * Loads global Notification message from central server
     */
    public static function get_global_notification() {
      // use @file_get_contents to suppress the warning when network is down.
      // Usually this happens on offline local development
      return json_decode( @file_get_contents('https://wp-palvelu.fi/ilmoitus/') );
    }

    public static function send_no_cache_headers() {
      // Use WP function for this
      nocache_headers();
    }
  }

  Fixes::load();
}
