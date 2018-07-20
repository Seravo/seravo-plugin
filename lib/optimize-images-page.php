<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
?>

<div id="dashboard-widgets" class="metabox-holder">
  <!-- container 1 -->
  <div class="postbox-container-max">
    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
      <!--First postbox: Image optimizer-->
      <div id="dashboard_optimize_images" class="postbox">
        <button type="button" class="handlediv button-link" aria-expanded="true">
          <span class="screen-reader-text">Toggle panel: <?php _e(  'Optimize Images (beta)', 'seravo' ); ?></span>
          <span class="toggle-indicator" aria-hidden="true"></span>
        </button>
        <h2 class="hndle ui-sortable-handle">
          <span><?php _e('Optimize Images (beta)', 'seravo'); ?></span>
        </h2>

        <div class="inside">
          <div class="seravo-section">
            <?php
            settings_errors();
            echo '<form method="post" action="options.php" class="seravo-general-form">';
            settings_fields( 'seravo-optimize-images-settings-group' );
            do_settings_sections( 'optimize_images_settings' );
            submit_button( __( 'Save', 'seravo' ), 'primary', 'btnSubmit' );
            echo '</form>';
            ?>
          </div>
        </div>
      </div>
      <!--First postbox: end-->
    </div>
  </div>
  <!-- end 1 -->
</div>
