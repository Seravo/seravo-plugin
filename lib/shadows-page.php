<?php
/**
 * Outputs shadows page content.
 */

// Deny direct access
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname( __FILE__ ) . '/api.php';
?>

<div id="dashboard-widgets" class="metabox-holder">
  <!-- container  -->
  <div class="postbox-container-max">
    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
      <!--First postbox: Shadow Reset-->
      <div id="dashboard_shadow_reset" class="postbox">
        <button type="button" class="handlediv button-link" aria-expanded="true">
          <span class="screen-reader-text"><?php _e('Toggle panel:', 'seravo'); ?>
          <?php _e('Shadows (beta)', 'seravo'); ?>
          </span>
          <span class="toggle-indicator" aria-hidden="true"></span>
        </button>
        <h2 class="hndle ui-sortable-handle">
          <span>
          <?php _e('Shadows (beta)', 'seravo'); ?>
          </span>
        </h2>
        <div class="inside" style="padding: 0px">
          <div class="seravo-section">
            <div style="padding: 0px 15px">
              <p><?php _e('Allow easy access to site shadows. Resetting a shadow copies the state of the production site to the shadow. All files under /data/wordpress/ will be replaced and the production database imported. For more information, visit our  <a href="https://seravo.com/docs/deployment/shadows/">Developer documentation</a>.', 'seravo'); ?></p>
            </div>
            <div style="padding: 0px">
              <?php
              // Get a list of site shadows
              $api_query = '/shadows';
              $shadow_list = Seravo\API::get_site_data($api_query);
              if ( is_wp_error($shadow_list) ) {
                die($shadow_list->get_error_message());
              }

              // Order the shadow data so they correspond to the table labels.
              $shadow_data_ordered = array( 'info', 'name', 'ssh', 'created' );
              if ( ! empty($shadow_list) ) :
                ?>
              <table class="widefat striped fixed"
                style="width=100%; border:none" cellspacing="0">
                <thead>
                  <th><?php _e('Name', 'seravo'); ?></th>
                  <th><?php _e('Identifier', 'seravo'); ?></th>
                  <th><?php _e('SSH Port', 'seravo'); ?></th>
                  <th><?php _e('Creation Date', 'seravo'); ?></th>
                  <th><?php _e('Reset Shadow', 'seravo'); ?></th>
                </thead>
                <tbody>
                    <?php foreach ( $shadow_list as $shadow_data ) : ?>
                      <tr>
                        <?php foreach ( $shadow_data_ordered as $data_key ) : ?>
                        <td>
                          <?php if ( isset($shadow_data[ $data_key ]) ) : ?>
                            <?php echo $shadow_data[ $data_key ]; ?>
                          <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <td><button data-shadow-name="<?php echo $shadow_data['name']; ?>" class="shadow-reset button" type="button"><?php _e('Reset Shadow', 'seravo'); ?></button></td>
                      </tr>
                    <?php endforeach; ?>

                </tbody>
              </table>
              <?php else : ?>
                <p style="padding: 15px">
                  <?php _e('No shadows found. If your plan is WP Pro or higher, you can request a shadow instance from Seravo admins at <a href="mailto:wordpress@seravo.com">wordpress@seravo.com</a>.', 'seravo'); ?>
                </p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <!--First postbox: end-->
    </div>
  </div>
  <!-- end container -->
</div>
