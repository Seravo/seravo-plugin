<?php
/* Module which handles ajax requests for the updates page */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_tests() {
  exec('wp-test', $output, $return_variable);

  // Filter out command prompt stylings
  $pattern = '/\x1b\[[0-9;]*m/';
  $output = preg_replace($pattern, '', $output);
  return array(
    'test_result' => $output,
    'exit_code' => $return_variable,
  );
}

function seravo_ajax_upkeep( $date ) {
  check_ajax_referer('seravo_upkeep', 'nonce');
  switch ( sanitize_text_field($_REQUEST['section']) ) {

    case 'seravo_default_config_file':
      echo seravo_default_config_file();
      break;

    case 'seravo_tests':
      echo wp_json_encode(seravo_tests());
      break;

    default:
      error_log('ERROR: Section ' . sanitize_text_field($_REQUEST['section']) . ' not defined');
      break;
  }

  wp_die();
}
