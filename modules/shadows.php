<?php
/*
 * Plugin name: Shadows
 * Description: Add a page to list shadows and transfer data between them and
 * production.
 * TODO: Should we also prevent the loading of this module in WP Network sites
 * to prevent disaster?
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname(__FILE__) . '/../lib/helpers.php';
require_once dirname(__FILE__) . '/../lib/shadows-ajax.php';

if ( ! class_exists('Shadows') ) {
  class Shadows {

    public static function load() {
      add_action('admin_menu', array( __CLASS__, 'register_shadows_page' ));
      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_shadows_scripts' ));
      add_action('wp_ajax_seravo_reset_shadow', 'seravo_reset_shadow');

      seravo_add_postbox(
        'shadows',
        __('Shadows', 'seravo') . ' (beta)',
        array( __CLASS__, 'seravo_shadows_postbox' ),
        'tools_page_shadows_page',
        'normal'
      );
    }

    public static function register_shadows_page() {
      add_submenu_page(
        'tools.php',
        __('Shadows', 'seravo'),
        __('Shadows', 'seravo'),
        'manage_options',
        'shadows_page',
        'Seravo\seravo_postboxes_page'
      );

    }

    public static function register_shadows_scripts( $page ) {
      wp_register_style('seravo_shadows', plugin_dir_url(__DIR__) . '/style/shadows.css', '', Helpers::seravo_plugin_version());
      wp_register_script('seravo_shadows', plugin_dir_url(__DIR__) . '/js/shadows.js', '', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_shadows_page' ) {
        wp_enqueue_style('seravo_shadows');
        wp_enqueue_script('seravo_shadows');

        $loc_translation = array(
          'success'    => __('Success', 'seravo'),
          'failure'    => __('Failure', 'seravo'),
          'error'      => __('Error', 'seravo'),
          'confirm'    => __('Are you sure? This replaces all information in the selected environment.', 'seravo'),
          'ajaxurl'    => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('seravo_shadows'),
        );

        wp_localize_script('seravo_shadows', 'seravo_shadows_loc', $loc_translation);
      }
    }

    public static function seravo_shadows_postbox() {
      ?>
      <div class="seravo-section">
        <div style="padding: 0px 15px">
          <p><?php _e('Allow easy access to site shadows. Resetting a shadow copies the state of the production site to the shadow. All files under /data/wordpress/ will be replaced and the production database imported. For more information, visit our  <a href="https://seravo.com/docs/deployment/shadows/">Developer documentation</a>.', 'seravo'); ?></p>
        </div>
        <div style="padding: 0px">
          <?php
          // Get a list of site shadows
          $api_query = '/shadows';
          $shadow_list = API::get_site_data($api_query);
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
      <?php
    }
  }

  // Only show shadows overview in production
  if ( Helpers::is_production() ) {
    Shadows::load();
  }
}
