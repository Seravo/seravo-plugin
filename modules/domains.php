<?php
/*
 * Plugin name: Domains
 * Description: View and edit domains and DNS
 * Version: 1.0
 */

namespace Seravo;

require_once dirname( __FILE__ ) . '/../lib/domains-ajax.php';

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Domains') ) {
  class Domains {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_domains_page' ) );
      add_action( 'wp_ajax_seravo_ajax_domains', 'seravo_ajax_domains' );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ));
    }

    public static function register_scripts( $page ) {

      if ( $page === 'tools_page_domains_page' ) {

        wp_enqueue_script( 'seravo_domains', plugins_url( '../js/domains.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );

        $loc_translation_domains = array(
          'ajaxurl'    => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('seravo_domains'),
          'zone_update_failed' => __( 'The zone update failed', 'seravo' ),
          'zone_update_success' => __('The zone was updated succesfully!', 'seravo'),
          'zone_modifications' => __('The following modifications were done for the zone: ', 'seravo'),
        );

        wp_localize_script( 'seravo_domains', 'seravo_domains_loc', $loc_translation_domains );

      }

    }

    public static function register_domains_page() {
      add_submenu_page( 'tools.php', __('Domains', 'seravo'), __('Domains', 'seravo'), 'manage_options', 'domains_page', array( __CLASS__, 'load_domains_page' ) );
    }

    public static function load_domains_page() {
      require_once dirname( __FILE__ ) . '/../lib/domains-page.php';
    }

  }

  /* Only show domains page in production */
  if ( Helpers::is_production() ) {
    Domains::load();
  }
}
