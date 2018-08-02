<?php
/*
 * Plugin name: Database
 * Description: View database credentials and link to Adminer
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname( __FILE__ ) . '/../lib/search-replace-ajax.php';
require_once dirname( __FILE__ ) . '/../lib/database-ajax.php';

if ( ! class_exists('Database') ) {
  class Database {

    /**
     * Load database features
     */
    public static function load() {

      if ( ! is_multisite() ) {
        $GLOBALS['sr_networkvisibility'] = false;
        $GLOBALS['sr_alltables'] = true;
      } elseif ( current_user_can( 'manage_network' ) ) {
        $GLOBALS['sr_networkvisibility'] = true;
        $GLOBALS['sr_alltables'] = true;
      } else {
        $GLOBALS['sr_networkvisibility'] = false;
        $GLOBALS['sr_alltables'] = false;
      }

      add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_database_scripts' ));
      add_action( 'admin_menu', array( __CLASS__, 'register_database_page' ) );

      // Add AJAX endpoints for wp search-replace and database info
      add_action( 'wp_ajax_seravo_search_replace', 'seravo_ajax_search_replace' );
      add_action( 'wp_ajax_seravo_wp_db_info', 'seravo_ajax_get_wp_db_info' );

    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function enqueue_database_scripts( $page ) {

      wp_register_style('seravo_database', plugin_dir_url(__DIR__) . '/style/database.css', '', Helpers::seravo_plugin_version());
      wp_register_script( 'chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js', '', Helpers::seravo_plugin_version(), true );

      if ( $page === 'tools_page_database_page' ) {
        wp_enqueue_style('seravo_database');
        wp_enqueue_script('chart-js');
        wp_enqueue_script( 'color-hash', plugins_url( '../js/color-hash.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );
        wp_enqueue_script( 'reports-chart', plugins_url( '../js/reports-chart.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );
        wp_enqueue_script( 'seravo_database', plugins_url( '../js/database.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );

        $loc_translation_database = array(
          'ajaxurl'     => admin_url('admin-ajax.php'),
          'ajax_nonce'  => wp_create_nonce('seravo_database'),
        );
        wp_localize_script( 'seravo_database', 'seravo_database_loc', $loc_translation_database );
      }

    }

    /**
     * Add admin menu item
     */
    public static function register_database_page() {
      add_submenu_page( 'tools.php', __('Database', 'seravo'), __('Database', 'seravo'), 'manage_options', 'database_page', array( __CLASS__, 'load_database_page' ) );
    }

    /**
     * Load options page
     */
    public static function load_database_page() {
      require_once dirname( __FILE__ ) . '/../lib/database-page.php';
    }

  }

  Database::load();
}
