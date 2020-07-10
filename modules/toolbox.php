<?php

namespace Seravo;

if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('SeravoToolbox') ) {
  class SeravoToolbox {
    public static function load() {
      add_action('admin_menu', array( __CLASS__, 'register_toolbox_subpages' ));
    }

    public static function register_toolbox_subpages() {
      add_menu_page(
        __('Site Toolkit', 'toolkit_index_page'),
        'Site Toolkit',
        'manage_options',
        'toolkit_index_page',
        'Seravo\seravo_postboxes_page',
        'dashicons-admin-generic',
        999
      );

      add_submenu_page(
        'toolkit_index_page',
        __('Site Status', 'seravo'),
        __('Site Status', 'seravo'),
        'manage_options',
        'toolkit_index_page',
        'Seravo\seravo_postboxes_page'
      );

      add_submenu_page(
        'toolkit_index_page',
        __('Upkeep', 'seravo'),
        __('Upkeep', 'seravo'),
        'manage_options',
        'upkeep_page',
        'Seravo\seravo_two_column_postboxes_page'
      );

      add_submenu_page(
        'toolkit_index_page',
        __('Database', 'seravo'),
        __('Database', 'seravo'),
        'manage_options',
        'database_page',
        'Seravo\seravo_postboxes_page'
      );

      // Only show the menu item on systems where wp-backup is available
      if ( exec('which wp-backup-status') ) {
        add_submenu_page(
          'toolkit_index_page',
          __('Backups', 'seravo'),
          __('Backups', 'seravo'),
          'manage_options',
          'backups_page',
          'Seravo\seravo_two_column_postboxes_page'
        );
      }

      add_submenu_page(
        'toolkit_index_page',
        __('Security', 'seravo'),
        __('Security', 'seravo'),
        'manage_options',
        'security_page',
        'Seravo\seravo_postboxes_page'
      );

      if ( getenv('WP_ENV') === 'production' ) {
        add_submenu_page(
          'toolkit_index_page',
          __('Domains', 'seravo'),
          __('Domains', 'seravo'),
          'manage_options',
          'domains_page',
          'Seravo\seravo_wide_column_postboxes_page'
        );
      }

      add_submenu_page(
        'toolkit_index_page',
        __('Logs', 'seravo'),
        __('Logs', 'seravo'),
        'manage_options',
        'logs_page',
        array( Logs::init(), 'render_tools_page' )
      );
    }
  }

  SeravoToolbox::load();
}
