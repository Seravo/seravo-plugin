<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_carinbot() {
  //exec('wp-bxackup 2>&1', $output);
  return $output;
}

function seravo_ajax_carinbot() {
  check_ajax_referer( 'seravo_carinbot', 'nonce' );
  echo wp_json_encode(seravo_carinbot());

  wp_die();
}
