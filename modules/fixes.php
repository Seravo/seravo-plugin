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
       * Hide update nofications if this is not development
       */
      if ( ! Helpers::is_development() ) {
        add_action('admin_menu', array( __CLASS__, 'hide_update_notifications' ));
        add_filter('wp_get_update_data', array( __CLASS__, 'hide_update_data' ));
        add_filter('site_status_tests', array( __CLASS__, 'remove_update_check' ));
      }

      /**
       * Ask browser not cache WordPress/PHP output if blog is not in production or if
       * WP_DEBUG is set (which happens in wp-config.php by default in non-production).
       */
      if ( ! Helpers::is_production() || WP_DEBUG ) {
        add_action('send_headers', array( __CLASS__, 'send_no_cache_headers' ));
      }

      /**
       * Send proper headers after unsuccesful login
       */
      add_action('wp_login_failed', array( __CLASS__, 'change_http_code_to_unauthorized' ));

      /**
       * Additional hooks to option updates to ensure they get refreshed in the
       * Redis object-cache when they change.
       *
       * WP core has implemented a similar fix in 5.3.1,
       * this has been depreacted since that.
       */

      if ( version_compare(get_bloginfo('version'), '5.3.1', '<') ) {
        add_action('added_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ));
        add_action('updated_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ));
        add_action('deleted_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ));
      }

    }


    /**
     * Fix a race condition in options caching
     *
     * See https://core.trac.wordpress.org/ticket/31245
     * and https://github.com/tillkruss/redis-cache/issues/58
     *
     */
    public static function maybe_clear_alloptions_cache( $option ) {

      if ( wp_installing() === false ) {
        $alloptions = wp_load_alloptions(); // alloptions should be cached at this point

        // If alloptions collection has $option key, clear the collection from cache
        // because it can't be trusted to be correct after modifications in options.
        if ( array_key_exists($option, $alloptions) ) {
          wp_cache_delete('alloptions', 'options');
        }
      }
    }

    /**
     * Removes core update notifications
     */
    public static function hide_update_notifications() {
      remove_action('admin_notices', 'update_nag', 3);
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
     * Removes Site Health update check
     */
    public static function remove_update_check( $tests ) {
      unset($tests['async']['background_updates']);
      return $tests;
    }

    /**
     * Return better http status code (401 unauthorized) after failed login.
     * Then failed login attempts (brute forcing) can be noticed in access.log
     * WP core ticket: https://core.trac.wordpress.org/ticket/25446
     */
    public static function change_http_code_to_unauthorized() {
      status_header(401);
    }

    public static function send_no_cache_headers() {
      // Use WP function for this
      nocache_headers();
    }
  }

  Fixes::load();
}
