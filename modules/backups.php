<?php
/*
 * Plugin name: Backups
 * Description: Enable users to list and create backups
 * Version: 1.0
 */

namespace Seravo;

if ( ! class_exists('Backups') ) {
  class Backups {

    public static function load() {

      add_action('wp_ajax_seravo_backups', function() {
          require_once(dirname( __FILE__ ) . '/../lib/backups-ajax.php');
          wp_die();
      });

      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_backups_scripts' ));

      // Only show the menu item on systems where wp-backup is available
      if ( exec('which wp-backup-status') ) {
        add_action( 'admin_menu', array( __CLASS__, 'register_backups_page' ) );
      }

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array( __CLASS__, 'register_view_backups_capability' ) );
    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function register_backups_scripts( $page ) {

      wp_register_style('seravo_backups', plugin_dir_url(__DIR__) . '/style/backups.css');

      if ( $page === 'tools_page_backups_page' ) {
        wp_enqueue_script( 'backups', plugins_url( '../js/backups.js' , __FILE__), 'jquery', null, false );
        wp_enqueue_style('seravo_backups');
      }

    }

    public static function register_backups_page() {
      add_submenu_page( 'tools.php', __('Backups', 'seravo'), __('Backups', 'seravo'), 'manage_options', 'backups_page', array( __CLASS__, 'load_backups_page' ) );
    }

    public static function load_backups_page() {
      require_once(dirname( __FILE__ ) . '/../lib/backups-page.php');
    }

  }

  backups::load();
}
