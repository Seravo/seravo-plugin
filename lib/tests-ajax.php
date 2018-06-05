<?php
/* Module which handles ajax requests for the test page */

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_tests() {
  exec('wp-test', $output);
  return $output;
}

function seravo_ajax_tests() {
  switch ( $_REQUEST['section'] ) {
    case 'seravo_tests':
      echo wp_json_encode(seravo_tests());
      break;
    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }
  wp_die();
}
