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
require_once dirname(__FILE__) . '/../modules/instance-switcher.php';

if ( ! class_exists('Shadows') ) {
  class Shadows {

    public static function load() {
      add_action('admin_menu', array( __CLASS__, 'register_shadows_page' ));
      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_shadows_scripts' ));
      add_action('wp_ajax_seravo_ajax_shadows', 'seravo_ajax_shadows');

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
          'success'    => __('Success!', 'seravo'),
          'failure'    => __('Failure!', 'seravo'),
          'error'      => __('Error!', 'seravo'),
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
          <hr>
        </div>
        <div style="padding: 5px 15px 0 15px">
          <?php
          // Get a list of site shadows
          $api_query = '/shadows';
          $shadow_list = API::get_site_data($api_query);
          if ( is_wp_error($shadow_list) ) {
            die($shadow_list->get_error_message());
          }

          if ( ! empty($shadow_list) ) {
          ?>
            <table id="shadow-table">
              <tr>
                <th><?php _e('Name', 'seravo'); ?></th>
                <td id="shadow-name">
                  <select id="shadow-selector">
                    <option value="" disabled selected hidden><?php _e('Select shadow', 'seravo'); ?></option>
                    <?php
                    foreach ( $shadow_list as $shadow => $shadow_data ) {
                      $shadow_list[$shadow]['domain'] = InstanceSwitcher::get_shadow_domain($shadow_data['name']);
                      printf('<option value="%s">%s</option>', $shadow_data['name'], $shadow_data['info']);
                    }
                    ?>
                  </select>
                </td>
              </tr>
              <?php
              function add_shadow( $identifier, $ssh, $created, $domain, $hidden = true ) {
              ?>
                <tbody data-shadow="<?php echo $identifier; ?>" data-domain="<?php echo $domain; ?>" class="<?php echo ($hidden ? 'shadow-hidden ' : ''); ?>shadow-row">
                  <tr><th><?php _e('Identifier', 'seravo'); ?></th><td><?php echo $identifier; ?></td></tr>
                  <tr><th><?php _e('SSH Port', 'seravo'); ?></th><td><?php echo $ssh; ?></td></tr>
                  <tr><th><?php _e('Domain', 'seravo'); ?></th><td><?php echo (empty($domain) ? '-' : $domain); ?></td></tr>
                  <tr><th><?php _e('Creation Date', 'seravo'); ?></th><td><?php echo $created; ?></td></tr>
                  <?php
                  if ( ! empty($identifier) ) {
                  ?>
                    <tr class="data-actions"><th><?php _e('Actions', 'seravo'); ?></th><td><a class="action-link closed" href=""><?php _e('Move Data', 'seravo'); ?><span></span></a></td></tr>
                    <?php
                  }
                    ?>
                </tbody>
                <?php
              }
              // One empty entry for 'select shadow' in dropdown
              add_shadow('', '', '', ' ', false);
              foreach ( $shadow_list as $shadow_data ) {
                add_shadow($shadow_data['name'], $shadow_data['ssh'], $shadow_data['created'], $shadow_data['domain']);
              }
              ?>
            </table>
            <?php
          } else {
            ?>
          <p style="padding: 15px 15px 0 15px;">
            <?php _e('No shadows found. If your plan is WP Pro or higher, you can request a shadow instance from Seravo admins at <a href="mailto:wordpress@seravo.com"    >wordpress@seravo.com</a>.', 'seravo'); ?>
          </p>
            <?php
          }
            ?>
        </div>
        <div id="shadow-data-actions" class="shadow-hidden">
          <hr>
          <h3 style="margin-top:20px;"><?php _e('Reset shadow from production', 'seravo'); ?></h3>
          <i>production > <span id="shadow-reset-instance">shadow</span></i>
          <p><?php _e('<b>Warning:</b> This will replace everything currently in the <i>/data/wordpress/</i> directory and the database of the shadow with a copy of production site. Be sure to know what you are doing.', 'seravo'); ?></p>
          <form>
            <input type="hidden" name="shadow-reset-production" value="<?php echo str_replace([ 'https://', 'http://' ], '://', get_home_url()); ?>">
            <table id="shadow-rs-table">
              <tr><th colspan="2"><input type="checkbox" name="shadow-reset-sr" disabled><?php _e('Execute search-replace', 'seravo'); ?></th></tr>
              <tr><td>From: </td><td><input type="text" name="shadow-reset-sr-from" disabled></td></tr>
              <tr><td>To: </td><td><input type="text" name="shadow-reset-sr-to" disabled></td></tr>
            </table>
          </form>
          <div id="shadow-reset-sr-alert" class="shadow-hidden">
            <?php _e("This shadow uses a custom domain. Search-replace can't currently be ran automatically with shadow reset. Please run it manually afterwards with the values above or the shadow can't be accessed. Instructions can be found in <a href='https://help.seravo.com/en/docs/151'>here</a>.", 'seravo'); ?> 
          </div>
          <div id="shadow-reset-nosr-alert">
            <?php _e("This shadow doesn't need search-replace to be ran afterwards for it to work.", 'seravo'); ?> 
          </div>
          <table class="shadow-reset-row">
            <tr>
              <td><button class="button" id="shadow-reset"><?php _e('Reset from production', 'seravo'); ?></button></td>
              <td id="shadow-reset-status"></td>
            </tr>
          </table>
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
