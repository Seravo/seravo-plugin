<?php
/* Module which handles ajax requests for the test page */

function seravo_tests() {
  exec('wp-test', $output);
  return $output;
}

switch ( $_REQUEST['section'] ) {
  case 'seravo_tests':
    echo wp_json_encode(seravo_tests());
    break;
  default:
    error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
    break;
}
