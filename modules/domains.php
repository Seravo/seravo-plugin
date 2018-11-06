<?php
/*
 * Plugin name: Domains
 * Description: View and edit domains and DNS
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Domains') ) {
  class Domains {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_domains_page' ) );

      add_action( 'admin_post_change_zone_file', array( 'Seravo\Domains', 'seravo_admin_change_zone_file' ), 20 );

    }

    public static function register_scripts( $page ) {
      echo $page;
      if ( $page === '' ) {
        wp_enqueue_script( 'seravo_domains', plugins_url( '../js/domains.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );
      }
    }

    public static function register_domains_page() {
      add_submenu_page( 'tools.php', __('Domains', 'seravo'), __('Domains', 'seravo'), 'manage_options', 'domains_page', array( __CLASS__, 'load_domains_page' ) );
    }

    public static function load_domains_page() {
      require_once dirname( __FILE__ ) . '/../lib/domains-page.php';
    }

    public static function seravo_admin_change_zone_file() {
      check_admin_referer( 'seravo-zone-nonce' );
      $response = '';
      if ( isset( $_POST['zonefile'] ) && isset( $_POST['domain'] ) ) {
        // Attach the editable records to the compulsory
        if ( $_POST['compulsory'] ) {
          $zone = $_POST['compulsory'] . "\n" . $_POST['zonefile'];
        } else {
          $zone = $_POST['zonefile'];
        }

        // Remove the escapes that are not needed.
        // This makes \" into "
        $data_str = str_replace( '\"', '"', $zone );
        // This makes \\\\" into \"
        $data_str = str_replace( '\\\\"', '\"', $data_str );
        $data = explode( "\r\n", $data_str );
        $response = API::update_site_data( $data, '/domain/' . $_POST['domain'] . '/zone', [ 200, 400 ] );
        if ( is_wp_error( $response ) ) {
          die( $response->get_error_message() );
        }
      } else {
        die('An error occured while trying to change the zone');
      }
      // Response as an object
      $r_obj = json_decode( $response );

      // Check if validation tests failed
      if ( isset( $r_obj->status ) && $r_obj->status === 400 ) {
        $error_msg = '&error=' . urlencode( $r_obj->reason );
        wp_redirect( admin_url( 'tools.php?page=domains_page&zone-updated=true' . $error_msg ) );
        die();

      } elseif ( $r_obj->modifications ) {
        $modifications = $r_obj->modifications;
        $mod_str = '';
        foreach ( $modifications as $m ) {
          $mod_str .= '&modifications[]=' . urlencode( $m );
        }
      }
      wp_redirect( admin_url( 'tools.php?page=domains_page&zone-updated=true' . $mod_str ) );
      die();
    }
  }

  Domains::load();
}
