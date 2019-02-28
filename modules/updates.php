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

require_once dirname( __FILE__ ) . '/../lib/updates-ajax.php';

if ( ! class_exists('Updates') ) {
  class Updates {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_updates_page' ) );

      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ));
      add_action( 'wp_ajax_seravo_ajax_updates', 'seravo_ajax_updates' );
      /*
      * This will use the SWD api to toggle Seravo updates on/off and add
      * technical contact emails for this site.
      */
      add_action( 'admin_post_toggle_seravo_updates', array( 'Seravo\Updates', 'seravo_admin_toggle_seravo_updates' ), 20 );

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook( __FILE__, array( __CLASS__, 'register_view_updates_capability' ) );

      seravo_add_postbox(
        'seravo-updates',
        __('Seravo Updates', 'seravo'),
        array( __CLASS__, 'seravo_updates_postbox' ),
        'tools_page_updates_page',
        'side'
      );

      seravo_add_postbox(
        'site-status',
        __('Site Status', 'seravo'),
        array( __CLASS__, 'site_status_postbox' ),
        'tools_page_updates_page',
        'side'
      );

      seravo_add_postbox(
        'tests-status',
        __('Tests Status', 'seravo'),
        array( __CLASS__, 'tests_status_postbox' ),
        'tools_page_updates_page',
        'side'
      );

      seravo_add_postbox(
        'change-php-version',
        __('Change PHP Version', 'seravo'),
        array( __CLASS__, 'change_php_version_postbox' ),
        'tools_page_updates_page',
        'side'
      );
    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function register_scripts( $page ) {

      wp_register_style('seravo_updates', plugin_dir_url(__DIR__) . '/style/updates.css', '', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_updates_page' ) {
        wp_enqueue_style( 'seravo_updates' );
        wp_enqueue_script( 'seravo_updates', plugins_url( '../js/updates.js', __FILE__), 'jquery', Helpers::seravo_plugin_version(), false );

        $loc_translation_updates = array(
          'ajaxurl'     => admin_url('admin-ajax.php'),
          'ajax_nonce'  => wp_create_nonce('seravo_updates'),
        );

        wp_localize_script( 'seravo_updates', 'seravo_updates_loc', $loc_translation_updates );
      }

    }

    public static function register_updates_page() {
      if ( getenv('SERAVO_API_KEY') !== '' ) {
        add_submenu_page( 'tools.php', __('Updates', 'seravo'), __('Updates', 'seravo'), 'manage_options', 'updates_page', 'Seravo\seravo_postboxes_page' );
      }
    }

    public static function seravo_admin_get_site_info() {
      $site_info = API::get_site_data();
      return $site_info;
    }

    public static function seravo_updates_postbox() {
      ?>
      <?php
        $site_info = SELF::seravo_admin_get_site_info();
      ?>
      <?php
        //WP_error-object
      if ( gettype($site_info) === 'array' ) {
        ?>
        <h2><?php _e('Opt-out from updates by Seravo', 'seravo'); ?></h2>
        <?php
        if ( $site_info['seravo_updates'] === true ) {
          $checked = 'checked="checked"';
        } else {
          $checked = '';
        }

        if ( isset( $site_info['notification_webhooks'][0]['url'] ) &&
              $site_info['notification_webhooks'][0]['type'] === 'slack' ) {
          $slack_webhook = $site_info['notification_webhooks'][0]['url'];
        } else {
          $slack_webhook = '';
        }

        $contact_emails = array();
        if ( isset($site_info['contact_emails']) ) {
          $contact_emails = $site_info['contact_emails'];
        }
        ?>
        <p><?php _e('The Seravo upkeep service includes core and plugin updates to your WordPress site, keeping your site current with security patches and frequent tested updates to both the WordPress core and plugins. If you want full control of updates to yourself, you should opt out from Seravo\'s updates by unchecking the checkbox below.', 'seravo'); ?></p>
          <form name="seravo_updates_form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
            <?php wp_nonce_field( 'seravo-updates-nonce' ); ?>
            <input type="hidden" name="action" value="toggle_seravo_updates">
            <div class="checkbox allow_updates_checkbox">
              <input id="seravo_updates" name="seravo_updates" type="checkbox" <?php echo $checked; ?>> <?php _e('Seravo updates enabled', 'seravo'); ?><br>
            </div>

            <hr class="seravo-updates-hr">
            <h2><?php _e('Update Notifications with a Slack Webhook', 'seravo'); ?></h2>
            <p><?php _e('By defining a Slack webhook address below, Seravo can send you notifications about every update attempt, whether successful or not, to the Slack channel you have defined in your webhook. <a href="https://api.slack.com/incoming-webhooks">Read more about webhooks</a>.', 'seravo'); ?></p>
            <input name="slack_webhook" type="url" size="30" placeholder="https://hooks.slack.com/services/..." value="<?php echo $slack_webhook; ?>">
            <button type="button" class="button" id="slack_webhook_test"><?php _e('Send a Test Notification', 'seravo'); ?></button>

            <hr class="seravo-updates-hr">
            <h2><?php _e('Technical Contacts', 'seravo'); ?></h2>
            <p><?php _e('Seravo may use the email addresses defined here to send automatic notifications about technical problems with you site. Remember to use a properly formatted email address.', 'seravo'); ?></p>
            <input class="technical_contacts_input" type="email" multiple size="30" placeholder="<?php _e('example@example.com', 'seravo'); ?>" value="" data-emails="<?php echo htmlspecialchars(json_encode($contact_emails)); ?>">
            <button type="button" class="technical_contacts_add button"><?php _e('Add', 'seravo'); ?></button>
            <span class="technical_contacts_error"><?php _e('Email must be formatted as name@domain.com', 'seravo'); ?></span>
            <input name="technical_contacts" type="hidden">
            <div class="technical_contacts_buttons"></div>
            <p><small class="seravo-developer-letter-hint">
            <?php
              // translators: %1$s link to Newsletter for WordPress developers
              printf( __('P.S. Subscribe to our %1$sNewsletter for WordPress Developers%2$s to get up-to-date information about our new features.', 'seravo'), '<a href="https://seravo.com/newsletter-for-wordpress-developers/">', '</a>');
            ?>
            </small></p>
            <br>
            <br>
            <input type="submit" id="save_settings_button" class="button button-primary" value="<?php _e('Save settings', 'seravo'); ?>">
          </form>
        <?php
      } else {
        echo $site_info->get_error_message();
      }
      ?>
      <?php
    }

    public static function site_status_postbox() {
      ?>
      <?php
        $site_info = SELF::seravo_admin_get_site_info();
      ?>
      <?php if ( gettype($site_info) === 'array' ) : ?>
      <ul>
        <li><?php _e('Site Created', 'seravo'); ?>: <?php echo date('Y-m-d', strtotime($site_info['created'])); ?></li>
        <li><?php _e('Latest Successful Full Update', 'seravo'); ?>: <?php echo date('Y-m-d', strtotime($site_info['update_success'])); ?></li>
        <?php if ( ! empty( $site_info['update_attempt'] ) ) { ?>
        <li><?php _e('Latest Update Attempt', 'seravo'); ?>: <?php echo date('Y-m-d', strtotime($site_info['update_attempt'])); ?></li>'
        <?php } ?>
      </ul>
        <?php
        else :
          echo $site_info->get_error_message();
          ?>
      <?php endif; ?>
      <h3><?php _e('Last 5 partial or attempted updates:', 'seravo'); ?><h3>
      <ul>
        <?php
        exec('zgrep -h "Started updates for" /data/log/update.log*', $output);
        foreach ( array_slice($output, 0, 5) as $key => $value ) {
          echo '<li>' . substr($value, 1, 16) . '</li>';
        }
        ?>
      </ul>
      <p>
        <?php
        printf(
          // translators: event count and updates.log and security.log paths
          __('For details about last %1$s update attempts by Seravo, see %2$s and %3$s.', 'seravo'),
          count($output),
          '<code>/data/log/update.log*</code>',
          '<code>/data/log/security.log*</code>'
        );
        ?>
      </p>
      <?php
    }

    public static function tests_status_postbox() {
      ?>
      <?php
      exec('zgrep -h -A 1 "Running initial tests in production" /data/log/update.log* | tail -n 1 | cut -d " " -f 4-8', $test_status);
      if ( $test_status[0] == 'Success! Initial tests have passed.' ) {
        echo '<p style="color: green;">' . __('Success!', 'seravo') . '</p>';
        // translators: Link to Tests page
        echo '<p>' . sprintf( __('Site baseline <a href="%s">tests</a> have passed and updates can run normally.', 'seravo'), 'tools.php?page=tests_page') . '</p>';
      } else {
        echo '<p style="color: red;">' . __('Failure!', 'seravo') . '</p>';
        // translators: Link to Tests page
        echo '<p>' . sprintf( __('Site baseline <a href="%s">tests</a> are failing and needs to be fixed before further updates are run.', 'seravo'), 'tools.php?page=tests_page') . '</p>';
      }
      ?>
      <?php
    }

    public static function change_php_version_postbox() {
      ?>
      <p>Latest version is recommended if all plugins and theme support it. Check <a href="tools.php?page=logs_page&logfile=wp-php-compatibility.log">compatibility scan results.</a></p>

      <div id="seravo-php-version">
        <?php
        $php_versions = array(
          '5.6' => array(
            'value' => '5.6',
            'name' => 'PHP 5.6 (EOL 31.12.2018)'
          ),
          '7.0' => array(
            'value' => '7.0',
            'name' => 'PHP 7.0 (EOL 3.12.2018)'
          ),
          '7.2' => array(
            'value' => '7.2',
            'name' => 'PHP 7.2'
          ),
          '7.3' => array(
            'value' => '7.3',
            'name' => 'PHP 7.3'
          )
        );

        $curVer = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        foreach ($php_versions as $php) {
          ?>
          <input type='radio' name="php-version" value="<?php echo $php['value']; ?>" class='php-version-radio' <?php if ($curVer == $php['value']) { echo 'checked'; }; ?> ><?php echo $php['name']?><br>
          <?php
        }
        ?>
        <br>
        <button id='change-version-button'>Change version</button>
        <br>
      </div>
      <div id="version-change-status">
      </div>
      <?php
    }

    public static function seravo_admin_image_comparison( $atts = [], $content = null, $tag = 'seravo_admin_image_comparison' ) {
      ob_start();
      ?>
      <div class="postbox-container">
        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
          <div id="dashboard_right_now" class="postbox">
            <button type="button" class="handlediv button-link" aria-expanded="true">
              <span class="screen-reader-text">Toggle panel: <?php _e('Screenshots', 'seravo'); ?></span>
              <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
            <h2 class="hndle ui-sortable-handle">
              <span><?php _e('Screenshots', 'seravo'); ?></span>
            </h2>
            <div class="inside seravo-updates-postbox">
      <?php
      $screenshots = glob( '/data/reports/tests/debug/*.png' );
      $showing = 0;
      # Shows a comparison of any and all image pair of *.png and *.shadow.png found.
      if ( count($screenshots) > 3 ) {

        echo '
      <table>
        <tr>
          <th>' . __('The Difference', 'seravo') . '</th>
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
              <td>
              <hr class="seravo-updates-hr">
              <a href="/.seravo/screenshots-ng/debug/' . $name . '.diff.png" class="diff-img-title">' . $name . '</a>
              <span';
              // Make the difference number stand out if it is non-zero
          if ( $diff > 0.011 ) {
            echo ' style="background-color: yellow;color: red;"';
          }
              echo '>' . round( $diff * 100, 2 ) . ' %</span>';

              echo self::seravo_admin_image_comparison_slider(array(
                'difference' => $diff,
                'img_right' => "/.seravo/screenshots-ng/debug/$name.shadow.png",
                'img_left' => "/.seravo/screenshots-ng/debug/$name.png",
              ));
              echo '
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
      echo '
            </div>
          </div>
        </div>
      </div>';
      return ob_get_clean();
    }

    public static function seravo_admin_image_comparison_slider( $atts = [], $content = null, $tag = 'seravo_admin_image_comparison_slider' ) {

      // normalize attribute keys, lowercase
      $atts = array_change_key_case( (array) $atts, CASE_LOWER);

      $img_comp_atts = shortcode_atts([
        'difference'  => '',
        'img_left'    => '',
        'img_right'   => '',
        'desc_left' => __('Current State', 'seravo'),
        'desc_right'  => __('Update Attempt', 'seravo'),
        'desc_left_bg_color' => 'green',
        'desc_right_bg_color' => 'red',
        'desc_left_txt_color' => 'white',
        'desc_right_txt_color' => 'white',
	  ], $atts, $tag);
      if ( floatval( $img_comp_atts['difference'] ) > 0.011 ) {
        $knob_style = 'difference';
      } else {
        $knob_style = '';
      }
      ob_start();
      ?>
      <div class="ba-slider <?php echo $knob_style; ?>">
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

  // Show updates page only in production
  if ( Helpers::is_production() ) {
    Updates::load();
  }
}
