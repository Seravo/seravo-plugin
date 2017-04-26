<?php
/*
 * Plugin name: Seravoupdates
 * Description: Enable users to enable or disable Seravo WordPress updates
 * Version: 1.0
 */

namespace Seravo;

if (!class_exists('Seravoupdates')) {
  class Autoupdates {

    public static function load() {
      add_action( 'admin_menu', array(__CLASS__, 'register_seravoupdates_page') );

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array(__CLASS__, 'register_view_seravoupdates_capability') );
    }

    public static function register_autoupdates_page() {
		if (getenv("SERAVO_API_KEY") != "") {
          add_submenu_page( 'tools.php', 'Seravoupdates', 'Seravoupdates', 'manage_options', 'seravoupdates_page', array(__CLASS__, 'load_seravoupdates_page') );
	    }
    }

    public static function load_seravoupdates_page() {
      require_once(dirname( __FILE__ ) . '/../lib/seravoupdates-page.php');
    }

  }

  autoupdates::load();
  
	


}
