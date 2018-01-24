<?php

function find_cruft_file($name) {
  exec('find /data/wordpress -name ' . $name, $output);
  return $output;
}

function find_cruft_dir($name) {
  exec('find /data/wordpress -type d -name ' . $name, $output);
  return $output;
}

function list_known_cruft_file($name) {
  exec('ls ' . $name, $output);
  return $output;
}

function list_known_cruft_dir($name) {
  exec('ls -d ' . $name, $output);
  return $output;
}

switch ( $_REQUEST['section'] ) {
  case 'cruftfiles_status':

    $list_files = array('*.sql', '.hhvm.hhbc', '*.wpress'); // List of known types of cruft files
    $list_dirs = array('siirto', 'palautus', 'vanha', '*-old', '*-copy', '*-2', '*.bak', 'migration'); // List of known cruft directories
    $list_known_files = array();
    $list_known_dirs = array(
      '/data/wordpress/htdocs/wp-content/plugins/all-in-one-wp-migration/storage',
      '/data/wordpress/htdocs/wp-content/ai1wm-backups',
      '/data/wordpress/htdocs/wp-content/uploads/backupbuddy_backups',
      '/data/wordpress/htdocs/wp-content/updraft'
    );

    $crufts = array();
    foreach($list_files as $filename) {
      $cruft_found = find_cruft_file($filename);
      if ($cruft_found != "") array_push($crufts, $cruft_found);
    }
    foreach($list_dirs as $dirname) {
      $cruft_found = find_cruft_dir($dirname);
      if ($cruft_found != "") array_push($crufts, $cruft_found);
    }
    foreach($list_known_files as $dirname) {
      $cruft_found = list_known_cruft_file($dirname);
      if ($cruft_found != "") array_push($crufts, $cruft_found);
    }
    foreach($list_known_dirs as $dirname) {
      $cruft_found = list_known_cruft_dir($dirname);
      if ($cruft_found != "") array_push($crufts, $cruft_found);
    }
    set_transient('cruft_files_found', $crufts, 600);

    echo wp_json_encode($crufts);
    break;

  default:
    error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
    break;
}
