<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_reset_shadow() {
  if ( isset($_POST['shadow']) && ! empty($_POST['shadow']) ) {
    $shadow = $_POST['shadow'];
    $output = array();
    // Check if the shadow is known
    foreach ( Seravo\API::get_site_data('/shadows') as $data ) {
      if ( $data['name'] == $shadow ) {
        exec('wp-shadow-reset ' . $shadow . ' --force 2>&1', $output);
        echo json_encode($output);
        wp_die();
      }
    }
  }
  wp_die();
}

function seravo_ajax_shadows() {
  check_ajax_referer('seravo_shadows', 'nonce');

  switch ( $_REQUEST['section'] ) {
    case 'seravo_reset_shadow':
      seravo_reset_shadow();
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();

}
