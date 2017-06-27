<?php
/*
 * Plugin name: Search-replace
 * Description: Enable users to modify wp databases using Search-replace
 * Version: 1.0
 */

namespace Seravo;

if ( ! class_exists( 'SearchReplace' ) ) {
  class SearchReplace {

    public static function load() {

      if ( ! is_multisite() ) {
        $GLOBALS['sr_networkvisibility'] = 'hidden';
      } else {
        $GLOBALS['sr_networkvisibility'] = '';
      }

      add_action( 'wp_ajax_seravo_search_replace' , function(){
        require_once(dirname( __FILE__ ) . '/../lib/search-replace-ajax.php' );
        wp_die();
      });

      // Only show the menu item on systems where wp is available
      if ( exec( 'which wp' ) ) {
        add_action( 'admin_menu' , array( __CLASS__, 'register_search_replace_page' ) );
      }
    }

    public static function register_search_replace_page() {
      add_submenu_page( 'tools.php' , 'Search Replace' , 'Search Replace' , 'manage_options' , 'searchreplace_menu' , array( __CLASS__, 'load_search_replace_page' ) );
    }

    public static function load_search_replace_page() {
      require_once(dirname(__FILE__) . '/../lib/search-replace-page.php' );
    }
  }
  SearchReplace::load();
}
