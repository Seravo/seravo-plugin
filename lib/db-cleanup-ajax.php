<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_db_cleanup( $options ) {
  $command = 'wp-db-cleanup ';
  $output = array();

  $command .= seravo_db_cleanup_set_flags($options);

  if ( $options['dry_run'] === 'false' ) {
    $backup = 'wp-backup';
    array_push($output, '<b>$ ' . $backup . '</b>');
    exec($backup . ' 2>&1', $output, $return_code);
  }
  // Only way this is not true, is if the backups fail
  if ( $options['dry_run'] === 'true' || $return_code === 0 ) {
    array_push($output, '<b>$ ' . $command . '</b>');
    $dry_run = exec($command . ' 2>&1', $output);

    if ( empty($dry_run) ) {
      $output[$command] = __('Nothing to be cleaned up', 'seravo');
    }
  } else {
    array_push($output, 'Backup failed... Aborting');
  }
  return $output;
}

function seravo_db_cleanup_set_flags( $options ) {
  $flags = '';
  if ( ! isset($options['dry_run']) || $options['dry_run'] === 'true' ) {
    $flags .= '--dry-run ';
  } else {
    $flags .= '--delay 0 ';
  }

  return $flags;
}

function seravo_ajax_db_cleanup() {
  check_ajax_referer('seravo_database', 'nonce');
  switch ( $_REQUEST['section'] ) {
    case 'db_cleanup':
      $db_cleanup_result = seravo_db_cleanup($_REQUEST['options']);
      echo wp_json_encode($db_cleanup_result);
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}
