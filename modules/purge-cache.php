<?php
/**
 * Plugin name: Seravo Purge Cache
 * Description: Purges the Seravo cache
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Purge_Cache') ) {
  class Purge_Cache {

    public static function load() {
      // Check permissions before registering actions
      if ( current_user_can(self::custom_capability()) ) {
        add_action('admin_bar_menu', array( __CLASS__, 'purge_button' ), 999);
        add_action('wp_ajax_seravo_purge_cache', array( __CLASS__, 'purge_cache' ));
        add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
        add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
        add_action('admin_notices', array( __CLASS__, 'seravo_purge_notification' ));
      }
    }

    /**
     * Add a purge button in the WP Admin Bar
     */
    public static function purge_button( $admin_bar ) {
      $purge_url = add_query_arg('seravo_purge_cache', '1');
      $admin_bar->add_menu(
        array(
          'id'    => 'nginx-helper-purge-all',
          'title' => '<span class="ab-icon seravo-purge-cache-icon"></span><span title="' .
            // translators: %s cache refresh interval
            sprintf(__('Seravo.com uses front-end proxies to deliver lightning fast response times for your visitors. Cached pages will be refreshed every %s. This button is used for clearing all cached pages from the front-end proxy immediately.', 'seravo'), '15 min') .
            '" class="ab-label seravo-purge-cache-text">' . __('Purge Cache', 'seravo') . '</span>',
        )
      );
    }

    /**
     * Load required scripts and styles for this module
     */
    public static function enqueue_scripts() {
      wp_enqueue_style('seravo_purge_cache', plugin_dir_url(__DIR__) . 'style/purge-cache.css', '', Helpers::seravo_plugin_version());
      wp_enqueue_script('seravo_purge_cache', plugins_url('../js/purge-cache.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
      $loc_array = array(
        'seravo_purge_cache_nonce' => wp_create_nonce('seravo_purge_cache_nonce'),
        'ajax_url'                 => admin_url('admin-ajax.php'),
      );
      wp_localize_script('seravo_purge_cache', 'seravo_purge_cache_loc', $loc_array);
    }

    /**
     * Make capability filterable
     */
    public static function custom_capability() {
      return apply_filters('seravo_purge_cache_capability', 'edit_posts');
    }

    public static function seravo_purge_notification() {
      // Don't show anything if there is no need to.
      if ( ! isset($_REQUEST['seravo_purge_success']) ) {
        return;
      }
      $success = filter_var($_REQUEST['seravo_purge_success'], FILTER_VALIDATE_BOOLEAN);

      if ( $success ) : ?>
        <div class="notice updated is-dismissible">
          <p><strong><?php _e('Success:', 'seravo'); ?></strong> <?php _e('The cache was flushed.', 'seravo'); ?> <button type="button" class="notice-dismiss"></button></p>
        </div>
      <?php else : ?>
        <div class="notice notice-error is-dismissible">
          <p><strong><?php _e('Error:', 'seravo'); ?></strong> <?php _e('The cache was not flushed, please check your PHP error log for details.', 'seravo'); ?> <button type="button" class="notice-dismiss"></button></p>
        </div>
        <?php
      endif;
    }

    /**
     * Purge the cache via AJAX
     */
    public static function purge_cache() {
      $response = array();

      // Check nonce
      if ( ! isset($_REQUEST['nonce']) || ! wp_verify_nonce($_REQUEST['nonce'], 'seravo_purge_cache_nonce') ) {
        $response['success'] = false;
        $response['output'] = __('Error: the nonce did not verify.', 'seravo');

      } else {
        // Run wp-purge-cache, return command code and output
        exec('wp-purge-cache 2>&1', $output, $return_code);
        $response['success'] = (bool) ($return_code === 0);
        $response['output'] = implode("\n", $output);
      }

      // Log any errors
      if ( ! $response['success'] ) {
        error_log('Seravo Purge Cache error: ' . $response['output']);
      }

      wp_send_json($response);
    }
  }

  /* Caching happens in general only in production */
  if ( Helpers::is_production() ) {
    Purge_Cache::load();
  }
}
