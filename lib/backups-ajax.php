<?php

function seravo_backup_status() {
  exec("wp-backup-status 2>&1", $output);
  return $output;
}

function seravo_create_backup() {
  exec("wp-backup 2>&1", $output);
  return $output;
}

switch ($_REQUEST['section']) {
  case 'backup_status':
    echo json_encode(seravo_backup_status());
    break;

  case 'create_backup':
    echo json_encode(seravo_create_backup());
    break;

  default:
    error_log("ERROR: Section ". $_REQUEST['section'] ." not defined");
    break;
}
