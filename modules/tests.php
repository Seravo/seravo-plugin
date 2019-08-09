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

require_once dirname(__FILE__) . '/../lib/tests-ajax.php';

if ( ! class_exists('Tests') ) {
  class Tests {

    public static function load() {
      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_tests_scripts' ));

      // Add AJAX endpoint for running tests
      add_action('wp_ajax_seravo_tests_ajax', 'seravo_ajax_tests');

      seravo_add_postbox(
        'tests',
        __('Update tests', 'seravo'),
        array( __CLASS__, 'tests_postbox' ),
        'tools_page_upkeep_page',
        'normal'
      );

    }

    public static function register_tests_scripts( $page ) {
      wp_register_style('seravo_tests', plugin_dir_url(__DIR__) . '/style/tests.css', '', Helpers::seravo_plugin_version());
      wp_register_script('seravo_tests', plugin_dir_url(__DIR__) . '/js/tests.js', '', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_upkeep_page' ) {
        wp_enqueue_style('seravo_tests');
        wp_enqueue_script('seravo_tests');

        $loc_translation = array(
          'no_data'       => __('No data returned for the section.', 'seravo'),
          'test_success'  => __('Tests were run without any errors!', 'seravo'),
          'test_fail'     => __('At least one of the tests failed.', 'seravo'),
          'run_fail'      => __('Failed to load. Please try again.', 'seravo'),
          'running_tests' => __('Running rspec tests...', 'seravo'),
          'ajaxurl'       => admin_url('admin-ajax.php'),
          'ajax_nonce'    => wp_create_nonce('seravo_tests'),
        );
        wp_localize_script('seravo_tests', 'seravo_tests_loc', $loc_translation);
      }
    }

    public static function tests_postbox() {
      ?>
      <p>
        <?php
        _e('Here you can test the core functionality of your WordPress installation. Same results can be achieved via command line by running <code>wp-test</code> there. For further information, please refer to <a href="https://seravo.com/docs/tests/ng-integration-tests/"> Seravo Developer Documentation</a>.', 'seravo');
        ?>
      </p>
      <button type="button" class="button-primary" id="run-wp-tests"><?php _e('Run Tests', 'seravo'); ?></button>
      <div class="seravo-test-result-wrapper">
        <div class="seravo-test-status" id="seravo_tests_status">
          <?php _e('Click "Run Tests" to run the Codeception tests', 'seravo'); ?>
        </div>
        <div class="seravo-test-result">
          <pre id="seravo_tests"></pre>
        </div>
        <div id="seravo_test_show_more_wrapper" class="hidden">
          <a href="" id="seravo_test_show_more"><?php _e('Toggle Details', 'seravo'); ?>
            <div class="dashicons dashicons-arrow-down-alt2" id="seravo_arrow_show_more">
            </div>
          </a>
        </div>
      </div>
      <?php
    }
  }

  Tests::load();
}
