<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_pull_shadow() {
  check_ajax_referer( 'seravo_shadows', 'nonce' );
  if ( isset($_POST['pullshadow']) && ! empty($_POST['pullshadow']) ) {
    $shadow = $_POST['pullshadow'];
    $output = array();
    // check if the shadow is known
    foreach ( Seravo\API::get_site_data('/shadows') as $data ) {
      if ( $data['name'] === $shadow ) {
        exec('wp-shadow-pull ' . $shadow . ' --force 2>&1', $output);
        echo wp_json_encode($output);
        wp_die();
      }
    }
  }
  wp_die();
}

function seravo_reset_shadow() {
  check_ajax_referer( 'seravo_shadows', 'nonce' );
  if ( isset($_POST['resetshadow']) && ! empty($_POST['resetshadow']) ) {
    $shadow = $_POST['resetshadow'];
    $output = array();
    // check if the shadow is known
    foreach ( Seravo\API::get_site_data('/shadows') as $data ) {
      if ( $data['name'] === $shadow ) {
        exec('wp-shadow-reset ' . $shadow . ' --force 2>&1', $output);
        echo wp_json_encode($output);
        wp_die();
      }
    }
  }
  wp_die();
}
