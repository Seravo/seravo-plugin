<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Seravo Plugin
 * Version: 1.9.43
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
// NOTICE: Do not remove the 'false' parameter before no sites autoload the plugin.
if ( ! \class_exists(\Seravo\Postbox\Postbox::class, false) ) {
  require_once SERAVO_PLUGIN_SRC . 'vendor/autoload.php';
}

/*
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
 * Load the Seravo Plugin loader.
 */
require_once SERAVO_PLUGIN_SRC . 'loader.php';

/*
 * Load Canonical Domain and HTTPS. Check first that WP CLI is not defined so the module will not
 * perform any redirections locally.
 */
SecurityRestrictions::load();

new Loader();
