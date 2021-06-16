<?php
/*
 * Plugin name: Backups
 * Description: Enable users to list and create backups
 * Version: 1.0
 */

namespace Seravo;

use Seravo\Postbox\Component;
use Seravo\Postbox\Postbox;
use Seravo\Postbox\Requirements;
use Seravo\Postbox\Template;
use Seravo\Postbox\Toolpage;

require_once dirname(__FILE__) . '/../lib/backups-ajax.php';
require_once dirname(__FILE__) . '/postbox-tools.php';

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Backups') ) {
  class Backups {

    public static function load() {
      // Add AJAX endpoint for backups
      add_action('wp_ajax_seravo_backups', 'Seravo\seravo_ajax_backups');

      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_backups_scripts' ));

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook(__FILE__, array( __CLASS__, 'register_view_backups_capability' ));

      /* \Seravo\Postbox\seravo_add_raw_postbox(
        'backups-info',
        __('Backups', 'seravo'),
        array( __CLASS__, 'backups_info_postbox' ),
        'tools_page_backups_page',
        'normal'
      );

      \Seravo\Postbox\seravo_add_raw_postbox(
        'backups-create',
        __('Create a New Backup', 'seravo'),
        array( __CLASS__, 'backups_create_postbox' ),
        'tools_page_backups_page',
        'normal'
      );

      \Seravo\Postbox\seravo_add_raw_postbox(
        'backups-excludes',
        __('Files Excluded from the Backups', 'seravo'),
        array( __CLASS__, 'backups_excludes_postbox' ),
        'tools_page_backups_page',
        'side'
      );

      \Seravo\Postbox\seravo_add_raw_postbox(
        'backups-list',
        __('Current Backups', 'seravo'),
        array( __CLASS__, 'backups_list_postbox' ),
        'tools_page_backups_page',
        'side'
      ); */

      $page = new Toolpage('tools_page_backups_page');
      $test_widget = new Postbox('backup-widget', 'normal');
      $test_widget->set_title('Test widget');
      //$test_widget->set_data_func(array( __CLASS__, 'backups_info_postbox' ));
      $test_widget->set_build_func(array( __CLASS__, 'my_test_builder' ));
      $test_widget->set_requirements(
        array(
          Requirements::CAN_BE_PRODUCTION => true,
        )
      );

      $page->enable_ajax();
      $page->register_postbox($test_widget);
      $page->register_page();

    }

    public static function my_test_builder( Component $base, $data ) {
      $base->set_wrapper('<h3>', '</h3>');
      $base->add_child(Template::error_paragraph('Error haatana'));
      $base->add_child(Template::section_title('Test widget'));
      $base->add_child(Template::spinner('my_spinner'));
      $base->add_child(Template::tooltip('My tooltip'));
      $base->add_child(Template::success_failure(true));
      $base->add_child(Template::success_failure(false));
    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function register_backups_scripts( $page ) {
      wp_register_script('seravo_backups', plugin_dir_url(__DIR__) . '/js/backups.js', '', Helpers::seravo_plugin_version());
      wp_register_style('seravo_backups', plugin_dir_url(__DIR__) . '/style/backups.css', '', Helpers::seravo_plugin_version());
      wp_register_style('seravo_common', plugin_dir_url(__DIR__) . '/style/common.css', '', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_backups_page' ) {
        wp_enqueue_script('seravo_backups');
        wp_enqueue_style('seravo_backups');
        wp_enqueue_style('seravo_common');

        $loc_translation_backups = array(
          'ajaxurl'    => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('seravo_backups'),
          'no_entries' => __('No entries were found', 'seravo'),
        );
        wp_localize_script('seravo_backups', 'seravo_backups_loc', $loc_translation_backups);
      }

    }

    public static function backups_info_postbox() {
      ?>
      <p><?php _e('Backups are automatically created every night and preserved for 30 days. The data can be accessed on the server in under <code>/data/backups</code>.', 'seravo'); ?></p>
      <?php
    }

    public static function backups_create_postbox() {
      ?>
      <p><?php _e('You can also create backups manually by running <code>wp-backup</code> on the command line. We recommend that you get familiar with the command line option that is accessible to you via SSH. That way recovering a backup will be possible whether the WP Admin is accessible or not.', 'seravo'); ?></p>
      <p class="create_backup">
        <button id="create_backup_button" class="button-primary"><?php _e('Create a backup', 'seravo'); ?> </button>
        <div id="create_backup_loading"><img class="hidden" src="/wp-admin/images/spinner.gif"></div>
        <pre><div id="create_backup"></div></pre>
      </p>
      <?php
    }

    public static function backups_excludes_postbox() {
      // translators: %s name of the file shown
      printf(__('Below are the contents of %s.', 'seravo'), '<code>/data/backups/exclude.filelist</code>');
      ?>
      <p>
        <div id="backup_exclude_loading">
          <img src="/wp-admin/images/spinner.gif">
        </div>
        <pre id="backup_exclude"></pre>
      </p>
      <?php
    }

    public static function backups_list_postbox() {
      // translators: %s command used to list WordPress backups of the website
      printf(__('This list is produced by the command %s.', 'seravo'), '<code>wp-backup-status</code>');
      ?>
      <p>
        <div id="backup_status_loading"><img src="/wp-admin/images/spinner.gif"></div>
        <pre id="backup_status"></pre>
      </p>
      <?php
    }

    public static function display_test_widget() {
      echo Postbox_Tools::section_title('My section');
      echo Postbox_Tools::text('On this section you can test the components');
      echo Postbox_Tools::result_wrapper('test-wrapper', 'My result');
      echo Postbox_Tools::paragraph('My content test');
    }
  }

  Backups::load();
}
