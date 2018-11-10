<?php
/*
 * Plugin name: Updates
 * Description: Enable users to manage their Seravo WordPress updates
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Updates') ) {
  class Updates {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_updates_page' ) );

      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ));
      /*
      * This will use the SWD api to toggle Seravo updates on/off and add
      * technical contact emails for this site.
      */
      add_action( 'admin_post_toggle_seravo_updates', array( 'Seravo\Updates', 'seravo_admin_toggle_seravo_updates' ), 20 );

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array( __CLASS__, 'register_view_updates_capability' ) );
    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function register_scripts( $page ) {

      wp_register_style('seravo_updates', plugin_dir_url(__DIR__) . '/style/updates.css', '', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_updates_page' ) {
          wp_enqueue_style('seravo_updates');
          wp_enqueue_script( 'seravo_updates', plugins_url( '../js/updates.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );
      }

    }

    public static function register_updates_page() {
      if ( getenv('SERAVO_API_KEY') !== '' ) {
        add_submenu_page( 'tools.php', __('Updates', 'seravo'), __('Updates', 'seravo'), 'manage_options', 'updates_page', array( __CLASS__, 'load_updates_page' ) );
      }
    }

    public static function load_updates_page() {
      require_once dirname( __FILE__ ) . '/../lib/updates-page.php';
    }

    public static function seravo_admin_get_site_info() {
      $site_info = API::get_site_data();
      return $site_info;
    }

    public static function seravo_admin_toggle_seravo_updates() {
      check_admin_referer( 'seravo-updates-nonce' );

      if ( isset($_POST['seravo_updates']) && $_POST['seravo_updates'] === 'on' ) {
        $seravo_updates = 'true';
      } else {
        $seravo_updates = 'false';
      }
      $data = array( 'seravo_updates' => $seravo_updates );

      // Slack webhook
      $data['notification_webhooks'] = array(
        'type' => 'slack',
        'url' => $_POST['slack_webhook'],
      );

      // Handle site technical contact email addresses
      if ( isset($_POST['technical_contacts']) ) {
        $validated_addresses = array();

        if ( ! empty($_POST['technical_contacts']) ) {

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

      $response = API::update_site_data($data);
      if ( is_wp_error($response) ) {
        die($response->get_error_message());
      }

      wp_redirect( admin_url('tools.php?page=updates_page&settings-updated=true') );
      die();
    }

  }

  Updates::load();
}
