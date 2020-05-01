<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

use Seravo\Helpers;

function seravo_ajax_report_http_requests() {
  check_ajax_referer('seravo_site_status', 'nonce');
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
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  $stats = $redis->info('stats');

  $result = array(
    'Expired keys: ' . $stats['expired_keys'],
    'Evicted keys: ' . $stats['evicted_keys'],
    'Keyspace hits: ' . $stats['keyspace_hits'],
    'Keyspace misses: ' . $stats['keyspace_misses'],
  );

  $hits = $stats['keyspace_hits'];
  $misses = $stats['keyspace_misses'];

  if ( isset($hits) && isset($misses) ) {
    $total = $hits + $misses;
    if ( $total > 0 ) {
      array_push($result, 'Keyspace hit rate: ' . round(($hits / $total) * 100) . '%');
    }
  }

  return $result;
}

function seravo_report_longterm_cache_stats() {
  $access_logs = glob('/data/slog/*_total-access.log');

  $hit = 0;
  $miss = 0;
  $stale = 0;
  $bypass = 0;

  foreach ( $access_logs as $access_log ) {
    $file = fopen($access_log, 'r');
    if ( $file ) {
      while ( ! feof($file) ) {
        $line = fgets($file);
        if ( strpos($line, '"Seravo" HIT') ) {
          $hit++;
        } elseif ( strpos($line, '"Seravo" MISS') ) {
          $miss++;
        } elseif ( strpos($line, '"Seravo" STALE') ) {
          $stale++;
        } elseif ( strpos($line, '"Seravo" BYPASS') ) {
          $bypass++;
        }
      }
    }
  }

  $all_misses = $hit + $miss + $stale;
  if ( $all_misses == 0 ) {
    $all_misses = 1;
  }

  return array(
    'Hits: ' . $hit,
    'Misses: ' . $miss,
    'Stales: ' . $stale,
    'Bypasses: ' . $bypass,
    'Hit rate: ' . round($hit / $all_misses * 100) . '%',
  );
}

function seravo_report_front_cache_status() {
  exec('wp-check-http-cache ' . get_site_url(), $output);
  array_unshift($output, '$ wp-check-http-cache ' . get_site_url());

  return array(
    'success' => strpos(implode("\n", $output), "\nSUCCESS: ") == true,
    'test_result' => $output,
  );
}

function seravo_reset_shadow() {
  if ( isset($_POST['shadow']) && ! empty($_POST['shadow']) ) {
    $shadow = $_POST['shadow'];
    $output = array();
    // Check if the shadow is known
    foreach ( Seravo\API::get_site_data('/shadows') as $data ) {
      if ( $data['name'] == $shadow ) {
        exec('wp-shadow-reset ' . $shadow . ' --force 2>&1', $output);
        echo json_encode($output);
        wp_die();
      }
    }
  }
  wp_die();
}

function seravo_ajax_site_status() {
  check_ajax_referer('seravo_site_status', 'nonce');

  switch ( sanitize_text_field($_REQUEST['section']) ) {
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

    case 'longterm_cache':
      echo wp_json_encode(seravo_report_longterm_cache_stats());
      break;

    case 'front_cache_status':
      echo wp_json_encode(seravo_report_front_cache_status());
      break;

    case 'seravo_reset_shadow':
      seravo_reset_shadow();
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();

}
