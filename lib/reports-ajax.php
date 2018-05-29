<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

use Seravo\Helpers;

function seravo_report_folders() {
  exec ('du -sb /data', $dataFolder);
  list($dataSize, $dataName) = preg_split('/\s+/', $dataFolder[0]);
  exec('du -sb /data/* | sort -hr', $dataSub);
  // Generate sub folder array
  $dataFolders = array();
  foreach ( $dataSub as $folder ) {
    list($folderSize, $folderName) = preg_split('/\s+/', $folder);
    $dataFolders[ $folderName ] = array(
      'percentage' => (($folderSize / $dataSize) * 100),
      'human' => Helpers::human_file_size($folderSize),
      'size' => $folderSize,
    );
  }
  // Create output array
  $output = array(
    'data' => array(
      'human' => Helpers::human_file_size($dataSize),
      'size' => $dataSize,
    ),
    'dataFolders' => $dataFolders,
  );
  return $output;
}

function seravo_report_wp_core_verify() {
  exec('wp core verify-checksums 2>&1', $output);
  array_unshift($output, '$ wp core verify-checksums');
  return $output;
}

function seravo_report_git_status() {
  exec('git -C /data/wordpress status', $output);

  if ( empty($output) ) {
    return array( 'Git is not used on this site. To start using it, read our documentation for WordPress developers at <a href="https://seravo.com/docs/">seravo.com/docs</a>.' );
  }

  array_unshift($output, '$ git status');
  return $output;
}

function seravo_report_redis_info() {
  exec('redis-cli info stats | grep keys', $output);
  return $output;
}

function seravo_report_front_cache_status() {
  exec('curl -ILk ' . get_site_url(), $output);
  array_unshift($output, '$ curl -ILk ' . get_site_url());

  if ( preg_match('/X-Proxy-Cache: ([A-Z]+)/', implode("\n", $output), $matches) ) {

    switch ( $matches[1] ) {
      case 'HIT':
      case 'EXPIRED':
        $result = 'Front cache is working correctly.';
        break;

      case 'MISS':
        $result = 'Front page is not cached due to cookies or expiry headers emitted from the site.';
        break;

      default:
        $result = 'Unable to detect front cache status.';
        break;
    }
  } else {
    $result = 'No front cache available in this WordPress instance.';
  }

  array_unshift($output, $result, '');

  return $output;
}

function seravo_ajax_reports() {
  switch ( $_REQUEST['section'] ) {
    case 'folders_chart':
      echo wp_json_encode(seravo_report_folders());
      break;

    case 'wp_core_verify':
      echo wp_json_encode(seravo_report_wp_core_verify());
      break;

    case 'git_status':
      echo wp_json_encode(seravo_report_git_status());
      break;

    case 'redis_info':
      echo wp_json_encode(seravo_report_redis_info());
      break;

    case 'front_cache_status':
      echo wp_json_encode(seravo_report_front_cache_status());
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}
