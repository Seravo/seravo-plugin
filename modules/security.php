<?php
/*
 * Plugin name: Security settings
 * Description: Enable users to set the maximum security settings for their site.
 * Version: 1.0
 *
 * NOTE! For more fine-grained XML-RPC control, use https://wordpress.org/plugins/manage-xml-rpc/
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Security') ) {
  class Security {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_security_page' ) );
      add_action( 'admin_init', array( __CLASS__, 'register_security_settings' ) );

      seravo_add_postbox(
        'security_info',
        __('Security (beta)', 'seravo'),
        array( __CLASS__, 'security_info_postbox' ),
        'tools_page_security_page',
        'normal'
      );

    }

    public static function register_security_page() {
      add_submenu_page(
        'tools.php',
        __( 'Security', 'seravo' ),
        __( 'Security', 'seravo' ),
        'manage_options',
        'security_page',
        'Seravo\seravo_postboxes_page'
      );
    }

    public static function register_security_settings() {
      add_settings_section(
        'seravo_security_settings',
        '', // Empty on purpose, postbox title is enough
        array( __CLASS__, 'security_settings_description' ),
        'tools_page_security_page'
      );

      /* Dummy settings that cannot be changed */
      add_settings_field(
        'seravo-automatic-backups',
        __( 'Automatic backups', 'seravo' ),
        array( __CLASS__, 'seravo_security_checked_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      add_settings_field(
        'seravo-security-updates',
        __( 'Quick security updates', 'seravo' ),
        array( __CLASS__, 'seravo_security_checked_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      add_settings_field(
        'seravo-malicious-code-monitoring',
        __( 'Monitoring of malicius code and database contents', 'seravo' ),
        array( __CLASS__, 'seravo_security_checked_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      add_settings_field(
        'seravo-dos-protection',
        __( 'Denial-of-service protection', 'seravo' ),
        array( __CLASS__, 'seravo_security_checked_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      add_settings_field(
        'seravo-brute-force-protection',
        __( 'Brute-force login protection', 'seravo' ),
        array( __CLASS__, 'seravo_security_checked_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      /* Real settings below */
      add_settings_field(
        'seravo-disable-xml-rpc',
        __( 'Disable XML-RPC', 'seravo' ),
        array( __CLASS__, 'seravo_security_xmlrpc_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      add_settings_field(
        'seravo-disable-json-user-enumeration',
        __( 'Disable WP-JSON user enumeration', 'seravo' ),
        array( __CLASS__, 'seravo_security_json_user_enum_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      add_settings_field(
        'seravo-disable-get-author-enumeration',
        __( 'Disable GET author enumeration', 'seravo' ),
        array( __CLASS__, 'seravo_security_get_author_enum_field' ),
        'tools_page_security_page',
        'seravo_security_settings'
      );

      register_setting( 'seravo_security_settings', 'seravo-disable-xml-rpc' );
      register_setting( 'seravo_security_settings', 'seravo-disable-json-user-enumeration' );
      register_setting( 'seravo_security_settings', 'seravo-disable-get-author-enumeration' );
    }

    public static function security_settings_description() {
      echo '<p>' . __(
        'Seravo has security built-in. There are however a few extra measures
        that the site owner can choose to do if their site will not miss any functionality
        because of it.',
        'seravo') . '</p>';
    }

    public static function seravo_security_checked_field() {
      echo '<input type="checkbox" checked="on" disabled="disabled">';
    }

    public static function seravo_security_xmlrpc_field() {
      echo '<input type="checkbox" name="seravo-disable-xml-rpc" id="disable-xmlrpc" ' . checked( 'on', get_option( 'seravo-disable-xml-rpc' ), false ) . '>';
    }

    public static function seravo_security_json_user_enum_field() {
      echo '<input type="checkbox" name="seravo-disable-json-user-enumeration" id="disable-json-user-enumaration" ' . checked( 'on', get_option( 'seravo-disable-json-user-enumeration' ), false ) . '>';
    }

    public static function seravo_security_get_author_enum_field() {
      echo '<input type="checkbox" name="seravo-disable-get-author-enumeration" id="disable-get-author-enumeration" ' . checked( 'on', get_option( 'seravo-disable-get-author-enumeration' ), false ) . '>';
    }

    public static function security_info_postbox() {
      settings_errors();
      echo '<form method="post" action="options.php">';
      settings_fields( 'seravo_security_settings' );
      do_settings_sections( 'tools_page_security_page' );
      submit_button( __( 'Save', 'seravo' ), 'primary', 'btnSubmit' );
      echo '</form>';
    }
  }
  Security::load();
}
