<?php
/*
 * Plugin name: Mails
 * Description: ---
 * Version: 1.0
 */

namespace Seravo;

if ( ! class_exists('Mails') ) {
  class mails {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_mails_page' ) );
    }

    public static function register_mails_page() {
      add_submenu_page( 'tools.php', __('Mails', 'seravo'), __('Mails', 'seravo'), 'manage_options', 'mails_page', array( __CLASS__, 'load_mails_page' ) );
    }

    public static function load_mails_page() {
      require_once(dirname( __FILE__ ) . '/../lib/mails-page.php');
    }

  }

  Mails::load();
}
