<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

// Returns IP, username and time of successful logins.
// Number of results are limited by $max.
function seravo_logins_info( $max = 10 ) {
  $logfile = dirname(ini_get('error_log')) . '/wp-login.log';
  if ( is_readable($logfile) ) {
    // Get the latest logins from wp-login.log
    $login_data = file($logfile);
  } else {
    $login_data = array();
  }

  // If the wp-login.log has less than $max entries check older log files
  if ( count(preg_grep('/SUCCESS/', $login_data)) < $max ) {
    // Check the second newest log file (not gzipped yet)
    $login_data2_filename = glob('/data/log/wp-login.log-[0-9]*[?!\.gz]');
    // There should be only a maximum of one file matching previous criterion, but
    // count the files just in case and choose the biggest index
    $login_data2_count = count($login_data2_filename) - 1;
    // Merge log file if it exists
    if ( $login_data2_count >= 0 ) {
      // Merge with the first log file
      $login_data = array_merge(file($login_data2_filename[$login_data2_count]), $login_data);
    }
    $login_data = preg_grep('/SUCCESS/', $login_data);

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
      $datetime = DateTime::createFromFormat('d/M/Y:H:i:s T', $matches['datetime'][0]);
      $datetime->setTimezone(new DateTimeZone(date('T')));
      $date = $datetime->format(get_option('date_format'));
      $time = $datetime->format(get_option('time_format'));

      $login_data[ $i ] = '<tr>' .
        '<td class="seravo-tooltip" title="' . $matches['ip'][0] . '">' . $matches['ip'][0] . '</td>' .
        '<td class="seravo-tooltip" title="' . $matches['name'][0] . '">' . $matches['name'][0] . '</td>' .
        '<td class="seravo-tooltip" title="' . $date . '">' . $date . '</td>' .
        '<td class="seravo-tooltip" title="' . $time . '">' . $time . '</td>';
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
    '<th class="login_info_th">' . __('IP address', 'seravo') . '</th>' .
    '<th class="login_info_th">' . __('User', 'seravo') . '</th>' .
    '<th class="login_info_th">' . __('Date', 'seravo') . '</th>' .
    '<th class="login_info_th">' . __('Time', 'seravo') . ' (' . date('T') . ')</th></tr>';

  $login_data = array_reverse($login_data);
  $login_data = array_merge(array( $column_titles ), $login_data);
  $login_data = array_merge($login_data, array( '</table>' ));

  return $login_data;
}

function seravo_ajax_security() {
  check_ajax_referer('seravo_security', 'nonce');
  switch ( $_REQUEST['section'] ) {
    case 'logins_info':
      echo wp_json_encode(seravo_logins_info());
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}
