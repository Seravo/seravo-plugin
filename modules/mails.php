<?php
/*
 * Plugin name: Mails
 * Description: ---
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Mails') ) {

  class Mails {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_mails_page' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_styles' ) );
    }

    public static function register_mails_page() {
      add_submenu_page( 'tools.php', __('Mails', 'seravo'), __('Mails', 'seravo'), 'manage_options', 'mails_page', array( __CLASS__, 'load_mails_page' ) );
    }

    public static function load_mails_page() {
      require_once dirname( __FILE__ ) . '/../lib/mails-page.php';
    }

    /**
     * Enqueues styles and scripts for the admin tools page
     *
     * @param mixed $hook
     * @access public
     * @return void
     */
    public static function admin_enqueue_styles( $hook ) {
      wp_register_style( 'mails_page', plugin_dir_url( __DIR__ ) . '/style/mails.css', '', Helpers::seravo_plugin_version() );

      if ( $hook === 'tools_page_mails_page' ) {
        wp_enqueue_style( 'mails_page' );
      }
    }

  }

  Mails::load();
}
