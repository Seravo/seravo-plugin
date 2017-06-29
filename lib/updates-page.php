<div class="wrap">

  <h1><?php _e('Seravo updates', 'seravo') ?></h1>

<?php

$site_info = Seravo\Updates::seravo_admin_get_site_info();

?>

<h2><?php _e('Site status', 'seravo') ?></h2>
<ul>
  <li><?php _e('Site created', 'seravo') ?>: <?php echo $site_info['created']; ?></li>
  <li><?php _e('Latest update attempt', 'seravo') ?>: <?php echo $site_info['update_attempt']; ?></li>
  <li><?php _e('Latest successful update', 'seravo')?>: <?php echo $site_info['update_success']; ?></li>
</ul>


<h2><?php _e('Opt-out form updates by Seravo', 'seravo') ?></h2>
<?php
if ( $site_info['seravo_updates'] === true ) {
  $checked = 'checked="checked"';
} else {
  $checked = '';
}

// @TODO: Submit a nonce with the form if the amount of fields and inputs grows to protect users from XSS attacks.
?>

  <p><?php _e('Seravo\'s upkeep service includes that your WordPress site is kept up-to-date with quick security updates and regular tested updates of both WordPress core and plugins. If you want full control of updates yourself, you can opt-out from Seravo updates.', 'seravo') ?></p>

  <form name="toggle_seravo_updates" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
    <?php wp_nonce_field( 'toggle-seravo-updates-on-or-off' ); ?>
    <input type="hidden" name="action" value="toggle_seravo_updates">
    <input id="seravo_updates" name="seravo_updates" type="checkbox" <?php echo $checked; ?>> <?php _e('Seravo updates enabled', 'seravo') ?><br><br>
    <input type="submit" value="Save settings">
  </form>
</div>
