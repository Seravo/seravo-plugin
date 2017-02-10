<?php
/**
 * Plugin name: Seravo Purge Cache
 * Description: Purges the Seravo cache
 */

/**
 * Add a purge button in the WP Admin Bar
 */
add_action( 'admin_bar_menu', '_seravo_purge_button', 999 );
function _seravo_purge_button( $admin_bar ) {

  // check permissions
  if ( !current_user_can( 'manage_options' ) ) {
    return;
  }

  /*
   * Add 'Purge cache' button to menu
   */
  $purge_url = add_query_arg( 'seravo_purge_cache', '1' );
  $admin_bar->add_menu( array(
    'id' => 'nginx-helper-purge-all',
    'title' => '<span class="ab-icon"></span><span title="'.
    sprintf(__('Seravo.com uses front proxies to deliver lightning fast responses to your visitors. Cached pages will be refreshed every %s. This button is used for clearing all cached pages from the frontend proxy immediately.', 'seravo'), "15 min").
    '" class="ab-label">'.__('Purge Cache', 'seravo')."</span>",
    'href' => wp_nonce_url( $purge_url, '_seravo_purge', '_seravo_nonce' ),
  ));

  /*
   * Add style snippet in context of adminbar
   */
  ?>
  <style type="text/css" media="screen">
    #wpadminbar #wp-admin-bar-nginx-helper-purge-all .ab-item .ab-icon:before {
      content: "\f463";
      top: 3px;
    }
  </style>
  <?php
}

/**
 * Purge the cache via REQUEST parameters
 */
add_action( 'admin_init', '_maybe_seravo_purge_cache' );
function  _maybe_seravo_purge_cache() {

  // check permissions
  if ( !current_user_can( 'manage_options' ) ) {
    return;
  }

  if( isset($_REQUEST['seravo_purge_cache']) ) {

    // check nonce
    if (!isset($_GET['_seravo_nonce']) || !wp_verify_nonce($_GET['_seravo_nonce'], '_seravo_purge')) {
      return;
    }

    // purge the cache
    $response = _seravo_purge_cache();
    error_log( "NOTICE: Cache flush initiated from admin: \n" . $response );

    // redirect to the original siteurl with notification
    $redirect_url = remove_query_arg( array( 'seravo_purge_cache', '_seravo_nonce' ) );
    $redirect_url = add_query_arg( 'seravo_purge_success', 1, $redirect_url );
    wp_redirect($redirect_url);

    die();
  }
}

/**
 * Displays the cache purged notification
 */
add_action( 'admin_notices', '_seravo_purge_notification' );
function _seravo_purge_notification() {

  // check permissions
  if ( !current_user_can( 'manage_options' ) ) {
    return;
  }

  // check to see if we should show notification
  if(!isset($_REQUEST['seravo_purge_success'])) {
    return;
  }

  ?>
  <div class="notice updated is-dismissible">
      <p><strong><?php _e( 'Success:' ); ?></strong> <?php _e( 'The cache was flushed' ); ?> <button type="button" class="notice-dismiss"></button></p>
  </div>
  <?php
}

/**
 * Purges the cache
 */
function _seravo_purge_cache() {

  // send a purge request to the downstream server
  $ch = curl_init( get_site_url( null, '/purge/' ) );
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $return = curl_exec($ch);

  return $return;
}
