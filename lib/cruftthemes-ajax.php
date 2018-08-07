<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_ajax_list_cruft_themes() {
  check_ajax_referer( 'seravo_cruftthemes', 'nonce' );
  //get an array of WP_Theme -objects
  foreach ( wp_get_themes() as $theme ) {
    $output[] = array(
      'name' => $theme->get_stylesheet(),
      'title' => $theme->get( 'Name' ),
      'parent' => $theme->get( 'Template' ),
    );
  }
  set_transient('cruft_themes_found', $output, 600);
  echo wp_json_encode($output);
  wp_die();
}

function seravo_ajax_remove_themes() {
  check_ajax_referer( 'seravo_cruftthemes', 'nonce' );
  if ( isset($_POST['removetheme']) && ! empty($_POST['removetheme']) ) {
    $theme = $_POST['removetheme'];
    $legit_removeable_themes = get_transient('cruft_themes_found');
    foreach ( $legit_removeable_themes as $legit_theme ) {
      if ( $legit_theme['name'] == $theme ) {
        // (void|bool|WP_Error) When void, echoes content.
        echo json_encode(delete_theme($theme));
        wp_die();
      }
    }
  }
}
