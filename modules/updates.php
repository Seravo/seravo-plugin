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

    public static function seravo_admin_image_comparison( $atts = [], $content = null, $tag = 'seravo_admin_image_comparison' ) {
      ob_start();
      ?>
      <h2 class="clear"><?php _e('Screenshots', 'seravo'); ?></h2>
      <?php
      $screenshots = glob( '/data/reports/tests/debug/*.png' );
      $showing = 0;
      # Shows a comparison of any and all image pair of *.png and *.shadow.png found.
      if ( count($screenshots) > 3 ) {

        echo '
      <table>
        <tr>
          <th style="background-color: yellow;">' . __('The Difference', 'seravo') . '</th>
        </tr>
          <tbody  style="vertical-align: top; text-align: center;">';

        foreach ( $screenshots as $key => $screenshot ) {
          // Skip *.shadow.png files from this loop
          if ( strpos( $screenshot, '.shadow.png') || strpos( $screenshot, '.diff.png') ) {
            continue;
          }

          $name = substr( basename( $screenshot ), 0, -4);

          // Check whether the *.shadow.png exists in the set
          // Do not show the comparison if both images are not found.
          $exists_shadow = false;
          foreach ( $screenshots as $key => $screenshotshadow ) {
                // Increment over the known images. Stop when match found
            if ( strpos( $screenshotshadow, $name . '.shadow.png' ) !== false ) {
                $exists_shadow = true;
                break;
            }
          }
          // Only shot the comparison if both images are available
          if ( ! $exists_shadow ) {
            continue;
          }

          $diff_txt = file_get_contents( substr( $screenshot, 0, -4) . '.diff.txt' );
          if ( preg_match('/Total: ([0-9.]+)/', $diff_txt, $matches) ) {
            $diff = (float) $matches[1];
          }

          echo '
            <tr>
              <td style="background-color: yellow;">
              ';
              echo self::seravo_admin_image_comparison_slider(array(
                'img_right' => "/.seravo/screenshots-ng/debug/$name.shadow.png",
                'img_left' => "/.seravo/screenshots-ng/debug/$name.png",
              ));
              echo '<br><span';
              // Make the difference number stand out if it is non-zero
          if ( $diff > 0.011 ) {
            echo ' style="color: red;"';
          }
              echo '>' . round( $diff * 100, 2 ) . ' %</span>
              </td>
            </tr>';
            $showing++;
        }
        echo '
        </tbody>
      </table>';

      }

      if ( $showing == 0 ) {
        echo '<tr><td colspan="3">' .
          __('No screenshots found. They will become available during the next attempted update.', 'seravo') .
          '</td></tr>';
      }

      return ob_get_clean();
    }

    public static function seravo_admin_image_comparison_slider( $atts = [], $content = null, $tag = 'seravo_admin_image_comparison_slider' ) {

      // normalize attribute keys, lowercase
      $atts = array_change_key_case( (array) $atts, CASE_LOWER);

      $img_comp_atts = shortcode_atts([
        'img_left'    => '',
        'img_right'   => '',
        'desc_left' => __('Current State', 'seravo'),
        'desc_right'  => __('Update Attempt', 'seravo'),
        'desc_left_bg_color' => 'green',
        'desc_right_bg_color' => 'red',
        'desc_left_txt_color' => 'white',
        'desc_right_txt_color' => 'white',
	  ], $atts, $tag);
      ob_start();
      ?>
      <div class="ba-slider">
       <img src="<?php echo $img_comp_atts['img_right']; ?>">
       <div class="ba-text-block" style="background-color:<?php echo $img_comp_atts['desc_right_bg_color']; ?>;color:<?php echo $img_comp_atts['desc_right_txt_color']; ?>;">
            <?php echo $img_comp_atts['desc_right']; ?>
      </div>
       <div class="ba-resize">
           <img src="<?php echo $img_comp_atts['img_left']; ?>">
           <div class="ba-text-block" style="background-color:<?php echo $img_comp_atts['desc_left_bg_color']; ?>;color:<?php echo $img_comp_atts['desc_left_txt_color']; ?>;">
              <?php echo $img_comp_atts['desc_left']; ?>
           </div>
       </div>
       <span class="ba-handle"></span>
     </div>
      <?php

      return ob_get_clean();
    }

    public static function seravo_admin_toggle_seravo_updates() {
      check_admin_referer( 'seravo-updates-nonce' );

      if ( isset($_POST['seravo_updates']) && $_POST['seravo_updates'] === 'on' ) {
        $seravo_updates = 'true';
      } else {
        $seravo_updates = 'false';
      }
      $data = array( 'seravo_updates' => $seravo_updates );

      // Webhooks is an anonymous array of named arrays with type/url pairs
      $data['notification_webhooks'] = array(
        array(
          'type' => 'slack',
          'url'  => $_POST['slack_webhook'],
        ),
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
