<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
/*
function seravo_carinbot() {
  debug_to_console("seravo_carinbot funktio");
  exec('echo tuloste.seravo_carinbot 2>&1', $output);
  return $output;
}
*/
function seravo_ajax_carinbot() {
  debug_to_console("ajjjaxxx");
  //check_ajax_referer( 'seravo_carinbot', 'nonce' );
  //echo wp_json_encode(seravo_carinbot());
  //wp_die();
}

function debug_to_console( $data ) {
    $output = $data;
    if ( is_array( $output ) )
        $output = implode( ',', $output);

    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
}