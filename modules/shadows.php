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

require_once dirname( __FILE__ ) . '/../lib/helpers.php';
require_once dirname( __FILE__ ) . '/../lib/shadows-ajax.php';

if ( ! class_exists('Shadows') ) {
  class Shadows {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_shadows_page' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_shadows_scripts' ));
      add_action( 'wp_ajax_seravo_reset_shadow', 'seravo_reset_shadow' );
    }

    public static function register_shadows_page() {
      add_submenu_page(
        'tools.php',
        __('Shadows', 'seravo'),
        __('Shadows', 'seravo'),
        'manage_options',
        'shadows_page',
        array( __CLASS__, 'load_shadows_page' )
      );

    }

    public static function register_shadows_scripts( $page ) {
      wp_register_style('seravo_shadows', plugin_dir_url( __DIR__ ) . '/style/shadows.css', '', Helpers::seravo_plugin_version() );
      wp_register_script( 'seravo_shadows', plugin_dir_url( __DIR__ ) . '/js/shadows.js', '', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_shadows_page' ) {
        wp_enqueue_style( 'seravo_shadows' );
        wp_enqueue_script( 'seravo_shadows' );

        $loc_translation = array(
          'success'    => __('Success', 'seravo'),
          'failure'    => __('Failure', 'seravo'),
          'error'      => __('Error', 'seravo'),
          'confirm'    => __('Are you sure? This replaces all information in the selected environment.', 'seravo'),
          'ajaxurl'    => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('seravo_shadows'),
        );

        wp_localize_script( 'seravo_shadows', 'seravo_shadows_loc', $loc_translation );
      }
    }
    public static function load_shadows_page() {
      require_once dirname( __FILE__ ) . '/../lib/shadows-page.php';
    }
  }

  // Only show shadows overview in production
  if ( Helpers::is_production() ) {
    Shadows::load();
  }
}
