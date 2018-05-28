<?php
/*
 * Plugin name: Domains
 * Description: View and edit domains and DNS
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Domains') ) {
  class domains {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_domains_page' ) );
    }

    public static function register_domains_page() {
      add_submenu_page( 'tools.php', __('Domains', 'seravo'), __('Domains', 'seravo'), 'manage_options', 'domains_page', array( __CLASS__, 'load_domains_page' ) );
    }

    public static function load_domains_page() {
      require_once(dirname( __FILE__ ) . '/../lib/domains-page.php');
    }

  }

  Domains::load();
}
