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

/*
 * This is a master switch to disable all modules.
 * To disable this plugin use:
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
 * Add a cache purge button to the WP adminbar and to WP-CLI
 */
if(apply_filters('wpp_use_cache_purge',true)) {
  require_once(dirname( __FILE__ ) . '/modules/cache-purge.php');
}
