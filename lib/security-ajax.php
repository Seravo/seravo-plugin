<?php

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

/*
 * Custom function to read wp-login.log or previous versions of it in case
 * it rotated and older versions are needed to find last logins.
 *
 * The read_log_lines_backwards() from logs.php is not used as would not suffice.
 *
 * Returns IP, username and time of successful logins.
 * Number of results are limited by $max.
 */
function seravo_logins_info( $max = 10 ) {
  $logfile = dirname(ini_get('error_log')) . '/wp-login.log';
  if ( is_readable($logfile) ) {
    // Get the latest logins from wp-login.log
    $login_data = file($logfile);
  } else {
    $login_data = array();
  }

  $login_data = preg_grep('/SUCCESS/', $login_data);
  // If the wp-login.log has less than $max entries check older log files
  if ( count($login_data) < $max ) {
    // Check the second newest log file (not gzipped yet)
    $login_data2_filename = glob('/data/log/wp-login.log-[0-9]*[!\.gz]');
    // There should be only a maximum of one file matching previous criterion, but
    // count the files just in case and choose the biggest index
    $login_data2_count = count($login_data2_filename) - 1;
    // Merge log file if it exists
    if ( $login_data2_count >= 0 ) {
      // Merge with the first log file
      $login_data2 = file($login_data2_filename[$login_data2_count]);
      $login_data = array_merge(preg_grep('/SUCCESS/', $login_data2), $login_data);
    }

    // Opening necessary amount of gzipped log files
    // Find the gzip log files
    $login_data_gz_filename = glob('/data/log/wp-login.log-[0-9]*.gz');
    // Get the number of gzip log files
    // Using the count as an index to go through gzips starting from the newest
    $gz_count = count($login_data_gz_filename) - 1;
    // Opening gzips and merging to $login_data until enough logins or out of data
    $successful_logins_count = count(preg_grep('/SUCCESS/', $login_data));
    while ( $successful_logins_count < $max && $gz_count >= 0 ) {
      $zipped_data = preg_grep('/SUCCESS/', gzfile($login_data_gz_filename[ $gz_count ]));
      $login_data = array_merge($zipped_data, $login_data);
      --$gz_count;
    }
  }

  // Limit amount of login lines to $max
  $login_data = array_slice($login_data, -$max);

  // Clean up login lines, remove unnecessary characters
  $total_row_count = count($login_data);
  for ( $i = 0; $i < $total_row_count; $i++ ) {
    preg_match_all('/^(?<ip>[.:0-9a-f]+) - (?<name>[\w\-_.*@ ]+) \[(?<datetime>[\d\/\w: +]+)\]/', $login_data[ $i ], $matches);

    if ( isset($matches['ip'][0]) && isset($matches['name'][0]) && isset($matches['datetime'][0]) ) {
      // If valid line
      $timezone = get_option('timezone_string');
      $datetime = \DateTime::createFromFormat('d/M/Y:H:i:s T', $matches['datetime'][0]);
      $datetime->setTimezone(new \DateTimeZone(empty($timezone) ? 'UTC' : $timezone));
      $date = $datetime->format(get_option('date_format'));
      $time = $datetime->format(get_option('time_format'));

      // Fetch login IP and the reverse domain name
      $domain = gethostbyaddr($matches['ip'][0]);
      if ( empty($domain) ) {
        $address = $matches['ip'][0];
      } else {
        $address = $domain;
      }

      $login_data[ $i ] = '<tr>' .
        '<td class="seravo-tooltip" title="' . $date . ' ' . $time . '">' . $date . ' ' . $time . '</td>' .
        '<td class="seravo-tooltip" title="' . $matches['name'][0] . '">' . $matches['name'][0] . '</td>' .
        '<td class="seravo-tooltip" title="' . $address . '">' . $address . '</td>' .
        '</tr>';
    } else {
      // If invalid line
      unset($login_data[ $i ]);
    }
  }

  // Re-index the array after unsetting invalid lines
  $login_data = array_values($login_data);

  if ( empty($login_data) ) {
    $login_data = array( '<tr><td colspan="4">' . __('No login data', 'seravo') . '</td></tr>' );
  }

  // Adding column titles and table tags
  $column_titles = '<table class="login_info_table"><tr>' .
    '<th class="login_info_th">' . __('Time', 'seravo') . '</th>' .
    '<th class="login_info_th">' . __('User', 'seravo') . '</th>' .
    '<th class="login_info_th">' . __('Address', 'seravo') . '</th>' .
    '</tr>';

  $login_data = array_reverse($login_data);
  $login_data = array_merge(array( $column_titles ), $login_data);
  $login_data = array_merge($login_data, array( '</table>' ));

  return $login_data;
}

function seravo_check_passwords() {
  exec('wp-check-passwords', $output, $return_value);
  return array_map(
    function ( $x ) {
        return '<p>' . $x . '</p>';
    },
    $output
  );
}

function seravo_ajax_security() {
  check_ajax_referer('seravo_security', 'nonce');
  switch ( $_REQUEST['section'] ) {
    case 'logins_info':
      echo wp_json_encode(seravo_logins_info());
      break;
    case 'wp_check_passwords':
      echo wp_json_encode(seravo_check_passwords());
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}
