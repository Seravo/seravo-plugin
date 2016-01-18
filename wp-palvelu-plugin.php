<?php
/**
 * Plugin Name: WP-Palvelu Plugin
 * Version: 1.3.4
 * Plugin URI: https://github.com/Seravo/wp-palvelu-plugin
 * Description: Enhances WordPress with WP-Palvelu.fi specific features and integrations.
 * Author: Seravo Oy
 * Author URI: https://seravo.fi
 * Text Domain: wpp
 * Domain Path: /languages/
 * License: GPL v2 or later
 */

namespace WPPalvelu;

/*
 * This Plugin should be installed in all instances in WP-Palvelu. If you don't want to use some features
 * You can disable any of the modules by adding correct filter into your theme or plugin.
 * For example:
 *
 * add_filter('wpp_use_client_certificate_login', '__return_false');
 *
 */

/*
 * Translate plugin description too. This is here so that Poedit can find it
 */
__( 'Enhances WordPress with WP-Palvelu.fi specific features and integrations.', 'wpp' );

/*
 * Load helpers so that these functions can be used in modules
 */
require_once(dirname( __FILE__ ) . '/lib/helpers.php');

Class Loader {
  private static $_single; // Let's make this a singleton.
  private static $domain = 'wpp';

  public function __construct() {
    if (isset(self::$_single)) { return; }
    self::$_single       = $this; // Singleton set.

    /*
     * Load translations
     */
    add_action( 'plugins_loaded', array($this,'loadTextdomain') );

    /*
     * It is important to load plugins in init hook so that themes and plugins can override the functionality
     * Use smaller priority so that all plugins and themes are run first.
     */
    add_action('init', array($this,'loadAllModules'), 20);
  }

  public static function loadTextdomain() {

    // Load translations first from the languages directory
    $locale = apply_filters( 'plugin_locale', get_locale(), self::$domain );

    load_textdomain(
      self::$domain,
      WP_LANG_DIR . '/my-plugin/' . self::$domain . '-' . $locale . '.mo'
    );

    // And then from this plugin folder
    load_muplugin_textdomain( 'wpp', basename( dirname(__FILE__) ) . '/languages' );
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
      require_once(dirname( __FILE__ ) . '/modules/fixes.php');
    }

    /*
     * Enable ssl certificate login through /wpp-login endpoint
     */
    if(apply_filters('wpp_use_client_certificate_login',true)) {
      require_once(dirname( __FILE__ ) . '/modules/certificate-login.php');
    }

    /*
     * Add a cache purge button to the WP adminbar
     */
    if(apply_filters('wpp_use_purge_cache',true)) {
      require_once(dirname( __FILE__ ) . '/modules/purge-cache.php');
    }

    /*
     * Use relative urls in post content but absolute urls in feeds
     * This helps migrating the content between development and production
     */
    if(apply_filters('wpp_use_relative_urls',true)) {
      require_once(dirname( __FILE__ ) . '/modules/relative-urls.php');
    }
  }
}

new Loader();
