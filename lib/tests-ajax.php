<?php
/* Module which handles ajax requests for the test page */

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_tests() {
  exec('wp-test-ng', $output);
  return $output;
}

function seravo_tests_legacy() {
  exec('wp-test-legacy', $output);
  return $output;
}

function seravo_ajax_tests() {
  check_ajax_referer( 'seravo_tests', 'nonce' );
  switch ( $_REQUEST['section'] ) {
    case 'seravo_tests':
      echo wp_json_encode(seravo_tests());
      break;
    case 'seravo_tests_legacy':
      echo wp_json_encode(seravo_tests_legacy());
      break;
    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }
  wp_die();
}
