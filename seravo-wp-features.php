<?php
/**
 * Plugin Name: Seravo WordPress-palvelu
 * Description: Enables some Wordpress-palvelu specific features
 */

/*
 * This is used to add notifications
 */
function _seravo_notification() {

  // get notification
  if ( false === ( $notification = get_transient( 'seravo_notification' ) ) || ( isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache' ) ) {
    $notification = file_get_contents('https://wp-palvelu.seravo.fi/ilmoitus/');
    set_transient( 'seravo_notification', $notification, HOUR_IN_SECONDS );
  }

  if (!empty($notification)) {
  ?>
    <div class="updated fade">
      <p><?php echo $notification; ?></p>
    </div>
  <?php
  }
}
add_action('admin_notices', '_seravo_notification');
