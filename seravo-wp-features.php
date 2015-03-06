<?php
/**
 * Plugin Name: Seravo WP-palvelu
 * Plugin URI: https://github.com/Seravo/Seravo-Wordpress-Features
 * Description: Enables some Wordpress-palvelu specific features
 * Author: Seravo Oy
 */

/*
 * This is used to add notifications
 */
add_action('admin_notices', '_seravo_notification');
function _seravo_notification() {

  // get notification
  if ( false === ( $notification = get_transient( 'seravo_notification' ) ) || ( isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache' ) ) {
    $notification = file_get_contents('https://wp-palvelu.seravo.fi/ilmoitus/');
    // allow some html tags but strip most
    $notification = strip_tags( trim($notification),"<br><br/><a><b><i>" );
    set_transient( 'seravo_notification', $notification, HOUR_IN_SECONDS );
  }
  if (!empty($notification) ) {
  ?>
    <div class="updated fade">
      <p><?php echo $notification; ?></p>
    </div>
  <?php
  }
}

/*
 * Hide all core update nagging. We will handle updates for the clients.
 */
add_action('admin_menu','_seravo_hide_update_nag');
function _seravo_hide_update_nag() {
  remove_action( 'admin_notices', 'update_nag', 3 );
}

/*
 * Hide red dots from nagging. We will handle updates for the clients.
 */
add_filter('wp_get_update_data', '_seravo_hide_update_data');
function _seravo_hide_update_data($update_data, $titles='') {
  return array (
    'counts' => array(
      'plugins' => 0,
      'themes' => 0,
      'wordpress' => 0,
      'translations' => 0,
      'total' => 0
    ),
    'title' => ''
  );
}