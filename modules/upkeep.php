<?php

namespace Seravo;

if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname(__FILE__) . '/../lib/upkeep-ajax.php';

if ( ! class_exists('Upkeep') ) {
  class Upkeep {
    public static function load() {
      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ));
      add_action('wp_ajax_seravo_ajax_upkeep', 'seravo_ajax_upkeep');

      add_action('admin_post_toggle_seravo_updates', array( __CLASS__, 'seravo_admin_toggle_seravo_updates' ), 20);

      // TODO: check if this hook actually ever fires for mu-plugins
      register_activation_hook(__FILE__, array( __CLASS__, 'register_view_updates_capability' ));

      if ( getenv('WP_ENV') === 'production' ) {
        seravo_add_postbox(
          'site-status',
          __('Site Status', 'seravo'),
          array( __CLASS__, 'site_status_postbox' ),
          'tools_page_upkeep_page',
          'normal'
        );

        seravo_add_postbox(
          'tests-status',
          __('Tests Status', 'seravo'),
          array( __CLASS__, 'tests_status_postbox' ),
          'tools_page_upkeep_page',
          'normal'
        );
      }

      seravo_add_postbox(
        'tests',
        __('Update tests', 'seravo'),
        array( __CLASS__, 'tests_postbox' ),
        'tools_page_upkeep_page',
        'normal'
      );

      if ( getenv('WP_ENV') === 'production' ) {
        seravo_add_postbox(
          'seravo-updates',
          __('Seravo Updates', 'seravo'),
          array( __CLASS__, 'seravo_updates_postbox' ),
          'tools_page_upkeep_page',
          'normal'
        );

        seravo_add_postbox(
          'screenshots',
          __('Screenshots', 'seravo'),
          array( __CLASS__, 'screenshots_postbox' ),
          'tools_page_upkeep_page',
          'side'
        );
      }

      seravo_add_postbox(
        'change-php-version',
        __('Change PHP Version', 'seravo'),
        array( __CLASS__, 'change_php_version_postbox' ),
        'tools_page_upkeep_page',
        'side'
      );

      seravo_add_postbox(
        'seravo-plugin-updater',
        __('Seravo Plugin Updater', 'seravo'),
        array( __CLASS__, 'seravo_plugin_updater_postbox' ),
        'tools_page_upkeep_page',
        'side'
      );
    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function register_scripts( $page ) {

      wp_register_style('seravo_upkeep', plugin_dir_url(__DIR__) . '/style/upkeep.css', '', Helpers::seravo_plugin_version());
      wp_register_script('seravo_upkeep', plugin_dir_url(__DIR__) . '/js/upkeep.js', 'jquery', Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_upkeep_page' ) {
        wp_enqueue_style('seravo_upkeep');
        wp_enqueue_script('seravo_upkeep');

        $loc_translation = array(
          'compatibility_check_running' => __('Running PHP compatibility check, this can take a while depending on the number of plugins and themes installed.', 'seravo'),
          'compatibility_check_error' => __(' errors found. See logs for more information.', 'seravo'),
          'compatibility_check_clear' => __('&#x2705; No errors found! See logs for more information.', 'seravo'),
          'compatibility_run_fail' => __('There was an error starting the compatibility check.', 'seravo'),
          'no_data'       => __('No data returned for the section.', 'seravo'),
          'test_success'  => __('Tests were run without any errors!', 'seravo'),
          'test_fail'     => __('At least one of the tests failed.', 'seravo'),
          'run_fail'      => __('Failed to load. Please try again.', 'seravo'),
          'running_tests' => __('Running rspec tests...', 'seravo'),
          'ajaxurl'    => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('seravo_upkeep'),
        );

        wp_localize_script('seravo_upkeep', 'seravo_upkeep_loc', $loc_translation);
      }

    }

    public static function seravo_admin_get_site_info() {
      $site_info = API::get_site_data();
      return $site_info;
    }

    public static function seravo_updates_postbox() {

      $site_info = self::seravo_admin_get_site_info();

      // WP_error-object
      if ( gettype($site_info) === 'array' ) {
        ?>
        <h2><?php _e('Opt-out from updates by Seravo', 'seravo'); ?></h2>
        <?php
        if ( $site_info['seravo_updates'] === true ) {
          $checked = 'checked="checked"';
        } else {
          $checked = '';
        }

        if ( isset($site_info['notification_webhooks'][0]['url']) &&
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
        <form name="seravo_updates_form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
          <?php wp_nonce_field('seravo-updates-nonce'); ?>
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
          <br>
          <input type="submit" id="save_settings_button" class="button button-primary" value="<?php _e('Save settings', 'seravo'); ?>">
          <p><small class="seravo-developer-letter-hint">
              <?php
              // translators: %1$s link to Newsletter for WordPress developers
              printf(__('P.S. Subscribe to our %1$sNewsletter for WordPress Developers%2$s to get up-to-date information about our new features.', 'seravo'), '<a href="https://seravo.com/newsletter-for-wordpress-developers/">', '</a>');
              ?>
            </small></p>
          <br>
        </form>
        <?php
      } else {
        echo $site_info->get_error_message();
      }
    }

    public static function site_status_postbox() {

      $site_info = self::seravo_admin_get_site_info();
      // Calculate the approx. amount of days since last succesful FULL update
      // 86400 is used to get days out of seconds (60*60*24)
      $interval = round((strtotime(date('Y-m-d')) - strtotime($site_info['update_success'])) / 86400);

      // Check if update.log exists and if not fetch the name of the rotated log instead
      // for linking to correct log on the logs page as well as fetching the failed lines
      // from the log if needed in the update notification
      $update_logs_arr = glob('/data/log/update.log');
      if ( empty($update_logs_arr) ) {
        $update_logs_arr = preg_grep('/([0-9]){8}$/', glob('/data/log/update.log-*'));
      }
      $update_log_name = substr(end($update_logs_arr), 10);

      if ( gettype($site_info) === 'array' ) {
        ?>
        <p>
          <?php _e('Site created', 'seravo'); ?>:
          <?php echo date('Y-m-d', strtotime($site_info['created'])); ?>
        </p>
        <?php
        // Show notification if FULL site update hasn't been succesful in 30 or more days
        // and the site is using Seravo updates
        if ( $site_info['seravo_updates'] === true && $interval >= 30 ) {
          if ( empty($update_logs_arr) ) {
            echo '<p>' . __('Unable to fetch the latest update log.', 'seravo') . '</p>';
          } else {
            // Get last item from logs array
            $update_log_fp = fopen(end($update_logs_arr), 'r');
            if ( $update_log_fp != false ) {
              $index = 0;
              while ( ! feof($update_log_fp) ) {
                // Strip timestamps from log lines
                // Show only lines with 'Updates failed!'
                $buffer = substr(fgets($update_log_fp), 28);
                if ( substr($buffer, 0, 15) === 'Updates failed!' ) {
                  $update_log_contents[ $index ] = $buffer;
                  $index++;
                }
              }
              fclose($update_log_fp);
              $update_log_output = implode('<br>', $update_log_contents);
            }

            echo '<p>' . __(
              'Last succesful full site update was over a month ago. A developer should take
              a look at the update log and fix the issue preventing the site from updating.',
              'seravo'
            ) . '</p>';
            if ( ! empty($update_log_contents) ) {
              echo '<p><b>' . __('Latest update.log:', 'seravo') . '</b></p><p>' .
                $update_log_output . '</p>';
            }
            echo '<p><a href="tools.php?page=logs_page&logfile=update.log&max_num_of_rows=50">' . __('See the logs page for more info.', 'seravo') . '</a></p>';
          }
        }

        ?>

        <p><?php _e('Latest successful full update', 'seravo'); ?>: <?php echo date('Y-m-d', strtotime($site_info['update_success'])); ?></p>

        <?php
        if ( ! empty($site_info['update_attempt']) ) {
          echo '<p>' . __('Latest attempted full update', 'seravo') . ': ';
          echo date('Y-m-d', strtotime($site_info['update_attempt'])) . '</p>';
        }
      } else {
        echo $site_info->get_error_message();
      }
      ?>
      <h3><?php _e('Last 5 partial or attempted updates:', 'seravo'); ?></h3>
      <ul>
        <?php
        exec('zgrep -h -e "Started updates for" -e "Installing urgent security" /data/log/update.log* | sort -r', $output);
        foreach ( array_slice($output, 0, 5) as $key => $value ) {
          // Show only date ad the hour and minute are irrelevant
          echo '<li>' . substr($value, 1, 11) . '</li>';
        }
        ?>
      </ul>
      <p>
        <?php
        printf(
          // translators: event count and update.log filename and updates.log and security.log paths
          __('For details about last %1$s update attempts by Seravo, see %2$s.', 'seravo'),
          count($output),
          '<a href="tools.php?page=logs_page&logfile=' . $update_log_name . '&max_num_of_rows=50"><code>update.log*</code></a>'
        );
        ?>
      </p>
      <?php
    }

    public static function tests_status_postbox() {
      exec('zgrep -h -A 1 "Running initial tests in production" /data/log/update.log-* /data/log/update.log | tail -n 1 | cut -d " " -f 4-8', $test_status);

      if ( $test_status[0] == 'Success! Initial tests have passed.' ) {
        echo '<p style="color: green;">' . __('Success!', 'seravo') . '</p>';
        // translators: Link to Tests page
        echo '<p>' . sprintf(__('Site baseline tests have passed and updates can run normally.', 'seravo')) . '</p>';
      } else {
        echo '<p style="color: red;">' . __('Failure!', 'seravo') . '</p>';
        // translators: Link to Tests page
        echo '<p>' . sprintf(__('Site baseline tests are failing and needs to be fixed before further updates are run.', 'seravo')) . '</p>';
      }
    }

    public static function change_php_version_postbox() {
      ?>
      <p>
        <?php
        printf(
          // translators: link to log file
          __('Latest version is recommended if all plugins and theme support it. Check <a href="%s">compatibility scan results.</a>', 'seravo'),
          'tools.php?page=logs_page&logfile=wp-php-compatibility.log'
        );
        ?>
      </p>

      <button id='check-php-compatibility-button'><?php _e('Check PHP compatibility', 'seravo'); ?></button>

      <div id="check-php-compatibility-status" class="hidden">
        <img src="/wp-admin/images/spinner.gif" style="display:inline-block">
      </div>

      <p>
        <?php
        _e('See also <a target="_blank" href="https://help.seravo.com/en/knowledgebase/13/docs/107-set-your-site-to-use-newest-php-version">more information on PHP version upgrades</a>.', 'seravo');
        ?>
      </p>

      <div id="seravo-php-version">
        <?php
        $php_versions = array(
          '5.6' => array(
            'value'    => '5.6',
            'name'     => 'PHP 5.6 (EOL 31.12.2018)',
            'disabled' => true,
          ),
          '7.0' => array(
            'value'    => '7.0',
            'name'     => 'PHP 7.0 (EOL 10.1.2019)',
            'disabled' => true,
          ),
          '7.2' => array(
            'value' => '7.2',
            'name'  => 'PHP 7.2',
          ),
          '7.3' => array(
            'value' => '7.3',
            'name'  => 'PHP 7.3',
          ),
          '7.4' => array(
            'value' => '7.4',
            'name'  => 'PHP 7.4',
          ),
        );

        $curver = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        foreach ( $php_versions as $php ) {
          ?>
          <input type='radio' name="php-version"
          <?php
          if ( isset($php['disabled']) && $php['disabled'] ) {
            echo 'disabled';
          };
          ?>
                 value="<?php echo $php['value']; ?>" class='php-version-radio'
          <?php
          if ( $curver == $php['value'] ) {
            echo 'checked';
          };
          ?>
          ><?php echo $php['name']; ?><br>
          <?php
        }
        ?>
        <br>
        <span id="overwrite-config-files-span">
          <input type="checkbox" id="overwrite-config-files" class="hidden">
          <?php
          _e('I\'m aware of the risks associated with edits to the PHP configuration files and want to proceed with the change.', 'seravo');
          ?>
          <br>
        </span>
        <button id='change-php-version-button'><?php _e('Change version', 'seravo'); ?></button>
        <br>
      </div>
      <div id="change-php-version-status" class="hidden">
        <img src="/wp-admin/images/spinner.gif" style="display:inline-block">
        <?php _e('Activating... Please wait up to 30 seconds', 'seravo'); ?>
      </div>
      <div id="php-change-end">
        <p id="activated-line" class="hidden">
          <?php
          printf(
            // translators: link to log file
            __('PHP version has been changed succesfully! Please check <a href="%s">php-error.log</a> for regressions.', 'seravo'),
            'tools.php?page=logs_page&logfile=php-error.log'
          );
          ?>
        </p>
        <p id="activation-failed-line" class="hidden"><?php _e('PHP version change failed.', 'seravo'); ?></p>
      </div>
      <?php
    }

    public static function seravo_plugin_updater_postbox() {
      ?>
      <p><?php _e('Seravo automatically updates your site and the Seravo Plugin as well. If you want to immediately update to the latest Seravo Plugin version, you can do it here.', 'seravo'); ?></p>
      <p>
        <?php
        printf(
          // translators: current Seravo plugin version
          __('Current version: %s', 'seravo'),
          Helpers::seravo_plugin_version()
        );
        ?>
      </p>
      <p>
        <?php
        printf(
          // translators: upstream Seravo plugin version
          __('Upstream version: %s', 'seravo'),
          seravo_plugin_upstream_version()
        );
        ?>
      </p>
      <p id='uptodate_seravo_plugin_version' class='hidden' style='color: green'><?php _e('The currently installed version is the same as the latest available version.', 'seravo'); ?></p>
      <p id='old_seravo_plugin_version' class='hidden' style='color: orange'><?php _e('There is a new version available', 'seravo'); ?></p>
      <p id='seravo_plugin_updated' class='hidden' style='color: green'><?php _e('Seravo Plugin updated', 'seravo'); ?></p>
      <div id="update_seravo_plugin_status" class="hidden">
        <img src="/wp-admin/images/spinner.gif" style="display:inline-block">
        <?php _e('Updating... Please wait up to 5 seconds', 'seravo'); ?>
      </div>
      <button id='seravo_plugin_update_button' class='hidden'><?php _e('Update plugin now', 'seravo'); ?></button>
      <?php
    }

    public static function screenshots_postbox() {

      $screenshots = glob('/data/reports/tests/debug/*.png');
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
          if ( strpos($screenshot, '.shadow.png') || strpos($screenshot, '.diff.png') ) {
            continue;
          }

          $name = substr(basename($screenshot), 0, -4);

          // Check whether the *.shadow.png exists in the set
          // Do not show the comparison if both images are not found.
          $exists_shadow = false;
          foreach ( $screenshots as $key => $screenshotshadow ) {
            // Increment over the known images. Stop when match found
            if ( strpos($screenshotshadow, $name . '.shadow.png') !== false ) {
              $exists_shadow = true;
              break;
            }
          }
          // Only shot the comparison if both images are available
          if ( ! $exists_shadow ) {
            continue;
          }

          $diff_txt = file_get_contents(substr($screenshot, 0, -4) . '.diff.txt');
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
          echo '>' . round($diff * 100, 2) . ' %</span>';

          echo self::seravo_admin_image_comparison_slider(
            array(
              'difference' => $diff,
              'img_right'  => "/.seravo/screenshots-ng/debug/$name.shadow.png",
              'img_left'   => "/.seravo/screenshots-ng/debug/$name.png",
            )
          );
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
        echo __('No screenshots found. They will become available during the next attempted update.', 'seravo');
      }
    }

    public static function seravo_admin_image_comparison_slider( $atts = array(), $content = null, $tag = 'seravo_admin_image_comparison_slider' ) {

      // normalize attribute keys, lowercase
      $atts = array_change_key_case((array) $atts, CASE_LOWER);

      $img_comp_atts = shortcode_atts(
        array(
          'difference'           => '',
          'img_left'             => '',
          'img_right'            => '',
          'desc_left'            => __('Current State', 'seravo'),
          'desc_right'           => __('Update Attempt', 'seravo'),
          'desc_left_bg_color'   => 'green',
          'desc_right_bg_color'  => 'red',
          'desc_left_txt_color'  => 'white',
          'desc_right_txt_color' => 'white',
        ),
        $atts,
        $tag
      );
      if ( floatval($img_comp_atts['difference']) > 0.011 ) {
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
      check_admin_referer('seravo-updates-nonce');

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

          $contact_addresses = explode(',', $_POST['technical_contacts']);

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

      wp_redirect(admin_url('tools.php?page=upkeep_page&settings-updated=true'));
      die();
    }

    public static function tests_postbox() {
      ?>
      <p>
        <?php
        _e('Here you can test the core functionality of your WordPress installation. Same results can be achieved via command line by running <code>wp-test</code> there. For further information, please refer to <a href="https://seravo.com/docs/tests/ng-integration-tests/"> Seravo Developer Documentation</a>.', 'seravo');
        ?>
      </p>
      <button type="button" class="button-primary" id="run-wp-tests"><?php _e('Run Tests', 'seravo'); ?></button>
      <div class="seravo-test-result-wrapper">
        <div class="seravo-test-status" id="seravo_tests_status">
          <?php _e('Click "Run Tests" to run the Codeception tests', 'seravo'); ?>
        </div>
        <div class="seravo-test-result">
          <pre id="seravo_tests"></pre>
        </div>
        <div id="seravo_test_show_more_wrapper" class="hidden">
          <a href="" id="seravo_test_show_more"><?php _e('Toggle Details', 'seravo'); ?>
            <div class="dashicons dashicons-arrow-down-alt2" id="seravo_arrow_show_more">
            </div>
          </a>
        </div>
      </div>
      <?php
    }
  }

  Upkeep::load();
}
