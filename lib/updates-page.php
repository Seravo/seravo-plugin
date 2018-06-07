<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
?>
<div id="wpbody" role="main">
  <div id="wpbody-content" aria-label="Main content" tabindex="0">
    <div class="wrap">
      <?php
      $site_info = Seravo\Updates::seravo_admin_get_site_info();
      ?>
      <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
      <div id="updates-settings_updated" class="updated settings-error notice is-dismissible">
        <p>
          <strong><?php _e('Settings saved.'); ?></strong>
        </p>
        <button type="button" class="notice-dismiss">
          <span class="screen-reader-text"><?php _e('Dismiss this notice.'); ?></span>
        </button>
      </div>
      <?php } ?>
      <div id="dashboard-widgets" class="metabox-holder">
        <div class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="dashboard_right_now" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: <?php _e('Seravo updates', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span><?php _e('Seravo updates', 'seravo'); ?></span>
              </h2>
              <div class="inside seravo-updates-postbox">
                <h2><?php _e('Opt-out form updates by Seravo', 'seravo'); ?></h2>

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

                <p><?php _e('Seravo\'s upkeep service includes that your WordPress site is kept up-to-date with quick security updates and regular tested updates of both WordPress core and plugins. If you want full control of updates yourself, you can opt-out from Seravo updates.', 'seravo'); ?></p>

                <form name="seravo_updates_form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                  <?php wp_nonce_field( 'seravo-updates-nonce' ); ?>
                  <input type="hidden" name="action" value="toggle_seravo_updates">
                  <div class="checkbox allow_updates_checkbox">
                    <input id="seravo_updates" name="seravo_updates" type="checkbox" <?php echo $checked; ?>> <?php _e('Seravo updates enabled', 'seravo'); ?><br>
                  </div>
                  <hr class="seravo-updates-hr">
                  <h2><?php _e('Technical contact email addresses', 'seravo'); ?></h2>
                  <p><?php _e('Seravo may send automatic notifications about site errors and failed updates to these addresses. Remember to add @-character to email.', 'seravo'); ?></p>
                  <input class="technical_contacts_input" type="email" multiple size="30" placeholder="<?php _e('example@example.com', 'seravo'); ?>" value="" data-emails="<?php echo htmlspecialchars(json_encode($contact_emails)); ?>">
                  <button type="button" class="technical_contacts_add button"><?php _e('Add', 'seravo'); ?></button>
                  <span class="technical_contacts_error"><?php _e('Email must be formatted as name@domain.com', 'seravo'); ?></span>
                  <input name="technical_contacts" type="hidden">
                  <div class="technical_contacts_buttons"></div>
                  <p><small class="seravo-developer-letter-hint">
                    <?php
                      // translators: %1$s link to Newsletter for WordPress developers
                      echo wp_sprintf( __('P.S. Subscribe to Seravo\'s %1$sNewsletter for WordPress Developers%2$s to get up-to-date information about our newest features.', 'seravo'), '<a href="https://seravo.com/newsletter-for-wordpress-developers/">', '</a>');
                    ?>
                  </small></p>
                  <input type="submit" id="save_settings_button" class="button button-primary" value="<?php _e('Save settings', 'seravo'); ?>">
                </form>
              </div>
            </div>
          </div>
        </div>
        <div class="postbox-container">
          <div id="side-sortables" class="meta-box-sortables ui-sortable">
            <div id="dashboard_quick_press" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: <?php _e('Site status', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span>
                  <span class="hide-if-no-js"><?php _e('Site status', 'seravo'); ?></span>
                </span>
              </h2>
              <div class="inside">
                <ul>
                  <li><?php _e('Site created', 'seravo'); ?>: <?php echo $site_info['created']; ?></li>
                  <li><?php _e('Latest update attempt', 'seravo'); ?>: <?php echo $site_info['update_attempt']; ?></li>
                  <li><?php _e('Latest successful update', 'seravo'); ?>: <?php echo $site_info['update_success']; ?></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
