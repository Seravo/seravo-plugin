<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_reset_shadow() {
  check_ajax_referer( 'seravo_shadows', 'nonce' );
  if ( isset($_POST['resetshadow']) && ! empty($_POST['resetshadow']) ) {
    $shadow = $_POST['resetshadow'];
    $output = array();
    // check if the shadow is known
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
