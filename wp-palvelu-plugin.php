<?php
/**
 * Plugin Name: WP-palvelu Plugin
 * Plugin URI: https://github.com/Seravo/wp-palvelu-plugin
 * Description: Enables some Wordpress-palvelu specific features
 * Author: Seravo Oy
 * Version: 1.1
 */

/*
 * Helpers for hiding useless notifications and small fixes in logging
 */
require_once(dirname( __FILE__ ) . '/modules/helpers.php');

/*
 * Enable ssl certificate login through /wpp-login endpoint
 * You can disable this by adding:
 * add_filter('wpp_do_client_certificate_login', '__return_false');
 */
require_once(dirname( __FILE__ ) . '/modules/wpp-certificate-login.php');

