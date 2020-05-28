<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Seravo Plugin
 * Version: 1.9.18
 * Plugin URI: https://github.com/Seravo/seravo-plugin
 * Description: Enhances WordPress with Seravo.com specific features and integrations.
 * Author: Seravo Oy
 * Author URI: https://seravo.com/
 * Text Domain: seravo
 * Domain Path: /languages/
 * License: GPL v3 or later
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

/*
 * This plugin should be installed in all WordPress instances at Seravo.com.
 * If you don't want to use some features you can disable any of the modules
 * by adding correct filter into your theme or plugin.
 * For example:
 *
 * add_filter('seravo_show_instance_switcher', '__return_false');
 *
 */

/*
 * Load helpers so that these functions can be used in modules
 */
require_once dirname(__FILE__) . '/lib/helpers.php';

/*
 * Load Seravo API module
 */
require_once dirname(__FILE__) . '/lib/api.php';

/**
 * Load Seravo postbox functionalities
 */
require_once dirname(__FILE__) . '/lib/seravo-postbox.php';

/*
 * Load Canonical Domain and HTTPS. Check first that WP CLI is not defined so the module will not
 * perform any redirections locally.
 */
if ( ! defined('WP_CLI') ) {
  require_once dirname(__FILE__) . '/lib/canonical-domain-and-https.php';
}

/*
 * Restrict XML-RPC and/or REST-API user enumeration
 */
require_once dirname(__FILE__) . '/lib/security-restrictions.php';

class Loader {
  private static $_single; // Let's make this a singleton.
  private static $domain = 'seravo';

  public function __construct() {
    if ( isset(self::$_single) ) {
      return;
    }
    self::$_single = $this; // Singleton set.

    /*
     * Load translations
     */
    add_action('plugins_loaded', array( $this, 'load_textdomain' ));

    /*
     * Register early on the direct download add_action as it must trigger
     * before anything is sent to the output buffer.
     */
    add_action('plugins_loaded', array( $this, 'enable_direct_download' ));

    /*
     * It is important to load plugins in init hook so that themes and plugins can override the functionality
     * Use smaller priority so that all plugins and themes are run first.
     */
    add_action('init', array( $this, 'load_all_modules' ), 20);
  }

  /**
   * Pass report file on to admin users
   */
  public static function enable_direct_download() {
    global $pagenow;

    // This check fires on every page load, so keep the scope small
    if ( $pagenow === 'tools.php' && isset($_GET['report']) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification

      // Next check if the request for a report is valid
      // - user be administrator
      // - filename must be of correct form, e.g. 2016-09.html
      if ( current_user_can('administrator') &&
          preg_match('/[0-9]{4}-[0-9]{2}\.html/', $_GET['report'], $matches) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification

        header('Content-type: text/html');
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
        readfile('/data/slog/html/goaccess-' . $matches[0]);
        // Stop executing WordPress once a HTML file has been sent
        exit();
      } else {
        // Yield an error if ?report was requested, but without permissions
        // or with wrong filename.
        exit('Report file not found.');
      }
    }
  }

  public static function load_textdomain() {

    // Load translations first from the languages directory
    $locale = apply_filters('plugin_locale', get_locale(), self::$domain);

    load_textdomain(
      self::$domain,
      WP_LANG_DIR . '/seravo-plugin/' . self::$domain . '-' . $locale . '.mo'
    );

    // And then from this plugin folder
    load_muplugin_textdomain('seravo', basename(dirname(__FILE__)) . '/languages');
  }

  public static function load_all_modules() {

    /*
     * Helpers for hiding useless notifications and small fixes in logging
     */
    require_once dirname(__FILE__) . '/modules/fixes.php';

    /*
     * Helpers for fixing issues with third-party code (plugins etc.)
     */
    require_once dirname(__FILE__) . '/modules/thirdparty-fixes.php';

    /*
     * Add a cache purge button to the WP adminbar
     */
    require_once dirname(__FILE__) . '/modules/purge-cache.php';

    /*
     * Hide the domain alias from search engines
     */
    require_once dirname(__FILE__) . '/modules/noindex-domain-alias.php';

    /*
     * Allow automated login for user 'seravotest' if necessary
     */
    require_once dirname(__FILE__) . '/modules/seravotest-auth-bypass.php';

    /*
     * Log all login attempts, failed or successful. Use no filters, as this should be mandatory
     */
    require_once dirname(__FILE__) . '/modules/wp-login-log.php';

    /*
     * Enforce strong passwords
     */
    require_once dirname(__FILE__) . '/modules/passwords.php';

    /*
     * Instance switcher
     */
    if ( apply_filters('seravo_show_instance_switcher', true) && getenv('WP_ENV') !== 'development' ) {
      require_once dirname(__FILE__) . '/modules/instance-switcher.php';
    }

    /*
     * Check that https is enabled in siteurl
     */
    if ( current_user_can('administrator') ) {
      require_once dirname(__FILE__) . '/modules/check-https.php';
    }

    /*
     * Notify that a newer PHP version is available
     */
    if ( current_user_can('administrator') ) {
      require_once dirname(__FILE__) . '/modules/check-php-version.php';
    }

    /*
     * Check that user has changed admin email to something else from no-reply@seravo
     */
    if ( current_user_can('administrator') ) {
      require_once dirname(__FILE__) . '/modules/check-default-email.php';
    }

    /*
     * Optimize images on upload. Only logged in users make uploads.
     */
    if ( is_user_logged_in() ) {
      require_once dirname(__FILE__) . '/modules/optimize-on-upload.php';
    }

    /*
     * Hide some functionality in multisites from normal admins
     */
    if ( ! is_multisite() || current_user_can('manage_network') ) {
      if ( current_user_can('administrator') ) {
        require_once dirname(__FILE__) . '/modules/toolbox.php';
      }

      /*
       * Backups view for Seravo customers
       */
      if ( apply_filters('seravo_show_backups_page', true) && getenv('CONTAINER') ) {
        require_once dirname(__FILE__) . '/modules/backups.php';
      }

      /*
       * Allow Seravo customers to manage their domains & emails
       */
      if ( apply_filters('seravo_show_domains_page', true) && current_user_can('administrator') && getenv('CONTAINER') ) {
        require_once dirname(__FILE__) . '/modules/domains.php';
      }

      /*
       * Show logs from /data/log/*.log in WP-admin
       */
      if ( current_user_can('administrator') ) {
        require_once dirname(__FILE__) . '/modules/logs.php';
      }

      /*
       * Notification with last WordPress login date and error count. This module handles its own
       * capability checks.
       */
      require_once dirname(__FILE__) . '/modules/login-notification.php';

      /*
       * Upkeep page
       */
      if ( apply_filters('seravo_show_upkeep_page', true) && current_user_can('administrator') ) {
        require_once dirname(__FILE__) . '/modules/upkeep.php';
      }

      /*
       * Site Status page
       */
      if ( apply_filters('seravo_show_site_status_page', true) && current_user_can('administrator') ) {
        require_once dirname(__FILE__) . '/modules/sitestatus.php';
      }

      /*
       * Security page
       */
      if ( apply_filters('seravo_show_security_page', true) && current_user_can('administrator') ) {
        require_once dirname(__FILE__) . '/modules/security.php';
      }
    }

    /*
     * Database and search & replace
     * This module handels it's Network Admin and other permission checks in its own load().
     */
    if ( apply_filters('seravo_show_database_page', true) && current_user_can('administrator') ) {
      require_once dirname(__FILE__) . '/modules/database.php';
    }

    // Load WP-CLI module 'wp seravo'
    if ( defined('WP_CLI') && WP_CLI ) {
      require_once dirname(__FILE__) . '/modules/wp-cli.php';
    }
  }
}

new Loader();
