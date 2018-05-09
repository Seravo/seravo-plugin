<div class="wrap">

  <h1><?php _e('Seravo updates', 'seravo') ?></h1>

<?php

$site_info = Seravo\Updates::seravo_admin_get_site_info();

?>

<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
<div id="updates-settings_updated" class="updated settings-error notice is-dismissible">
<p><strong><?php _e('Settings saved.'); ?></strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.'); ?></span></button></div>
<?php } ?>

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

$contact_emails = array();
if ( isset($site_info['contact_emails']) ) {
  $contact_emails = $site_info['contact_emails'];
}

?>

  <p><?php _e('Seravo\'s upkeep service includes that your WordPress site is kept up-to-date with quick security updates and regular tested updates of both WordPress core and plugins. If you want full control of updates yourself, you can opt-out from Seravo updates.', 'seravo') ?></p>

  <form name="seravo_updates_form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
    <?php wp_nonce_field( 'seravo-updates-nonce' ); ?>
    <input type="hidden" name="action" value="toggle_seravo_updates">
    <input id="seravo_updates" name="seravo_updates" type="checkbox" <?php echo $checked; ?>> <?php _e('Seravo updates enabled', 'seravo') ?><br>

    <h2><?php _e('Technical contact email addresses', 'seravo') ?></h2>
    <p><?php _e('Seravo may send automatic notifications about site errors and failed updates to these addresses. Please separate multiple email addresses with commas.', 'seravo') ?></p>
    <input name="technical_contacts" type="email" multiple size="30" placeholder="<?php _e('example@example.com', 'seravo') ?>" value="<?php
if ( ! empty($contact_emails) ) {
  echo implode(', ', $contact_emails);
}
?>">

    <p><small class="seravo-developer-letter-hint">
      <?php echo sprintf( __('P.S. Subscribe to Seravo\'s %1$sNewsletter for WordPress Developers%2$s to get up-to-date information about our newest features.', 'seravo'), '<a href="https://seravo.com/newsletter-for-wordpress-developers/">', '</a>'); ?>
    </small></p>

    <input type="submit" id="save_settings_button" class="button button-primary" value="<?php _e('Save settings', 'seravo') ?>">
  </form>
</div>
