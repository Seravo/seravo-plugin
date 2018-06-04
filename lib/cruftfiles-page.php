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
          <!-- container 1 -->
          <div class="postbox-container-max">
            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
              <!--First postbox: Cruft remover-->
              <div id="dashboard_cruft_files" class="postbox">
                <button type="button" class="handlediv button-link" aria-expanded="true">
                  <span class="screen-reader-text">Toggle panel:
                    <?php _e( 'Cruft Files (beta)', 'seravo' ); ?>
                  </span>
                  <span class="toggle-indicator" aria-hidden="true"></span>
                </button>
                <h2 class="hndle ui-sortable-handle">
                  <span>
                    <?php _e( 'Cruft Files (beta)', 'seravo' ); ?>
                  </span>
                </h2>
                <div class="inside">
                  <div class="seravo-section">
                    <p>
                      <?php _e( 'Find and delete unnecessary files in the filesystem', 'seravo' ); ?>
                    </p>
                    <p>
                      <div id="cruftfiles_status">
                        <table>
                          <tbody id="cruftfiles_entries">
                          </tbody>
                        </table>
                        <div id="cruftfiles_status_loading">
                          <?php _e( 'Finding files...', 'seravo' ); ?>
                          <img src="/wp-admin/images/spinner.gif">
                        </div>
                      </div>
                    </p>
                  </div>
                </div>
              </div>
              <!--First postbox: end-->
            </div>
          </div>
          <!-- end 1 -->
        </div>
      </div>
    </div>
  </div>
