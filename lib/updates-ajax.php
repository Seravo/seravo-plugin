<?php
/* Module which handles ajax requests for the updates page */

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_change_php_version() {
  $php_version = sanitize_text_field($_REQUEST['version']);

  $php_version_array = array(
    '5.6' => '5',
    '7.0' => '7.0',
    '7.2' => '7.2',
    '7.3' => '7.3',
  );

  if ( array_key_exists($php_version, $php_version_array) ) {
    file_put_contents( '/data/wordpress/nginx/php.conf', 'set $mode php' . $php_version_array[ $php_version ] . ';' . PHP_EOL);
    // NOTE! The exec below must end with '&' so that subprocess is sent to the
    // background and the rest of the PHP execution continues. Otherwise the Nginx
    // restart will kill this PHP file, and when this PHP files dies, the Nginx
    // restart will not complete, leaving the server state broken so it can only
    // recover if wp-restart-nginx is run manually.
    exec('echo "--> Setting to mode ' . $php_version_array[ $php_version ] .
         '" >> /data/log/php-version-change.log');
    exec('wp-restart-nginx >> /data/log/php-version-change.log 2>&1 &');
  }
}

function seravo_php_check_version() {
  $current_php_version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

  if ( $current_php_version == sanitize_text_field($_REQUEST['version']) ) {
    return true;
  } else {
    return false;
  }
}

function seravo_plugin_version_check() {
  error_log('Start');
  $current_version = Seravo\Helpers::seravo_plugin_version();
  $upstream_version = exec('curl -s https://api.github.com/repos/seravo/seravo-plugin/tags | python3 -c "import sys, json; print(json.load(sys.stdin)[0][\'tarball_url\'])" | rev | cut -d / -f -1 | rev');

  error_log("Current version: " . $current_version . " Latest version: " . $upstream_version);

  if ( $upstream_version == $current_version ) {
    return true;
  } else {
    return false;
  }
}

function seravo_ajax_updates() {
  check_ajax_referer( 'seravo_updates', 'nonce' );
  switch ( sanitize_text_field($_REQUEST['section']) ) {
    case 'seravo_change_php_version':
      echo seravo_change_php_version();
      break;

    case 'seravo_php_check_version':
      echo seravo_php_check_version();
      break;

    case 'seravo_plugin_version_check':
      echo seravo_plugin_version_check();
      break;

    default:
      error_log('ERROR: Section ' . sanitize_text_field($_REQUEST['section']) . ' not defined');
      break;
  }

  wp_die();
}
