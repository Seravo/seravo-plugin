<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_backup_status() {
  exec('wp-backup-status 2>&1', $output);
  return $output;
}

function seravo_backup_exclude() {
  exec('cat /data/backups/exclude.filelist', $output);
  return $output;
}
function seravo_create_backup() {
  exec('wp-backup 2>&1', $output);
  return $output;
}

function seravo_ajax_backups() {
  check_ajax_referer('seravo_backups', 'nonce');
  switch ( $_REQUEST['section'] ) {
    case 'backup_status':
      echo wp_json_encode(seravo_backup_status());
      break;

    case 'backup_exclude':
      echo wp_json_encode(seravo_backup_exclude());
      break;

    case 'create_backup':
      echo wp_json_encode(seravo_create_backup());
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}
