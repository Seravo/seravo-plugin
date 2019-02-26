<?php
/* Module which handles ajax requests for the updates page */

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_change_php_version() {
  $php_version_string = ( '"set \$mode php' . $_REQUEST['version'] . ';"' );
  exec('echo ' . $php_version_string . ' | tee /data/wordpress/nginx/php_version.conf');
  exec('wp-restart-nginx');
  exec('wp-restart-php');
  exec('wp-purge-cache');
}

function seravo_php_check_version() {
  $current_php_version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

  if ( $current_php_version == $_REQUEST['version'] ) {
    return true;
  } else {
    return false;
  }
}

function seravo_ajax_updates() {
  check_ajax_referer( 'seravo_updates', 'nonce' );
  switch ( $_REQUEST['section'] ) {
    case 'seravo_change_php_version':
      seravo_change_php_version();
      break;

    case 'seravo_php_check_version':
      echo seravo_php_check_version();
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}
