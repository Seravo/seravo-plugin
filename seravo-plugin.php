<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Seravo Plugin
 * Version: 1.9.35
 * Plugin URI: https://github.com/Seravo/seravo-plugin
 * Description: Enhances WordPress with Seravo.com specific features and integrations.
 * Author: Seravo Oy
 * Author URI: https://seravo.com/
 * Text Domain: seravo
 * Domain Path: /languages/
 * License: GPL v2 or later
 */

namespace Seravo;

// Deny direct access to this file
if ( ! \defined('ABSPATH') ) {
  die('Access denied!');
}

// Use debug mode only in development
\define('SERAVO_PLUGIN_DEBUG', false);

if ( \defined('SERAVO_PLUGIN_DEBUG') && SERAVO_PLUGIN_DEBUG ) {
  \nocache_headers();
}

if ( ! \defined('SERAVO_PLUGIN_URL') ) {
  \define('SERAVO_PLUGIN_URL', \plugin_dir_url(__FILE__));
}
if ( ! \defined('SERAVO_PLUGIN_DIR') ) {
  \define('SERAVO_PLUGIN_DIR', \plugin_dir_path(__FILE__));
}
if ( ! \defined('SERAVO_PLUGIN_SRC') ) {
  \define('SERAVO_PLUGIN_SRC', SERAVO_PLUGIN_DIR . 'src/');
}

// Use Postbox::class for now to see if autoload needs to be required
if ( ! \class_exists(\Seravo\Postbox\Postbox::class) ) {
  require_once SERAVO_PLUGIN_DIR . 'vendor/autoload.php';
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

/**
 * Load Seravo postbox functionalities
 */
require_once SERAVO_PLUGIN_SRC . 'modules/postbox-init.php';

/*
 * Load Canonical Domain and HTTPS. Check first that WP CLI is not defined so the module will not
 * perform any redirections locally.
 */
if ( ! \defined('WP_CLI') ) {
  require_once SERAVO_PLUGIN_SRC . 'lib/canonical-domain-and-https.php';
}

/*
 * Load Canonical Domain and HTTPS. Check first that WP CLI is not defined so the module will not
 * perform any redirections locally.
 */
SecurityRestrictions::load();

class Loader {
  /**
   * @var $this
   */
  private static $_single; // Let's make this a singleton.
  /**
   * @var string
   */
  private static $domain = 'seravo';

  public function __construct() {
    if ( isset(self::$_single) ) {
      return;
    }
    self::$_single = $this; // Singleton set.

    /*
     * Load translations
     */
    \add_action('plugins_loaded', array( __CLASS__, 'load_textdomain' ));

    /*
     * Register early on the direct download add_action as it must trigger
     * before anything is sent to the output buffer.
     */
    \add_action('plugins_loaded', array( __CLASS__, 'protected_downloads' ));

    /*
     * Register common scripts to be used by modules and pages.
     * No scripts should be enqueued here. Use higher priority
     * so the scripts are registered before any module tries to use them.
     */
    \add_action('init', array( __CLASS__, 'register_scripts' ), 5);

    /*
     * It is important to load plugins in init hook so that themes and plugins can override the functionality
     * Use smaller priority so that all plugins and themes are run first.
     */
    \add_action('init', array( __CLASS__, 'load_all_modules' ), 20);
  }

  /**
   * Pass file download to Nginx with X-Accel-Redirect headers
   * @param string $file Path to file on filesystem, or URL with .seravo prefix
   * @return no-return
   */
  public static function x_accel_redirect( $file ) {
    // If a real file path was given, send out MIME type and file size headers
    if ( \file_exists($file) ) {
      \header('Content-Type: ' . \mime_content_type($file));
      // phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnFilesystem
      \header('Content-Length: ' . \filesize($file));
    }

    // If the filename contains a path component that looks like a filesystem
    // path (has /data/ in it), it will automatically be replaced with the
    // correct URL that Nginx can handle in a X-Accel-Redirect.
    $paths = array(
      '/data/slog/html/',
      '/data/reports/',
    );
    $urls = array(
      '/.seravo/goaccess/',
      '/.seravo/codeception/',
    );
    $file = \str_replace($paths, $urls, $file);

    // Send X-Accel-Redirect header (Nginx version of X-Sendfile)
    \header('X-Accel-Redirect: ' . $file);

    // Stop executing PHP once a file has been sent and let Nginx handle the rest
    exit();
  }

  /**
   * Pass report file on to admin users
   * @return void
   */
  public static function protected_downloads() {
    global $pagenow;

    // This check fires on every page load, so keep the scope small
    // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
    if ( $pagenow === 'tools.php' && isset($_GET['x-accel-redirect']) ) {

      // This URL uses authentication, thus don't cache anything from it
      \nocache_headers();

      // User must be an administrator at access these files or
      // if WP Network, then must be super-admin
      if ( ! \current_user_can('administrator') ) {
        \status_header(401);
        die('Access denied!');
      }

      // Filename must be of correct form, e.g. 2016-09.html or home.png
      // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
      if ( isset($_GET['report']) && \preg_match('/^\d{4}-\d{2}\.html$/', $_GET['report'], $matches) === 1 ) {
        self::x_accel_redirect('/data/slog/html/goaccess-' . $matches[0]);
      // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
      } elseif ( isset($_GET['screenshot']) && \preg_match('/^[a-z-.]+\.png$/', $_GET['screenshot'], $matches) === 1 ) {
        self::x_accel_redirect('/data/reports/tests/debug/' . $matches[0]);
      } else {
        // Yield an error if a file was requested, but with wrong filename.
        \status_header(404);
        die('File could not be found.');
      }
    }
  }

  /**
   * @return void
   */
  public static function load_textdomain() {

    // Load translations first from the languages directory
    $locale = \apply_filters('plugin_locale', \get_locale(), self::$domain);

    \load_textdomain(
      self::$domain,
      WP_LANG_DIR . '/seravo-plugin/' . self::$domain . '-' . $locale . '.mo'
    );

    // And then from this plugin folder
    \load_muplugin_textdomain('seravo', '/seravo-plugin/languages');
  }

  /**
   * Register common scripts later maybe needed by other scripts.
   * No scripts should be enqueued here.
   * @return void
   */
  public static function register_scripts() {
    // Register scripts, enqueue only if needed by another script
    \wp_register_script('seravo-common-js', SERAVO_PLUGIN_URL . 'js/common.js', array( 'jquery', 'thickbox' ), Helpers::seravo_plugin_version());
    \wp_register_style('seravo-common-css', SERAVO_PLUGIN_URL . 'style/common.css', array( 'thickbox' ), Helpers::seravo_plugin_version());
    \wp_register_script('seravo-admin-bar-js', SERAVO_PLUGIN_URL . 'js/admin-bar.js', array( 'jquery', 'seravo-common-js' ), Helpers::seravo_plugin_version());
    \wp_register_style('seravo-admin-bar-css', SERAVO_PLUGIN_URL . 'style/admin-bar.css', array(), Helpers::seravo_plugin_version());
  }

  /**
   * @return void
   */
  public static function load_all_modules() {
    /*
     * Helpers for hiding useless notifications and small fixes in logging
     */
    Module\Fixes::load();
    /*
     * Helpers for fixing issues with third-party code (plugins etc.)
     */
    Module\ThirdPartyFixes::load();
    /*
     * Add a cache purge button to the WP adminbar
     */
    Module\PurgeCache::load();
    /*
     * Add a speed test button to the WP adminbar
     */
    Module\SpeedTest::load();
    /*
     * Hide the domain alias from search engines
     */
    Module\Noindex::load();
    /*
     * Allow automated login for user 'seravotest' if necessary
     */
    Module\SeravoTestAuthBypass::load();
    /*
     * Log all login attempts, failed or successful. Use no filters, as this should be mandatory
     */
    Module\LoginLog::load();
    /*
     * Log plugin and theme activations, deactivations, installations and deletions
     */
    Module\PluginLog::load();
    /*
     * Log important user changes in user such as roles, passwords and emails
     */
    Module\UserLog::load();
    /*
     * Enforce strong passwords
     */
    Module\Passwords::load();
    /*
     * Instance switcher
     */
    Module\InstanceSwitcher::load();
    /*
     * Check that https is enabled in siteurl
     */
    Module\AdminChecks::load();
    /*
     * Add features to image uploading
     */
    Module\ImageUpload::load();
    /*
     * Hide prespecified and given users from a WordPress page
     */
    Module\HideUsers::load();
    /*
     * Add Seravo.com specific WP-CLI actions. Can't be autoloaded because the
     * extended class 'WP_CLI_Command' only exists if 'WP_CLI' is defined.
     */
    if ( \defined('WP_CLI') && WP_CLI ) {
      require_once SERAVO_PLUGIN_SRC . 'modules/seravo-cli.php';
      Module\SeravoCLI::load();
    }

    // OLD AJAX FILES
    require_once SERAVO_PLUGIN_SRC . 'lib/domains-ajax.php';
    require_once SERAVO_PLUGIN_SRC . 'lib/domain-tables.php';

    // Site Status page
    Page\SiteStatus::load();
    // Upkeep page
    Page\Upkeep::load();
    // Database page
    Page\Database::load();
    // Backups page
    Page\Backups::load();
    // Security page
    Page\Security::load();
    // Domains page
    Domains::load();
    // Logs page
    Page\Logs::load();

    if ( \defined('SERAVO_PLUGIN_DEBUG') && SERAVO_PLUGIN_DEBUG ) {
      // Test page
      Page\TestPage::load();
    }

    /*
     * Show notification stylish wp-admin dashboard widgets
     */
    DashboardWidgets::load();
  }
}

new Loader();
