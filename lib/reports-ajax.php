<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

use Seravo\Helpers;

function seravo_ajax_report_http_requests() {
  check_ajax_referer('seravo_reports', 'nonce');
  $reports = glob('/data/slog/html/goaccess-*.html');
  // Create array of months with total request sums
  $months = array();
  // Track max request value to calculate relative bar widths
  $max_requests = 0;
  foreach ( $reports as $report ) {
    $total_requests_string = exec("grep -oE 'total_requests\": ([0-9]+),' $report");
    preg_match('/([0-9]+)/', $total_requests_string, $total_requests_match);
    $total_requests = intval($total_requests_match[1]);
    if ( $total_requests > $max_requests ) {
      $max_requests = $total_requests;
    }
    array_push(
      $months,
      array(
        'date'     => substr($report, 25, 7),
        'requests' => $total_requests,
      )
    );
  }
  if ( count($months) > 0 ) {
    array_push(
      $months,
      array(
        'max_requests' => $max_requests,
      )
    );
  }
  echo wp_json_encode($months);
  wp_die();
}


function seravo_report_folders() {
  exec('du -sb /data', $data_folder);
  list($data_size, $data_name) = preg_split('/\s+/', $data_folder[0]);
  exec('du -sb /data/* | sort -hr', $data_sub);
  // Generate sub folder array
  $data_folders = array();
  foreach ( $data_sub as $folder ) {
    list($folder_size, $folder_name) = preg_split('/\s+/', $folder);
    $data_folders[ $folder_name ] = array(
      'percentage' => (($folder_size / $data_size) * 100),
      'human'      => Helpers::human_file_size($folder_size),
      'size'       => $folder_size,
    );
  }
  // Create output array
  $output = array(
    'data'        => array(
      'human' => Helpers::human_file_size($data_size),
      'size'  => $data_size,
    ),
    'dataFolders' => $data_folders,
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
    return array(
      'Git is not used on this site. To start using it,
      read our documentation for WordPress developers at
      <a href="https://seravo.com/docs/">seravo.com/docs</a>.',
    );
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
  check_ajax_referer('seravo_reports', 'nonce');
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
