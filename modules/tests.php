<?php
/*
 * Plugin name: Tests
 * Description: Enable users to run wp-test through a GUI
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname( __FILE__ ) . '/../lib/tests-ajax.php';

if ( ! class_exists('Tests') ) {
  class Tests {

    public static function load() {
      add_action('admin_menu', array( __CLASS__, 'register_tests_page' ));

      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_tests_scripts' ));

      // Add AJAX endpoint for running tests
      add_action('wp_ajax_seravo_tests_ajax', 'seravo_ajax_tests');
    }

    public static function register_tests_page() {
      add_submenu_page('tools.php', __('Tests', 'seravo'), __('Tests', 'seravo'), 'manage_options', 'tests_page', array( __CLASS__, 'load_tests_page' ));
    }

    public static function register_tests_scripts( $page ) {
      wp_register_style('seravo_tests', plugin_dir_url(__DIR__) . '/style/tests.css', '', Helpers::seravo_plugin_version());
      wp_register_script('seravo_tests', plugin_dir_url(__DIR__) . '/js/tests.js', '', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_tests_page' ) {
        wp_enqueue_style('seravo_tests');
        wp_enqueue_script('seravo_tests');

        $loc_translation = array(
          'no_data'       => __('No data returned for section.', 'seravo'),
          'test_success'  => __('Tests run succesfully without errors!', 'seravo'),
          'test_fail'     => __('At least one test failed.', 'seravo'),
          'run_fail'      => __('Failed to load. Please try again.', 'seravo'),
          'running_tests' => __('Running rspec tests...', 'seravo'),
          'ajaxurl'       => admin_url('admin-ajax.php'),
          'ajax_nonce'    => wp_create_nonce('seravo_tests'),
        );
        wp_localize_script('seravo_tests', 'seravo_tests_loc', $loc_translation);
      }
    }

    public static function load_tests_page() {
      require_once dirname(__FILE__) . '/../lib/tests-page.php';
    }
  }

  Tests::load();
}
