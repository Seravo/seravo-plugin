<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
// [plugin, reason, size]
function seravo_ajax_list_cruft_plugins() {
  exec('wp plugin list --fields=name,title,status --format=json', $output);
  /* notes
  exec('wp site list --field=url | xargs -I % wp plugin list --url=% --fields=name,title,status --status=active --format=json');
  du -hs --apparent-size htdocs/wordpress/wp-content/plugins/
  //to check if the system has these
  //set_transient('cruft_files_found', $output, 600);
  */
  echo json_encode($output);
  wp_die();
}

function seravo_ajax_remove_plugins() {
  if ( isset($_POST['removeplugin']) && ! empty($_POST['removeplugin']) ) {
    $plugins = $_POST['removeplugin'];
    if ( is_string($plugins) ) {
      $plugins = array( $plugins );
    }
    if ( ! empty($plugins) ) {
      $result = array();
      foreach ( $plugins as $plugin ) {
        exec( 'wp plugin delete ' . $plugin, $output );
        //if ( $output )
      }
    }
  }
  echo json_encode($output);
  wp_die();
}
