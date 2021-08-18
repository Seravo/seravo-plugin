<?php

namespace Seravo\Module;

use \Seravo\Helpers;
use \Seravo\Compatibility;

use \Seravo\Ajax;

use \Seravo\Postbox\Template;

/**
 * Class PurgeCache
 *
 * Adds button for purging the Seravo cache.
 */
final class PurgeCache {
  use Module;

  /**
   * Check whether the module should be loaded or not.
   * @return bool Whether to load.
   */
  protected function should_load() {
    // Require production env and 'edit_posts' capability by default
    $capability = \apply_filters('seravo_' . self::get_name() . '_capability', 'edit_posts');
    return \current_user_can($capability) && Helpers::is_production();
  }

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    \add_action('admin_bar_menu', array( __CLASS__, 'purge_button' ), 999);
    \add_action('wp_ajax_seravo_purge_cache', array( __CLASS__, 'purge_cache' ));
    \add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
    \add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
    \add_action('admin_notices', array( __CLASS__, 'seravo_purge_notification' ));
  }

  /**
   * Add a purge button in the WP Admin Bar.
   * @param \WP_Admin_Bar $admin_bar Instance of the admin bar.
   * @return void
   */
  public static function purge_button( $admin_bar ) {
    \add_query_arg('seravo_purge_cache', '1');
    $admin_bar->add_menu(
      array(
        'id'    => 'nginx-helper-purge-all',
        'title' => '<span class="ab-icon seravo-purge-cache-icon"></span><span title="' .
          // translators: %s cache refresh interval
          __('Seravo.com uses front-end proxies to deliver lightning fast response times for your visitors. Cached pages will be refreshed every 15 minutes. This button is used for clearing all cached pages from the front-end proxy immediately.', 'seravo') .
          '" class="ab-label seravo-purge-cache-text">' . __('Purge Cache', 'seravo') . '</span>',
      )
    );
  }

  /**
   * Load required scripts and styles for this module.
   * @return void
   */
  public static function enqueue_scripts() {
    \wp_enqueue_style('seravo-admin-bar-css');
    \wp_enqueue_script('seravo-admin-bar-js');

    $inline_js = 'seravo_purge_cache_ajax_url="' . \admin_url('admin-ajax.php') . '"; ';
    $inline_js .= 'seravo_purge_cache_nonce="' . \wp_create_nonce('seravo_purge_cache_nonce') . '"; ';
    \wp_add_inline_script('seravo-admin-bar-js', $inline_js, 'before');
  }

  /**
   * Validate purge-cache result and show a notification for the user.
   * @return void
   */
  public static function seravo_purge_notification() {
    // Don't show anything if there is no need to.
    if ( ! isset($_REQUEST['seravo-purge-success']) ) {
      return;
    }

    // Show success/failure notification.
    $success = \filter_var($_REQUEST['seravo-purge-success'], FILTER_VALIDATE_BOOLEAN);

    $class = 'notice-success';
    $msg = '<b>' . __('Success', 'seravo') . ':</b> ' . __('The cache was flushed.', 'seravo');
    if ( ! $success ) {
      $class = 'notice-error';
      $msg = '<b>' . __('Error', 'seravo') . ':</b> ' . __('The cache was not flushed, please check your PHP error log for details.', 'seravo');
    }

    Template::nag_notice(Template::paragraph($msg), $class)->print_html();
  }

  /**
   * AJAX function for purging the cache.
   * @return void
   */
  public static function purge_cache() {
    $response = new Ajax\AjaxResponse();
    $response->is_success(true);

    // Check nonce
    if ( ! isset($_REQUEST['nonce']) || \wp_verify_nonce($_REQUEST['nonce'], 'seravo_purge_cache_nonce') === false ) {
      $response->is_success(false);
      self::error_log("Couldn't purge cache: the nonce did not verify");
    } else {
      // Run wp-purge-cache and check the result
      $exec = Compatibility::exec('wp-purge-cache 2>&1', $output, $return_code);
      if ( $exec === false || $return_code !== 0 ) {
        // Cache purge failed
        $response->is_success(false);
        self::error_log("Couldn't purge cache: " . \implode("\n", $output));
      }
    }

    $response->send();
  }

}
