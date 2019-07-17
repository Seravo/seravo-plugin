<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_ajax_list_cruft_themes() {
  check_ajax_referer('seravo_cruftthemes', 'nonce');
  if ( is_multisite() ) {
    if ( wp_is_large_network() ) {
      // Can't get the needed data for large network (1000+ sites)
      delete_transient('cruft_themes_found');
      echo wp_json_encode(array());
      wp_die();
    } else {
      // Gets all active themes across the sites
      $active_themes = array();
      foreach ( get_sites() as $site ) {
        switch_to_blog($site->blog_id);
        $theme = wp_get_theme();
        if ( ! in_array($theme, $active_themes) ) {
          array_push($active_themes, $theme->get_stylesheet());
        }
        restore_current_blog();
      }
    }
  } else {
    $active_themes = array( wp_get_theme()->get_stylesheet() );
  }
  // Get an array of WP_Theme -objects
  foreach ( wp_get_themes() as $theme ) {
    $output[] = array(
      'name'   => $theme->get_stylesheet(),
      'title'  => $theme->get('Name'),
      'parent' => $theme->get('Template'),
      'active' => (in_array($theme->get_stylesheet(), $active_themes)),
    );
  }
  set_transient('cruft_themes_found', $output, 600);
  echo wp_json_encode($output);
  wp_die();
}

function seravo_ajax_remove_themes() {
  check_ajax_referer('seravo_cruftthemes', 'nonce');
  if ( isset($_POST['removethemes']) ) {
    $themes = json_decode(stripslashes($_POST['removethemes']));
    $legit_removeable_themes = get_transient('cruft_themes_found');
    if ( ! empty($themes) && $legit_removeable_themes !== false ) {
      $response = array();
      foreach ( $themes as $theme ) {
        $deleted = false;
        foreach ( $legit_removeable_themes as $legit_theme ) {
          if ( $legit_theme['name'] == $theme && ! $legit_theme['active'] ) {
            // (void|bool|WP_Error) When void, echoes content.
            $deleted = delete_theme($theme);
            continue;
          }
        }
        $response[ $theme ] = $deleted;
      }
      echo json_encode($response);
    }
  }
  wp_die();
}

