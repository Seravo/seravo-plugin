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
  check_ajax_referer( 'seravo_backups', 'nonce' );
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

# currently testing this so that wp-backup-helper.py is located in htdocs/wordpress/wp-admin/
function seravo_ajax_backup_download() {
  check_ajax_referer( 'seravo_backups', 'nonce' );
  error_log($_REQUEST['increment']);
  exec('python3 wp-backup-helper.py ' . $_REQUEST['increment'] . ' zip 2>&1', $output );
  error_log(wp_json_encode($output));
  header('Content-Type: application/octet-stream');
  header('Content-Description: File Transfer');
  header('Content-Disposition: attachment; filename="' . $output . '";');
  echo $output;
  wp_die();
}
