<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
?>

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
          <span class="screen-reader-text">Toggle panel: <?php _e('Seravo Updates', 'seravo'); ?></span>
          <span class="toggle-indicator" aria-hidden="true"></span>
        </button>
        <h2 class="hndle ui-sortable-handle">
          <span><?php _e('Seravo Updates', 'seravo'); ?></span>
        </h2>
        <div class="inside seravo-updates-postbox">
          <?php
          //WP_error-object
          if ( gettype($site_info) === 'array' ) {
            ?>
              <h2><?php _e('Automatic Updates by Seravo', 'seravo'); ?></h2>
            <?php
            if ( $site_info['seravo_updates'] === true ) {
              $checked = 'checked="checked"';
            } else {
              $checked = '';
            }

            if ( isset( $site_info['notification_webhooks'][0]['url'] ) &&
                 $site_info['notification_webhooks'][0]['type'] === 'slack' ) {
              $slack_webhook = $site_info['notification_webhooks'][0]['url'];
            } else {
              $slack_webhook = '';
            }

            $contact_emails = array();
            if ( isset($site_info['contact_emails']) ) {
              $contact_emails = $site_info['contact_emails'];
            }
            ?>
            <p><?php _e('The Seravo upkeep service includes automatic updates to your WordPress site, keeping your site current with security patches and frequent tested updates to both the WordPress core and plugins. If you want full control of updates to yourself, you should opt out from automatic updates by unchecking the checkbox below.', 'seravo'); ?></p>
              <form name="seravo_updates_form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <?php wp_nonce_field( 'seravo-updates-nonce' ); ?>
                <input type="hidden" name="action" value="toggle_seravo_updates">
                <div class="checkbox allow_updates_checkbox">
                  <input id="seravo_updates" name="seravo_updates" type="checkbox" <?php echo $checked; ?>> <?php _e('Automatic updates', 'seravo'); ?><br>
                </div>

                <hr class="seravo-updates-hr">
                <h2><?php _e('Update Notifications with a Slack Webhook', 'seravo'); ?></h2>
                <p><?php _e('By defining a Slack webhook address below, Seravo can send you notifications about every update attempt, whether successful or not, to the Slack channel you have defined in your webhook. <a href="https://api.slack.com/incoming-webhooks">Read more about webhooks</a>.', 'seravo'); ?></p>
                <input name="slack_webhook" type="url" size="30" placeholder="https://hooks.slack.com/services/..." value="<?php echo $slack_webhook; ?>">
                <button type="button" class="button" id="slack_webhook_test"><?php _e('Send a Test Notification', 'seravo'); ?></button>

                <hr class="seravo-updates-hr">
                <h2><?php _e('Technical Contacts', 'seravo'); ?></h2>
                <p><?php _e('Seravo may use the email addresses defined here to send automatic notifications about technical problems with you site. Remember to use a properly formatted email address.', 'seravo'); ?></p>
                <input class="technical_contacts_input" type="email" multiple size="30" placeholder="<?php _e('example@example.com', 'seravo'); ?>" value="" data-emails="<?php echo htmlspecialchars(json_encode($contact_emails)); ?>">
                <button type="button" class="technical_contacts_add button"><?php _e('Add', 'seravo'); ?></button>
                <span class="technical_contacts_error"><?php _e('Email must be formatted as name@domain.com', 'seravo'); ?></span>
                <input name="technical_contacts" type="hidden">
                <div class="technical_contacts_buttons"></div>
                <p><small class="seravo-developer-letter-hint">
                <?php
                  // translators: %1$s link to Newsletter for WordPress developers
                  printf( __('P.S. Subscribe to our %1$sNewsletter for WordPress Developers%2$s to get up-to-date information about our new features.', 'seravo'), '<a href="https://seravo.com/newsletter-for-wordpress-developers/">', '</a>');
                ?>
                </small></p>
                <br>
                <br>
                <input type="submit" id="save_settings_button" class="button button-primary" value="<?php _e('Save settings', 'seravo'); ?>">
              </form>
            <?php
          } else {
            echo $site_info->get_error_message();
          }
          ?>
        </div>
      </div>
    </div>
  </div>
  <div class="postbox-container">
    <div id="side-sortables" class="meta-box-sortables ui-sortable">
      <div id="dashboard_quick_press" class="postbox">
        <button type="button" class="handlediv button-link" aria-expanded="true">
          <span class="screen-reader-text">Toggle panel: <?php _e('Site Status', 'seravo'); ?></span>
          <span class="toggle-indicator" aria-hidden="true"></span>
        </button>
        <h2 class="hndle ui-sortable-handle">
          <span>
            <span class="hide-if-no-js"><?php _e('Site Status', 'seravo'); ?></span>
          </span>
        </h2>
        <div class="inside">
          <?php if ( gettype($site_info) === 'array' ) : ?>
          <ul>
            <li><?php _e('Site Created', 'seravo'); ?>: <?php echo date('Y-m-d', strtotime($site_info['created'])); ?></li>
            <li><?php _e('Latest Successful Update', 'seravo'); ?>: <?php echo date('Y-m-d', strtotime($site_info['update_success'])); ?></li>
            <?php if ( ! empty( $site_info['update_attempt'] ) ) { ?>
            <li><?php _e('Latest Update Attempt', 'seravo'); ?>: <?php echo date('Y-m-d', strtotime($site_info['update_attempt'])); ?></li>'
            <?php } ?>
          </ul>
            <?php
            else :
              echo $site_info->get_error_message();
              ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<h2 class="clear"><?php _e('Screenshots', 'seravo'); ?></h2>
<?php
$screenshots = glob( '/data/reports/tests/debug/*.png' );

# @TODO: Show as many screenshots as possible based on how many screenshot pairs
# are found, e.g. home.png / home.diff.png / home.shadow.png
if ( count($screenshots) > 3 ) {

  echo '
<table>
  <tr>
    <th><' . __('Current State', 'seravo') . '</th>
    <th style="background-color: yellow;">' . __('The Difference', 'seravo') . '</th>
    <th>' . __('Update Attempt', 'seravo') . '</th>
  </tr>
    <tbody  style="vertical-align: top; text-align: center;">';

  foreach ( $screenshots as $key => $screenshot ) {
    // Skip *.shadow.png files from this loop
    if ( strpos( $screenshot, '.shadow.png') || strpos( $screenshot, '.diff.png') ) {
      continue;
    }

    $name = substr( basename( $screenshot ), 0, -4);
    $diff_txt = file_get_contents( substr( $screenshot, 0, -4) . '.diff.txt' );
    if ( preg_match('/Total: ([0-9.]+)/', $diff_txt, $matches) ) {
      $diff = (float) $matches[1];
    }

    echo '
      <tr>
        <td>
          <a href="/.seravo/screenshots-ng/debug/' . $name . '.png">
            <img style="width: 30em" src="/.seravo/screenshots-ng/debug/' . $name . '.png">
          </a>
        </td>
        <td style="background-color: yellow;">
          <a href="/.seravo/screenshots-ng/debug/' . $name . '.diff.png">
            <img style="width: 30em" src="/.seravo/screenshots-ng/debug/' . $name . '.diff.png">
          </a>
          <br><span';

    // Make the difference number stand out if it is non-zero
    if ( $diff > 0.011 ) {
      echo ' style="color: red;"';
    }

    echo '>' . round( $diff * 100, 2 ) . ' %</span>
        </td>
        <td>
          <a href="/.seravo/screenshots-ng/debug/' . $name . '.shadow.png">
            <img style="width: 30em;" src="/.seravo/screenshots-ng/debug/' . $name . '.shadow.png">
          </a>
        </td>
      </tr>';
  }

  echo '
  </tbody>
</table>';

} else {

  echo '<tr><td colspan="3">' .
    __('No screenshots found. They will become available during the next attempted update.', 'seravo') .
    '</td></tr>';

}
