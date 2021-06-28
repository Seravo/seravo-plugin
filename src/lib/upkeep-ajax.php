<?php
/* Module which handles ajax requests for the updates page */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_plugin_upstream_version() {
  $upstream_version = get_transient('seravo_plugin_upstream_version');
  if ( $upstream_version === false || empty($upstream_version) ) {
    $upstream_version = exec('curl -s https://api.github.com/repos/seravo/seravo-plugin/tags | grep "name" -m 1 | awk \'{gsub("\"","")}; {gsub(",","")}; {print $2}\'');
    set_transient('seravo_plugin_upstream_version', $upstream_version, 10800);
  }

  return $upstream_version;
}

function seravo_default_config_file() {
  exec('grep -l "^set \$mode php" /data/wordpress/nginx/*.conf | LC_COLLATE=C sort | tail -1', $config_file);
  $config_file = $config_file[0];
  $configs_array = array( '/data/wordpress/nginx/custom.conf', '/data/wordpress/nginx/examples.conf', '/data/wordpress/nginx/php.conf' );
  return in_array($config_file, $configs_array);
}

function seravo_check_php_config_files() {
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

  // Filter out command prompt stylings
  $pattern = '/\x1b\[[0-9;]*m/';
  $output = preg_replace($pattern, '', $output);
  return array(
    'test_result' => $output,
    'exit_code' => $return_variable,
  );
}

function seravo_changes_since() {
  $date = $_POST['date'];
  $result_count = '';

  // Try catch to check if the date is
  try {
    $formal_date = new \DateTime($date);
    unset($formal_date);
  } catch ( \Exception $exception ) {
    $datenow = getdate();
    $y = $datenow['year'];
    $m = $datenow['mon'];

    if ( $datenow['mday'] >= 3 ) {
      $d = $datenow['mday'] - 2;
      $result_count = __('Invalid date, using 2 days offset <br><br>', 'seravo');
    } else {
      // Show since the month beginning
      $d = 1;
      $result_count = __('Invalid date, showing since month beginning <br><br>', 'seravo');
    }

    $date = $y . '-' . $m . '-' . $d;
  }

  $cmd = 'wp-backup-list-changes-since ' . $date;
  $result_count .= exec($cmd . ' | wc -l');
  exec($cmd, $output);

  return array(
    'rowCount' => $result_count,
    'output' => $output,
  );
}

function seravo_ajax_upkeep( $date ) {
  check_ajax_referer('seravo_upkeep', 'nonce');
  switch ( sanitize_text_field($_REQUEST['section']) ) {

    case 'seravo_default_config_file':
      echo seravo_default_config_file();
      break;

    case 'seravo_tests':
      echo wp_json_encode(seravo_tests());
      break;

    case 'seravo_changes':
      echo wp_json_encode(seravo_changes_since());
      break;

    default:
      error_log('ERROR: Section ' . sanitize_text_field($_REQUEST['section']) . ' not defined');
      break;
  }

  wp_die();
}
