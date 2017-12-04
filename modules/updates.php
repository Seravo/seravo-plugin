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
      * This will use the SWD api to toggle Seravo updates on/off and add
      * technical contact emails for this site.
      */
      add_action( 'admin_post_toggle_seravo_updates', array( 'Seravo\Updates', 'seravo_admin_toggle_seravo_updates' ), 20 );

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array( __CLASS__, 'register_view_updates_capability' ) );
    }

    public static function register_updates_page() {
      if ( getenv('SERAVO_API_KEY') !== '' ) {
        add_submenu_page( 'tools.php', __('Updates', 'seravo'), __('Updates', 'seravo'), 'manage_options', 'updates_page', array( __CLASS__, 'load_updates_page' ) );
      }
    }

    public static function load_updates_page() {
      require_once(dirname( __FILE__ ) . '/../lib/updates-page.php');
    }

    public static function seravo_admin_get_site_info() {

      $site = getenv('USER');

      $ch = curl_init('http://localhost:8888/v1/site/' . $site);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-Api-Key: ' . getenv('SERAVO_API_KEY') ));
      $response = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if ( curl_error($ch) || $httpcode !== 200 ) {
        error_log('SWD API (updates) error ' . $httpcode . ': ' . curl_error($ch));
        die('API call failed. Aborting. The error has been logged.');
      }

      curl_close($ch);

      $site_info = json_decode($response, true);

      return $site_info;
    }

    public static function seravo_admin_toggle_seravo_updates() {
      check_admin_referer( 'seravo-updates-nonce' );

      $site = getenv('USER');
      $ch = curl_init('http://localhost:8888/v1/site/' . $site);

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      if ( $_POST['seravo_updates'] === 'on' ) {
        $seravo_updates = 'true';
      } else {
        $seravo_updates = 'false';
      }
      $data = array( 'seravo_updates' => $seravo_updates );

      // Handle site technical contact email addresses
      if ( isset($_POST['technical_contacts']) ) {
        $validated_addresses = array();

        if (! empty($_POST['technical_contacts']) ) {
          $contact_addresses = explode( ',', $_POST['technical_contacts']);

          // Perform email validation before making API request
          foreach ( $contact_addresses as $contact_address ) {
            $address = trim($contact_address);

            if ( ! empty($address) && filter_var($address, FILTER_VALIDATE_EMAIL) ) {
              $validated_addresses[] = $address;
            }
          }

        } elseif ( trim($_POST['technical_contacts']) === '' ) {
          // If the contact email field is left entirely empty, it means that the
          // customer wishes to remove all his/her emails => so consider an empty
          // string as a "valid address"
          $validated_addresses[] = '';
        }

        // Only update addresses if any valid ones were found
        if ( ! empty($validated_addresses) ) {
          $data['contact_emails'] = $validated_addresses;
        }
      }

      $data_string = json_encode($data);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'X-Api-Key: ' . getenv('SERAVO_API_KEY'),
          'Content-Type: application/json',
          'Content-Length: ' . strlen($data_string),
      ));

      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

      $response = curl_exec($ch);

      if ( curl_error($ch) ) {
        error_log('SWD API (updates) error: ' . curl_error($ch));
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
