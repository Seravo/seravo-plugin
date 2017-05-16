<?php
/*
 * Plugin name: Updates
 * Description: Enable users to manage their Seravo WordPress updates
 * Version: 1.0
 */

namespace Seravo;

if ( ! class_exists('Updates') ) {
  class Updates {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_updates_page' ) );

      /*
      * This will use the SWD api to toggle Seravo updates on/off for this site.
      */
      add_action( 'admin_post_toggle_seravo_updates', array( 'Seravo\Updates', 'seravo_admin_toggle_seravo_updates' ), 20 );

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array( __CLASS__, 'register_view_updates_capability' ) );
    }

    public static function register_updates_page() {
      if ( getenv('SERAVO_API_KEY') != '' ) {
        add_submenu_page( 'tools.php', 'Updates', 'Updates', 'manage_options', 'updates_page', array( __CLASS__, 'load_updates_page' ) );
      }
    }

    public static function load_updates_page() {
      require_once(dirname( __FILE__ ) . '/../lib/updates-page.php');
    }

    public static function seravo_admin_toggle_seravo_updates() {
      check_admin_referer( 'toggle-seravo-updates-on-or-off' );

      $site = getenv('USER');
      $ch = curl_init('http://localhost:8888/v1/site/' . $site);

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      if ( $_POST['seravo_updates'] == 'on' ) {
        $seravo_updates = 'true';
      } else {
        $seravo_updates = 'false';
      }

      $data = array( 'seravo_updates' => $seravo_updates );
      $data_string = json_encode($data);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'X-Api-Key: ' . getenv('SERAVO_API_KEY'),
          'Content-Type: application/json',
          'Content-Length: ' . strlen($data_string),
      ));

      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      // error_log($data_string);

      $response = curl_exec($ch);

      if ( curl_error($ch) ) {
        error_log('SWD API error: ' . curl_error($ch));
        status_header(500);
        die('API call failed. Aborting. The error has been logged.');
      }

      curl_close($ch);
      status_header(200);
      header('Location: ' . esc_url( admin_url('tools.php?page=updates_page') ) );
      die();
    }

  }

  Updates::load();
}
