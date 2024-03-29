<?php

namespace Seravo\Module;

use \Seravo\Helpers;

/**
 * Class Fixes
 *
 * Contains small custom fixes for WordPress.
 */
final class Fixes {
  use Module;

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    /**
     * Hide update nofications if this is not development
     */
    if ( ! Helpers::is_development() ) {
      \add_action('admin_menu', array( __CLASS__, 'hide_update_notifications' ));
      \add_filter('wp_get_update_data', array( __CLASS__, 'hide_update_data' ));
      \add_filter('site_status_tests', array( __CLASS__, 'remove_update_check' ));
    }

    /**
     * Ask browser not cache WordPress/PHP output if blog is not in production or if
     * WP_DEBUG is set (which happens in wp-config.php by default in non-production).
     */
    if ( ! Helpers::is_production() || WP_DEBUG ) {
      \add_action('send_headers', array( __CLASS__, 'send_no_cache_headers' ));
    }

    /**
     * Send proper headers after unsuccesful login
     */
    \add_action('wp_login_failed', array( __CLASS__, 'change_http_code_to_unauthorized' ));

    /**
     * Additional hooks to option updates to ensure they get refreshed in the
     * Redis object-cache when they change.
     *
     * WP core has implemented a similar fix in 5.3.1,
     * this has been depreacted since that.
     */
    if ( \version_compare(\get_bloginfo('version'), '5.3.1', '<') ) {
      \add_action('added_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ));
      \add_action('updated_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ));
      \add_action('deleted_option', array( __CLASS__, 'maybe_clear_alloptions_cache' ));
    }
  }

  /**
   * Fix a race condition in options caching.
   *
   * @see https://core.trac.wordpress.org/ticket/31245
   * @see https://github.com/tillkruss/redis-cache/issues/58
   * @todo Remove after WordPress 5.3.1 is not supported.
   * @param string $option Option that changed.
   * @return void
   */
  public static function maybe_clear_alloptions_cache( $option ) {
    if ( \wp_installing() ) {
      return;
    }

    // Alloptions should be cached at this point
    $alloptions = \wp_load_alloptions();

    // If alloptions collection has $option key, clear the collection from cache
    // because it can't be trusted to be correct after modifications in options.
    if ( \array_key_exists($option, $alloptions) ) {
      \wp_cache_delete('alloptions', 'options');
    }
  }

  /**
   * Removes core update notifications.
   * @return void
   */
  public static function hide_update_notifications() {
     \remove_action('admin_notices', 'update_nag', 3);
  }

  /**
   * Removes red update bubbles from admin menus.
   * @return array<string,mixed>
   */
  public static function hide_update_data() {
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
   * Removes Site Health update check.
   * @param mixed[] $test_type Test details.
   * @return mixed[] Modified test details.
   */
  public static function remove_update_check( $test_type ) {
    unset($test_type['async']['background_updates']);
    return $test_type;
  }

  /**
   * Return better http status code (401 unauthorized) after failed login.
   * Then failed login attempts (brute forcing) can be noticed in access.log
   * WP core ticket: https://core.trac.wordpress.org/ticket/25446 (won't-fix)
   *
   * Doesn't force http 401 for AJAX calls as some AJAX actions don't
   * need authorization.
   *
   * @return void
   */
  public static function change_http_code_to_unauthorized() {
    if ( ! \defined('DOING_AJAX') ) {
      \status_header(401);
    }
  }

  /**
   * Send no-cache headers. Just a wrapper for
   * WordPress nocache_headers().
   * @return void
   */
  public static function send_no_cache_headers() {
    \nocache_headers();
  }
}
