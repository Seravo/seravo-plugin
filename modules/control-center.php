<?php

if ( ! defined ('ABSPATH') ) {
    die('Access denied!');
}

if ( ! class_exists('Control_Center') ) {
  class Control_Center {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_control_center' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
    }

    public static function register_control_center() {
      add_menu_page(
        'Control Center',
        __('Control Center', 'seravo'),
        'manage_options',
        'control_center',
        array( __CLASS__, 'load_control_center_page')
      );

      add_submenu_page(
        'control_center',
        __('Developer Tools', 'seravo'),
        __('Developer Tools', 'seravo'),
        'manage_options',
        'dev_tools_control_page',
        'Seravo\seravo_postboxes_page'
      );

      add_submenu_page(
        'control_center',
        __('Email', 'seravo'),
        __('Email', 'seravo'),
        'manage_options',
        'email_control_page',
        'Seravo\seravo_postboxes_page'
      );

      add_submenu_page(
        'control_center',
        __('Security', 'seravo'),
        __('Security', 'seravo'),
        'manage_options',
        'security_control_page',
        'Seravo\seravo_postboxes_page'
      );

      add_submenu_page(
        'control_center',
        __('Site Status', 'seravo'),
        __('Site Status', 'seravo'),
        'manage_options',
        'site_status_control_page',
        'Seravo\seravo_postboxes_page'
      );

      add_submenu_page(
        'control_center',
        __('Upkeep', 'seravo'),
        __('Upkeep', 'seravo'),
        'manage_options',
        'upkeep_control_page',
        'Seravo\seravo_postboxes_page'
      );
    }

    public static function register_scripts( $page ) {
      $loc_translation_updates = array(
        'ajaxurl'  => admin_url('admin-ajax.php'),
      );
    }

    public static function load_control_center_page() {
      require_once dirname( __FILE__ ) . '/../lib/control-center-page.php';
    }

    public static function seravo_security_postbox() {
      ?>
      <p>This is postbox</p>
      <?php 
    }

  }

  Control_Center::load();
}

?>