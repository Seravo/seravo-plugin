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
          <span class="screen-reader-text">Toggle panel: <?php _e('Seravo updates', 'seravo'); ?></span>
          <span class="toggle-indicator" aria-hidden="true"></span>
        </button>
        <h2 class="hndle ui-sortable-handle">
          <span><?php _e('Seravo updates', 'seravo'); ?></span>
        </h2>
        <div class="inside seravo-updates-postbox">
          <?php
          //WP_error-object
          if ( gettype($site_info) === 'array' ) {
            ?>
              <h2><?php _e('Opt-out from updates by Seravo', 'seravo'); ?></h2>
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
            <p><?php _e('Seravo\'s upkeep service includes that your WordPress site is kept up-to-date with quick security updates and regular tested updates of both WordPress core and plugins. If you want full control of updates yourself, you can opt-out from Seravo updates.', 'seravo'); ?></p>
              <form name="seravo_updates_form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <?php wp_nonce_field( 'seravo-updates-nonce' ); ?>
                <input type="hidden" name="action" value="toggle_seravo_updates">
                <div class="checkbox allow_updates_checkbox">
                  <input id="seravo_updates" name="seravo_updates" type="checkbox" <?php echo $checked; ?>> <?php _e('Seravo updates enabled', 'seravo'); ?><br>
                </div>

                <hr class="seravo-updates-hr">
                <h2><?php _e('Update notifications Slack webhook', 'seravo'); ?></h2>
                <p><?php _e('If you define a Slack webhook address below, then Seravo can send notifications on every update, both successful and failed ones, to the Slack channel you define in the webhook.', 'seravo'); ?></p>
                <input name="slack_webhook" type="url" size="30" placeholder="https://hooks.slack.com/services/..." value="<?php echo $slack_webhook; ?>">
                <button type="button" class="button" id="slack_webhook_test"><?php _e('Send test notification', 'seravo'); ?></button>

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
          <span class="screen-reader-text">Toggle panel: <?php _e('Site status', 'seravo'); ?></span>
          <span class="toggle-indicator" aria-hidden="true"></span>
        </button>
        <h2 class="hndle ui-sortable-handle">
          <span>
            <span class="hide-if-no-js"><?php _e('Site status', 'seravo'); ?></span>
          </span>
        </h2>
        <div class="inside">
          <?php if ( gettype($site_info) === 'array' ) : ?>
          <ul>
            <li><?php _e('Site created', 'seravo'); ?>: <?php echo $site_info['created']; ?></li>
            <li><?php _e('Latest successful update', 'seravo'); ?>: <?php echo $site_info['update_success']; ?></li>
            <?php if ( ! empty( $site_info['update_attempt'] ) ) { ?>
            <li><?php _e('Latest update attempt', 'seravo'); ?>: <?php echo $site_info['update_attempt']; ?></li>'
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
    <th><' . __('Current site', 'seravo') . '</th>
    <th style="background-color: yellow;">' . __('Difference', 'seravo') . '</th>
    <th>' . __('Update shadow', 'seravo') . '</th>
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
    __('No screenshots found. They will become available during the next update test.', 'seravo') .
    '</td></tr>';

}
