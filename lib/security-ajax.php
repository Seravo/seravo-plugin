<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

// Returns IP, username and time of successful logins.
// Number of results are limited by $max.
function seravo_logins_info( $max = 10 ) {
  // Get the latest logins from wp-loging.log
  $login_data = file('/data/log/wp-login.log');

  // If the wp-login.log has less than $max entries check older log files
  if ( count(preg_grep('/SUCCESS/', $login_data)) < $max ) {
    // Check the second newest log file (not gzipped yet)
    $login_data2_filename = glob('/data/log/wp-login.log-[0-9]*[?!\.gz]');
    // There should be only one file matching previous criterion, but
    // count the files just in case and choose the biggest index
    $login_data2_count = count($login_data2_filename) - 1;
    // Merge with the first log file
    $login_data = array_merge(file($login_data2_filename[ $login_data2_count ]), $login_data);

    // Opening necessary amount of gzipped log files
    // Find the gzip log files
    $login_data_gz_filename = glob('/data/log/wp-login.log-[0-9]*.gz');
    // Get the number of gzip log files
    // Using the count as an index to go through gzips starting from the newest
    $gz_count = count($login_data_gz_filename) - 1;
    // Opening gzips and merging to $login_data until enough logins or out of data
    $successful_logins_count = count(preg_grep('/SUCCESS/', $login_data));
    while ( $successful_logins_count < $max && $gz_count >= 0 ) {

      $login_data = array_merge(gzfile($login_data_gz_filename[ $gz_count ]), $login_data);
      --$gz_count;
    }
  }

  // Remove succesful login lines that exceed $max
  // Clean up succesful login lines, remove unnecessary characters
  // Remove failed logins
  $total_row_count = count($login_data);
  for ( $i = 0; $i < $total_row_count; $i++ ) {
    // Remove succesful login lines that exceed $max. The oldest lines are first.
    if ( strpos($login_data[ $i ], 'SUCCESS' ) && count(preg_grep('/SUCCESS/', $login_data)) > $max ) {
      unset($login_data[ $i ]);
    } else if ( strpos($login_data[ $i ], 'SUCCESS' ) ) {

      // Get the username. Username in log files between first "-" and "["
      $username_start = strpos($login_data[ $i ], '-') + 1;
      $username = substr($login_data[ $i ], $username_start, strpos($login_data[ $i ], '[') - $username_start );

      // Clean up succesful login lines, remove unnecessary characters
      $login_data[ $i ] = substr($login_data[ $i ], 0, strpos($login_data[ $i ], ' +0000]'));

      // Insert table elements to every row
      $login_data[ $i ] = '<tr><td>' . $login_data[ $i ] . '</td></tr>';

      // Log file data is in the format: IP - username [ date : time
      // Insert table elements in place of characters - [ :

      // Add username to tooltip. CSS ellipsis will shorten too long usernames
      $login_data[ $i ] = preg_replace('/ - /', '</td><td><div class="username_tooltip" title="' . $username . '">', $login_data[ $i ], 1);

      $login_data[ $i ] = preg_replace('/\[/', '</div></td><td>', $login_data[ $i ], 1);

      $login_data[ $i ] = preg_replace('/:/', '</td><td>', $login_data[ $i ], 1);

    } else {
      // Remove failed logins
      unset($login_data[ $i ]);
    }
  }
  // Re-index the array after unsetting failed logins
  $login_data = array_values($login_data);
  // Adding column titles and table tags
  $column_titles = '<table class="login_info_table"><tr>' .
    '<th class="login_info_th">' . __( 'IP address', 'seravo' ) . '</th>' .
    '<th class="login_info_th">' . __( 'User', 'seravo' ) . '</th>' .
    '<th class="login_info_th">' . __( 'Date', 'seravo' ) . '</th>' .
    '<th class="login_info_th">' . __( 'Time', 'seravo' ) . ' (UTC)</th></tr>';

  $login_data = array_merge(array( $column_titles ), $login_data);
  $login_data = array_merge($login_data, array( '</table>' ));

  return $login_data;
}

function seravo_ajax_security() {
  check_ajax_referer( 'seravo_security', 'nonce' );
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
