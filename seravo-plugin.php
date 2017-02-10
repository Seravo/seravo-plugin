<?php
/**
 * Plugin Name: Seravo Plugin
 * Version: 1.4.0
 * Plugin URI: https://github.com/Seravo/seravo-plugin
 * Description: Enhances WordPress with Seravo.com specific features and integrations.
 * Author: Seravo Oy
 * Author URI: https://seravo.com/
 * Text Domain: wpp
 * Domain Path: /languages/
 * License: GPL v2 or later
 */

namespace Seravo;

/*
 * This plugin should be installed in all WordPress instances at Seravo.com.
 * If you don't want to use some features you can disable any of the modules
 * by adding correct filter into your theme or plugin.
 * For example:
 *
 * add_filter('seravo_use_relative_urls', '__return_false');
 *
 */

/*
 * Load helpers so that these functions can be used in modules
 */
require_once(dirname( __FILE__ ) . '/lib/helpers.php');

Class Loader {
  private static $_single; // Let's make this a singleton.
  private static $domain = 'seravo';

  public function __construct() {
    if (isset(self::$_single)) { return; }
    self::$_single       = $this; // Singleton set.

    /*
     * Load translations
     */
    add_action( 'plugins_loaded', array($this,'loadTextdomain') );

    /*
     * Register early on the direct download add_action as it must trigger
     * before anything is sent to the output buffer.
     */
    add_action( 'plugins_loaded', array($this, 'enable_direct_download') );

    /*
     * It is important to load plugins in init hook so that themes and plugins can override the functionality
     * Use smaller priority so that all plugins and themes are run first.
     */
    add_action('init', array($this,'loadAllModules'), 20);
  }

  /**
   * Pass report file on to admin users
   */
  public static function enable_direct_download() {
    global $pagenow;

    // This check fires on every page load, so keep the scope small
    if ( $pagenow == 'tools.php' && isset($_GET['report']) ) {

      // Next check if the request for a report is valid
      // - user be administrator
      // - filename must be of correct form, e.g. 2016-09.html
      if (current_user_can('administrator') &&
          preg_match('/[0-9]{4}-[0-9]{2}\.html/', $_GET['report'], $matches) ) {

        header("Content-type: text/html");
        readfile("/data/slog/html/goaccess-". $matches[0]);
        // Stop executing WordPress once a HTML file has been sent
        exit();
      } else {
        // Yield an error if ?report was requested, but without permissions
        // or with wrong filename.
        exit("Report file not found.");
      }

    }
  }

  public static function loadTextdomain() {

    // Load translations first from the languages directory
    $locale = apply_filters( 'plugin_locale', get_locale(), self::$domain );

    load_textdomain(
      self::$domain,
      WP_LANG_DIR . '/seravo-plugin/' . self::$domain . '-' . $locale . '.mo'
    );

    // And then from this plugin folder
    load_muplugin_textdomain( 'seravo', basename( dirname(__FILE__) ) . '/languages' );
  }

  public static function loadAllModules() {

    /*
     * This is a master switch to disable all modules.
     */
    if(apply_filters('seravo_disable_modules',false)) {
      return;
    }

    /*
     * Helpers for hiding useless notifications and small fixes in logging
     */
    if(apply_filters('seravo_use_helpers',true)) {
      require_once(dirname( __FILE__ ) . '/modules/fixes.php');
    }

    /*
     * Add a cache purge button to the WP adminbar
     */
    if(apply_filters('seravo_use_purge_cache',true)) {
      require_once(dirname( __FILE__ ) . '/modules/purge-cache.php');
    }

    /*
     * Hide the domain alias from search engines
     */
    if(apply_filters('seravo_hide_domain_alias',true)) {
      require_once(dirname( __FILE__ ) . '/modules/noindex-domain-alias.php');
    }

    /*
     * Use relative urls in post content but absolute urls in feeds
     * This helps migrating the content between development and production
     */
    if(apply_filters('seravo_use_relative_urls',true)) {
      require_once(dirname( __FILE__ ) . '/modules/relative-urls.php');
    }

    /*
     * View various reports for Seravo customers
     */
    if (apply_filters('seravo_show_reports_page',true)) {
      require_once(dirname( __FILE__ ) . '/modules/reports.php');
    }
  }
}

new Loader();
