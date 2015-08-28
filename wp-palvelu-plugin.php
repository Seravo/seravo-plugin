<?php
/**
 * Plugin Name: WP-palvelu Plugin
 * Plugin URI: https://github.com/Seravo/wp-palvelu-plugin
 * Description: Enables some Wordpress-palvelu specific features
 * Author: Seravo Oy
 * Version: 1.1
 */

/*
 * This Plugin should be installed in all instances in WP-Palvelu. If you don't want to use some features
 * You can disable any of the modules by adding correct filter into your theme or plugin.
 * For example:
 *
 * add_filter('wpp_use_client_certificate_login', '__return_false');
 *
 */
namespace WPPalvelu;

Class Loader {
  private static $_single; // Let's make this a singleton.

  public function __construct() {
    if (isset(self::$_single)) { return; }
    self::$_single       = $this; // Singleton set.

    /*
     * It is important to load plugins in init hook so that themes and plugins can override the functionality
     * Use smaller priority so that all plugins and themes are run first.
     */
    add_action('init', array($this,'loadAllModules'), 20);
  }

  public static function loadAllModules() {

    /*
     * This is a master switch to disable all modules.
     */
    if(apply_filters('wpp_disable_modules',false)) {
      return;
    }

    /*
     * Helpers for hiding useless notifications and small fixes in logging
     */
    if(apply_filters('wpp_use_helpers',true)) {
      require_once(dirname( __FILE__ ) . '/modules/helpers.php');
    }

    /*
     * Enable ssl certificate login through /wpp-login endpoint
     */
    if(apply_filters('wpp_use_client_certificate_login',true)) {
      require_once(dirname( __FILE__ ) . '/modules/wpp-certificate-login.php');
    }

    /*
     * Add a cache purge button to the WP adminbar
     */
    if(apply_filters('wpp_use_purge_cache',true)) {
      require_once(dirname( __FILE__ ) . '/modules/purge-cache.php');
    }
  }
}

new Loader();