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

<div id="wpbody" role="main">
  <div id="wpbody-content" aria-label="Main content" tabindex="0">
    <div class="wrap">
      <h1><?php _e('Shadows (beta)', 'seravo'); ?></h1>
      <p><?php _e('Allow easy access to site shadows.', 'seravo'); ?></p>

      <?php
      // Get a list of site shadows
      $api_query = '/shadows';
      $shadow_list = Seravo\API::get_site_data($api_query);
      if ( is_wp_error($shadow_list) ) {
        die($shadow_list->get_error_message());
      }

      // Order the shadow data so they correspond to the table labels.
      $shadow_data_ordered = array('name', 'ssh', 'created', 'info');
      ?>

      <table class="wp-list-table widefat striped" cellspacing="0">
        <thead>
          <th><?php _e('Name', 'seravo'); ?></th>
          <th><?php _e('SSH port', 'seravo'); ?></th>
          <th><?php _e('Creation date', 'seravo'); ?></th>
          <th><?php _e('Information', 'seravo'); ?></th>
        </thead>

        <tbody>
          <?php if ( ! empty($shadow_list) ) : ?>
            <?php foreach ( $shadow_list as $shadow_data ) : ?>
              <tr>
                <?php foreach ( $shadow_data_ordered as $data_key ) : ?>
                  <?php if ( isset($shadow_data[$data_key]) ) : ?>
                    <th><?php echo $shadow_data[$data_key]; ?></th>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php else : ?>
            <?php _e('No shadows found. If your plan is WP Pro or higher, you can request a shadow instance from Seravo admins at wordpress@seravo.com.', 'seravo'); ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
