<?php
/**
 * Description: Adds Speed Test button to the WP admin bar
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Speed_Test') ) {
  class Speed_Test {

    public static function load() {
      // Check permissions before registering actions
      if ( current_user_can(self::custom_capability()) ) {
        add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
        add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
        add_action('admin_bar_menu', array( __CLASS__, 'speed_test_button' ), 1001);
      }
    }

    public static function custom_capability() {
      return apply_filters('seravo_speed_test_capability', 'edit_posts');
    }

    // Add speed test button to WP Admin Bar
    public static function speed_test_button( $admin_bar ) {
      $target_location = ltrim($_SERVER['REQUEST_URI'], '/');
      $url = get_home_url() . '/wp-admin/tools.php?page=site_status_page';

      if ( substr($target_location, 0, 9) === 'wp-admin/' ) {
        $admin_bar->add_menu(
          array(
            'id'    => 'speed-test-blocked',
            'title' => '<span class="ab-icon seravo-speed-test-icon blocked"></span><span class="ab-label seravo-speed-test-text blocked">' .
              __('Speed Test', 'seravo') . '</span>',
          )
        );

        $admin_bar->add_menu(
          array(
            'parent'  => 'speed-test-blocked',
            'id'      => 'speed-test-menu',
            'title'   => __('Speedtest cannot be run for wp-admin pages', 'seravo'),
          )
        );
      } else {
        $url .= '&speed_test_target=' . $target_location . '#seravo-postbox-speed-test';
        $admin_bar->add_menu(
          array(
            'id'    => 'speed-test',
            'title' => '<span class="ab-icon seravo-speed-test-icon title="derp"></span><span class="ab-label seravo-speed-test-text" title="' .
              __('Test the speed of the current page', 'seravo') . '">' .
              __('Speed Test', 'seravo') . '</span>',
            'href'  => $url,
          )
        );
      }
    }

    /**
     * Load required scripts and styles for this module
     */
    public static function enqueue_scripts() {
      wp_enqueue_style('seravo_speed_test', SERAVO_PLUGIN_URL . 'style/speed-test.css', null, Helpers::seravo_plugin_version(), 'all');
    }
  }
  /* Caching happens in general only in production */
  if ( Helpers::is_production() ) {
    Speed_Test::load();
  }
}
