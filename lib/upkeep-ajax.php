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
    '7.4' => '7.4',
  );

  if ( array_key_exists($php_version, $php_version_array) ) {
    file_put_contents('/data/wordpress/nginx/php.conf', 'set $mode php' . $php_version_array[ $php_version ] . ';' . PHP_EOL);
    // NOTE! The exec below must end with '&' so that subprocess is sent to the
    // background and the rest of the PHP execution continues. Otherwise the Nginx
    // restart will kill this PHP file, and when this PHP files dies, the Nginx
    // restart will not complete, leaving the server state broken so it can only
    // recover if wp-restart-nginx is run manually.
    exec('echo "--> Setting to mode ' . $php_version_array[ $php_version ] . '" >> /data/log/php-version-change.log');
    exec('wp-restart-nginx >> /data/log/php-version-change.log 2>&1 &');
  }

  if ( is_executable('/usr/local/bin/s-git-commit') && file_exists('/data/wordpress/.git') ) {
    exec('cd /data/wordpress/ && git add nginx/*.conf && /usr/local/bin/s-git-commit -m "Set new PHP version" && cd /data/wordpress/htdocs/wordpress/wp-admin');
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
  $current_version = Seravo\Helpers::seravo_plugin_version();

  if ( $current_version == seravo_plugin_upstream_version() ) {
    return true;
  } else {
    return false;
  }
}

function seravo_plugin_upstream_version() {
  $upstream_version = get_transient('seravo_plugin_upstream_version');
  if ( $upstream_version === false || empty($upstream_version) ) {
    $upstream_version = exec('curl -s https://api.github.com/repos/seravo/seravo-plugin/tags | grep "name" -m 1 | awk \'{gsub("\"","")}; {gsub(",","")}; {print $2}\'');
    set_transient('seravo_plugin_upstream_version', $upstream_version, 10800);
  }

  return $upstream_version;
}

function seravo_plugin_version_update() {
  exec('wp-seravo-plugin-update &');
}

function seravo_check_php_compatibility() {
  exec('wp-php-compatibility-check | grep "FOUND.*ERRORS AFFECTING.*" | awk \'{ print $2 }\'', $output, $return_value);
  $return_arr = array(
    'output' => $output,
    'exit_code' => $return_value,
  );
  return $return_arr;
}

function seravo_default_config_file() {
  exec('grep -l "^set \$mode php" /data/wordpress/nginx/*.conf | LC_COLLATE=C sort | tail -1', $config_file);
  $config_file = $config_file[0];
  $configs_array = array( '/data/wordpress/nginx/custom.conf', '/data/wordpress/nginx/examples.conf', '/data/wordpress/nginx/php.conf' );

  if ( ! in_array($config_file, $configs_array) ) {
    return false;
  }

  return true;
}

function check_php_config_files() {
  $dir = '/data/wordpress/nginx';

  exec('grep -l "^set \$mode php" ' . $dir . '/*.conf | tail -1', $config_file);
  array( exec('grep -l "^set \$mode php" ' . $dir . '/*.conf --exclude=php.conf | LC_COLLATE=C sort', $files) );

  $config_file = $config_file[0];

  foreach ( $files as $file ) {
    $lines = file($file);

    foreach ( $lines as &$line ) {
      if ( substr($line, 0, strlen('set $mode php')) === 'set $mode php' ) {
        $line = '#' . $line;
        file_put_contents($file, $lines);
      }
    }
  }
}

function seravo_tests() {
  exec('wp-test', $output, $return_variable);
  $return_arr = array(
    'test_result' => $output,
    'exit_code' => $return_variable,
  );
  return $return_arr;
}

function seravo_ajax_upkeep() {
  check_ajax_referer('seravo_upkeep', 'nonce');
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

    case 'seravo_plugin_version_update':
      echo seravo_plugin_version_update();
      break;

    case 'seravo_check_php_compatibility':
      echo wp_json_encode(seravo_check_php_compatibility());
      break;

    case 'seravo_default_config_file':
      echo seravo_default_config_file();
      break;

    case 'check_php_config_files':
      echo check_php_config_files();
      break;

    case 'seravo_tests':
      echo wp_json_encode(seravo_tests());
      break;

    default:
      error_log('ERROR: Section ' . sanitize_text_field($_REQUEST['section']) . ' not defined');
      break;
  }

  wp_die();
}
