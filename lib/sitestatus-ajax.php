<?php

namespace Seravo;

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
  $dir_max_limit = 1000000;
  $dir_threshold = 100000000;

  // Directories not counted against plan's quota but can be visible
  // in the front end
  $exclude_dirs = array(
    '--exclude=/data/backups',
    '--exclude=/data/log',
    '--exclude=/data/slog',
  );
  // Directories not shown in the front-end even if their size
  // exceed $dir_threshold. Produces a list string of the directories
  // in a format accepted by grep:  /data/dir_1\|/data/dir_1\| ...
  $hidden_dirs = implode(
    '\|',
    array(
      '/data/backups',
    )
  );

  // Get total disk usage
  exec('du -sb /data ' . implode(' ', $exclude_dirs), $data_folder);
  list($data_size, $data_name) = preg_split('/\s+/', $data_folder[0]);

  // Get the sizes of certain directories and directories with the
  // size larger than $dir_threshold, ones in $hidden_dirs will be
  // excluded from the output using grep
  exec(
    '(
    du --separate-dirs -b --threshold=' . $dir_threshold . ' /data/*/ &&
    du -sb /data/wordpress/htdocs/wp-content/uploads/ &&
    du -sb /data/wordpress/htdocs/wp-content/themes/ &&
    du -sb /data/wordpress/htdocs/wp-content/plugins/ &&
    du -sb /data/wordpress/htdocs/wordpress/wp-includes/ &&
    du -sb /data/wordpress/htdocs/wordpress/wp-admin/ &&
    du -sb /data/redis/ &&
    du -sb /data/reports/ &&
    du -sb /data/db/
    ) | grep -v "' . $hidden_dirs . '" | sort -hr',
    $data_sub
  );

  // Generate sub folder array
  $data_folders = array();
  foreach ( $data_sub as $folder ) {
    list($folder_size, $folder_name) = preg_split('/\s+/', $folder);

    if ( $folder_size > $dir_max_limit ) {
      $data_folders[ $folder_name ] = array(
        'percentage' => (($folder_size / $data_size) * 100),
        'human'      => Helpers::human_file_size($folder_size),
        'size'       => $folder_size,
      );
    }
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
      <a href="https://seravo.com/docs/" target="_BLANK">seravo.com/docs</a>.',
    );
  }

  array_unshift($output, '$ git status');
  return $output;
}

function seravo_report_redis_info() {
  $redis = new \Redis();
  $redis->connect('127.0.0.1', 6379);
  $stats = $redis->info('stats');

  $result = array(
    $stats['expired_keys'],
    $stats['evicted_keys'],
    $stats['keyspace_hits'],
    $stats['keyspace_misses'],
  );

  return $result;
}

function seravo_enable_object_cache() {
  $object_cache_url = 'https://raw.githubusercontent.com/Seravo/wordpress/master/htdocs/wp-content/object-cache.php';
  $object_cache_path = '/data/wordpress/htdocs/wp-content/object-cache.php';
  $result = array();

  // Remove all possible object-cache.php.* files
  foreach ( glob($object_cache_path . '.*') as $file ) {
    unlink($file);
  }

  // Get the newest file and write it
  $object_cache_content = file_get_contents($object_cache_url);
  $object_cache_file = fopen($object_cache_path, 'w');
  $write_object_cache = fwrite($object_cache_file, $object_cache_content);
  fclose($object_cache_file);

  if ( $write_object_cache && $object_cache_content ) {
    $result['success'] = true;
  } else {
    $result['success'] = false;
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
        // " is needed to match the log file
        if ( strpos($line, '" HIT') ) {
          $hit++;
        } elseif ( strpos($line, '" MISS') ) {
          $miss++;
        } elseif ( strpos($line, '" STALE') ) {
          $stale++;
        } elseif ( strpos($line, '" BYPASS') ) {
          $bypass++;
        }
      }
    }
  }

  return array(
    $hit,
    $miss,
    $stale,
    $bypass,
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
    foreach ( API::get_site_data('/shadows') as $data ) {
      if ( $data['name'] == $shadow ) {
        exec('wp-shadow-reset ' . $shadow . ' --force 2>&1', $output);
        echo json_encode($output);
        wp_die();
      }
    }
  }
  wp_die();
}

function seravo_site_checks() {
  $results = Site_Health::check_site_status();
  $issues = $results[0];
  $success = $results[1];

  return array(
    'success' => $success,
    'issues' => $issues,
  );
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

    case 'object_cache':
      echo wp_json_encode(seravo_enable_object_cache());
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

    case 'seravo_site_checks':
      echo wp_json_encode(seravo_site_checks());
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();

}

function seravo_speed_test() {
  check_ajax_referer('seravo_site_status', 'nonce');
  // Take location for the speed test from the ajax call. If there is not one, use WP home
  $url = isset($_POST['location']) ? get_home_url() . '/' . trim($_POST['location']) : get_home_url();

  // Make sure there is one / at the end of the url
  $url = rtrim($url, '/') . '/';

  // use filter_var to make sure the resulting url is a valid url
  if ( ! filter_var($url, FILTER_VALIDATE_URL) ) {
    wp_send_json_error(__('Error: Invalid url', 'seravo'));
  }

  // Check whether to test cached version or not. Default not.
  if ( isset($_POST['cached']) ) {
    $cached = $_POST['cached'] === 'true' ? true : false;
  } else {
    $cached = false;
  }

  // Prepare curl settings which are same for all requests
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // equals the command line -k option

  if ( ! $cached ) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Pragma: no-cache' ));
  }

  $response = curl_exec($ch);
  $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ( curl_error($ch) || $httpcode !== 200 ) {
    $error = __('Error! HTTP response code: ', 'seravo');
    wp_send_json_error($error . $httpcode);
  }
  $curl_info_arr = curl_getinfo($ch);

  $result= array(
    $curl_info_arr['total_time'],
    $curl_info_arr['namelookup_time'],
    $curl_info_arr['connect_time'],
    $curl_info_arr['pretransfer_time'],
    $curl_info_arr['starttransfer_time'],
  );

  // abort if total duration already over 120 seconds?

  wp_send_json_success($result);
  wp_die();
}
