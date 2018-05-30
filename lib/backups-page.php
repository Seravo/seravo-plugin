<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
?>

<div id="wpbody" role="main">
  <div id="wpbody-content" aria-label="Main content" tabindex="0">
    <div class="wrap">
      <div id="dashboard-widgets" class="metabox-holder">
        <div class="postbox-container">
          <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <!-- first -->
            <div id="dashboard_right_now" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: <?php _e('Backups', 'seravo'); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span><?php _e('Backups', 'seravo'); ?></span>
              </h2>
              <div class="inside">
                <p><?php _e('Backups are made automatically every night and preserved for 30 days. The data can be accessed on the server at <code>/data/backups</code>.', 'seravo') ?></p>
              </div>
          </div>
          <!-- first ends -->

          <!-- second -->
          <div id="dashboard_activity" class="postbox">
            <button type="button" class="handlediv button-link" aria-expanded="true">
              <span class="screen-reader-text">Toggle panel: <?php _e('Create a new backup', 'seravo'); ?></span>
              <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
            <h2 class="hndle ui-sortable-handle">
              <span><?php _e('Create a new backup', 'seravo'); ?></span>
            </h2>
            <div class="inside">
              <p><?php _e('You can also create backups using the command line tool <code>wp-backup</code>. We recommend getting familiar with the command line option accessible via SSH so that recovering a backup is not dependant on if WP-admin works or not.', 'seravo') ?></p>
              <p class="create_backup">
                <button id="create_backup_button" class="button"><?php _e('Make a new backup', 'seravo') ?> </button>
                <div id="create_backup_loading"><img class="hidden" src="/wp-admin/images/spinner.gif"></div>
                <pre><div id="create_backup"></div></pre>
              </p>
            </div>
          </div>
          <!-- second ends -->

          <!-- third -->
          <div id="dashboard_quick_press" class="postbox">
            <button type="button" class="handlediv button-link" aria-expanded="true">
              <span class="screen-reader-text">Toggle panel: <?php _e('Files excluded from backups', 'seravo'); ?></span>
              <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
            <h2 class="hndle ui-sortable-handle">
              <span><?php _e('Files excluded from backups', 'seravo'); ?></span>
            </h2>
            <div class="inside">
              <?php echo wp_sprintf( __('Below is the content of the file %s.', 'seravo'), '<code>/data/backups/exclude.filelist</code>' ); ?>
              <p>
                <div id="backup_exclude_loading">
                  <img src="/wp-admin/images/spinner.gif">
                </div>
                <pre id="backup_exclude"></pre>
              </p>
            </div>
          </div>
          <!-- third ends -->
        </div>
      </div>

      <div class="postbox-container">
        <div id="side-sortables" class="meta-box-sortables ui-sortable">
          <!-- fourth -->
          <div id="dashboard_primary" class="postbox">
              <button type="button" class="handlediv button-link" aria-expanded="true">
                <span class="screen-reader-text">Toggle panel: <?php _e( 'Current backups', 'seravo' ); ?></span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
              <h2 class="hndle ui-sortable-handle">
                <span><?php _e( 'Current backups', 'seravo' ); ?></span>
              </h2>
              <div class="inside">
                <?php echo wp_sprintf( __('This listing is produced by command %s.', 'seravo'), '<code>wp-backup-status</code>' ); ?>
                <p>
                  <div id="backup_status_loading"><img src="/wp-admin/images/spinner.gif"></div>
                  <pre id="backup_status"></pre>
                </p>
                <!-- fourth ends -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
