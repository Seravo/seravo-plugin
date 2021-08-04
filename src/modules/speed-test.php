<?php

namespace Seravo\Module;

use \Seravo\Helpers;

/**
 * Class SpeedTest
 *
 * Adds Speed Test button to the WP admin bar.
 */
final class SpeedTest {
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
    \add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
    \add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
    \add_action('admin_bar_menu', array( __CLASS__, 'speed_test_button' ), 1001);
  }

  /**
   * Load required scripts and styles for this module.
   * @return void
   */
  public static function enqueue_scripts() {
    \wp_enqueue_style('seravo-admin-bar-css');
  }

  /**
   * Add speed test button to WP Admin Bar.
   * @param \WP_Admin_Bar $admin_bar Instance of the admin bar.
   * @return void
   */
  public static function speed_test_button( $admin_bar ) {
    $target_location = \ltrim($_SERVER['REQUEST_URI'], '/');
    $url = \get_home_url() . '/wp-admin/tools.php?page=site_status_page';

    if ( \substr($target_location, 0, 9) === 'wp-admin/' ) {
      $admin_bar->add_menu(
        array(
          'id'    => 'speed-test-blocked',
          'title' => '<span class="ab-icon seravo-speed-test-icon blocked"></span><span class="ab-label seravo-speed-test-text blocked">' .
            \__('Speed Test', 'seravo') . '</span>',
        )
      );

      $admin_bar->add_menu(
        array(
          'parent'  => 'speed-test-blocked',
          'id'      => 'speed-test-menu',
          'title'   => \__('Speedtest cannot be run for wp-admin pages', 'seravo'),
        )
      );
    } else {
      $url .= '&speed_test_target=' . $target_location . '#seravo-postbox-speed-test';
      $admin_bar->add_menu(
        array(
          'id'    => 'speed-test',
          'title' => '<span class="ab-icon seravo-speed-test-icon title="derp"></span><span class="ab-label seravo-speed-test-text" title="' .
            \__('Test the speed of the current page', 'seravo') . '">' .
            \__('Speed Test', 'seravo') . '</span>',
          'href'  => $url,
        )
      );
    }
  }

}
