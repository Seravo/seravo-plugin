<?php

namespace Seravo;

if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname(__FILE__) . '/../lib/sitestatus-ajax.php';

if ( ! class_exists('Site_Status') ) {
  class Site_Status {
    // Default maximum resolution for images
    private static $max_width_default = 4000;
    private static $max_height_default = 4000;

    // Minimum resolution for images. Can't be set any lower by user.
    private static $min_width = 500;
    private static $min_height = 500;

    public static function load() {
      add_action('admin_init', array( __CLASS__, 'register_optimize_image_settings' ));
      add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_site_status_scripts' ));
      add_action('wp_ajax_seravo_ajax_site_status', 'seravo_ajax_site_status');
      add_action('wp_ajax_seravo_report_http_requests', 'seravo_ajax_report_http_requests');

      if ( getenv('WP_ENV') === 'production' ) {
        seravo_add_postbox(
          'site-info',
          __('Site Information', 'seravo'),
          array( __CLASS__, 'seravo_site_info' ),
          'tools_page_site_status_page',
          'normal'
        );
      }

      // Add HTTP request stats postbox
      seravo_add_postbox(
        'http-request-statistics',
        __('HTTP Request Statistics', 'seravo'),
        array( __CLASS__, 'seravo_http_request_statistics' ),
        'tools_page_site_status_page',
        'normal'
      );

      // Add cache status postbox
      seravo_add_postbox(
        'cache-status',
        __('Cache Status', 'seravo'),
        array( __CLASS__, 'seravo_cache_status' ),
        'tools_page_site_status_page',
        'normal'
      );

      if ( getenv('WP_ENV') === 'production' ) {
        seravo_add_postbox(
          'shadows',
          __('Shadows', 'seravo'),
          array( __CLASS__, 'seravo_shadows_postbox' ),
          'tools_page_site_status_page',
          'side'
        );
      }

      // Add disk usage postbox
      seravo_add_postbox(
        'disk-usage',
        __('Disk Usage', 'seravo'),
        array( __CLASS__, 'seravo_disk_usage' ),
        'tools_page_site_status_page',
        'side'
      );

      seravo_add_postbox(
        'optimize-images',
        __('Optimize Images', 'seravo'),
        array( __CLASS__, 'optimize_images_postbox' ),
        'tools_page_site_status_page',
        'side'
      );
    }

    public static function register_optimize_image_settings() {
      add_settings_section(
        'seravo-optimize-images-settings',
        '',
        array( __CLASS__, 'optimize_images_settings_description' ),
        'optimize_images_settings'
      );

      register_setting('seravo-optimize-images-settings-group', 'seravo-enable-optimize-images');
      register_setting(
        'seravo-optimize-images-settings-group',
        'seravo-image-max-resolution-width',
        array( 'sanitize_callback' => array( __CLASS__, 'sanitize_image_width' ) )
      );
      register_setting(
        'seravo-optimize-images-settings-group',
        'seravo-image-max-resolution-height',
        array( 'sanitize_callback' => array( __CLASS__, 'sanitize_image_height' ) )
      );

      add_settings_field(
        'seravo-images-enabled-field',
        __('Optimize Images', 'seravo'),
        array( __CLASS__, 'seravo_image_enabled_field' ),
        'optimize_images_settings',
        'seravo-optimize-images-settings'
      );
      add_settings_field(
        'seravo-images-max-width-field',
        __('Maximum Image Width (px)', 'seravo'),
        array( __CLASS__, 'seravo_image_max_width_field' ),
        'optimize_images_settings',
        'seravo-optimize-images-settings'
      );
      add_settings_field(
        'seravo-images-max-height-field',
        __('Maximum Image Height (px)', 'seravo'),
        array( __CLASS__, 'seravo_image_max_height_field' ),
        'optimize_images_settings',
        'seravo-optimize-images-settings'
      );

      self::check_default_settings();
    }

    public static function enqueue_site_status_scripts( $page ) {
      wp_register_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js', null, Helpers::seravo_plugin_version(), true);
      wp_register_script('seravo_site_status', plugin_dir_url(__DIR__) . '/js/sitestatus.js', '', Helpers::seravo_plugin_version());
      wp_register_style('seravo_site_status', plugin_dir_url(__DIR__) . '/style/sitestatus.css', '', Helpers::seravo_plugin_version());
      if ( $page === 'tools_page_site_status_page' ) {
        wp_enqueue_style('seravo_site_status');
        wp_enqueue_script('chart-js');
        wp_enqueue_script('color-hash', plugins_url('../js/color-hash.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('reports-chart', plugins_url('../js/reports-chart.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('seravo_site_status');

        $loc_translation = array(
          'no_data'             => __('No data returned for the section.', 'seravo'),
          'failed'              => __('Failed to load. Please try again.', 'seravo'),
          'no_reports'          => __('No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.', 'seravo'),
          'view_report'         => __('View report', 'seravo'),
          'running_cache_tests' => __('Running cache tests...', 'seravo'),
          'cache_success'       => __('HTTP cache working', 'seravo'),
          'cache_failure'       => __('HTTP cache not working', 'seravo'),
          'success'             => __('Success!', 'seravo'),
          'failure'             => __('Failure!', 'seravo'),
          'error'               => __('Error!', 'seravo'),
          'confirm'             => __('Are you sure? This replaces all information in the selected environment.', 'seravo'),
          'ajaxurl'             => admin_url('admin-ajax.php'),
          'ajax_nonce'          => wp_create_nonce('seravo_site_status'),

        );
        wp_localize_script('seravo_site_status', 'seravo_site_status_loc', $loc_translation);
      }
    }

    public static function seravo_http_request_statistics() {
      if ( ! Helpers::is_production() ) {
        __('This feature is available only on live production sites.', 'seravo');
      }
      ?>
      <div style="padding: 0px 15px;">
        <p><?php _e('These monthly reports are generated from the HTTP access logs of your site. All HTTP requests for the site are included, with traffic from both humans and bots. Requests blocked at the firewall level (for example during a DDOS attack) are not logged. The log files can also be accessed directly on the server at <code>/data/slog/html/goaccess-*.html</code>.', 'seravo'); ?></p>
      </div>
      <div class="http-requests_info_loading" style="padding: 0px;">
        <table class="widefat striped" style="width: 100%; border: none;">
          <thead>
          <tr>
            <th style="width: 25%;"><?php _e('Month', 'seravo'); ?></th>
            <th style="width: 50%;"><?php _e('HTTP Requests', 'seravo'); ?></th>
            <th style="width: 25%;"><?php _e('Report', 'seravo'); ?></th>
          </tr>
          </thead>
          <tbody id="http-reports_table"></tbody>
        </table>
      </div>
      <pre id="http-requests_info"></pre>
      <?php
    }

    public static function seravo_cache_status() {
      ?>
      <h3><?php _e('Redis Transient and Object Cache', 'seravo'); ?></h3>
      <div class="redis_info_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <pre id="redis_info"></pre>
      <h3><?php _e('Long-term HTTP Cache Stats', 'seravo'); ?></h3>
      <div class="longterm_cache_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <pre id="longterm_cache"></pre>
      <h3><?php _e('Nginx HTTP Cache', 'seravo'); ?></h3>
      <div id="front_cache_status">
        <p>
          <?php
          _e('Here you can test the functionality of front cache. Same results can be achieved via command line by running <code>wp-check-http-cache</code> there.', 'seravo');
          ?>
        </p>
        <button type="button" class="button-primary" id="run-cache-tests"><?php _e('Run Tests', 'seravo'); ?></button>
        <div class="seravo-cache-test-result-wrapper">
          <div class="seravo_cache_tests_status front_cache_status">
            <?php _e('Click "Run Tests" to run the cache tests', 'seravo'); ?>
          </div>
          <div class="seravo-cache-test-result">
            <pre id="seravo_cache_tests"></pre>
          </div>
          <div class="seravo_cache_test_show_more_wrapper hidden">
            <a href="" class="seravo_cache_test_show_more"><?php _e('Toggle Details', 'seravo'); ?>
              <div class="dashicons dashicons-arrow-down-alt2" id="seravo_arrow_cache_show_more">
              </div>
            </a>
          </div>
        </div>
      </div>
      <?php
    }

    public static function seravo_disk_usage() {
      ?>
      <p><?php _e('The total size of <code>/data</code> is', 'seravo'); ?>
      <div class="folders_chart_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <pre id="total_disk_usage"></pre>
      </p>
      <p><?php _e('Disk usage by directory', 'seravo'); ?>
      <div class="folders_chart_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <canvas id="pie_chart" style="width: 10%; height: 4vh;"></canvas>
      </p>
      <?php
    }

    public static function seravo_site_info() {
      if ( ! Helpers::is_production() ) {
        __('This feature is available only on live production sites.', 'seravo');
      }

      $site_info = Upkeep::seravo_admin_get_site_info();

      // If you are devloping locally and want to mock a api request, uncomment the code below and add a valid json response
      // $response = '{
      // }';
      // $site_info = json_decode($response, true);

      $plans = array(
        'demo'       => __('Demo', 'seravo'),
        'mini'       => __('WP Mini', 'seravo'),
        'pro'        => __('WP Pro', 'seravo'),
        'business'   => __('WP Business', 'seravo'),
        'enterprise' => __('WP Enterprise', 'seravo'),
      );

      $countries = array(
        'fi'       => __('Finland', 'seravo'),
        'se'       => __('Sweden', 'seravo'),
        'de'       => __('Germany', 'seravo'),
        'us'       => __('USA', 'seravo'),
        'anywhere' => __('No preference', 'seravo'),
      );

      $contact_emails = array();
      if ( isset($site_info['contact_emails']) ) {
        $contact_emails = $site_info['contact_emails'];
      }

      function print_item( $value, $description ) {
        if ( is_array($value) ) {
          echo '<p>' . $description . ': ';
          $mails = implode(', ', $value);
          echo $mails . '</p>';
        } elseif ( ! empty($value) && '1970-01-01' != $value ) {
          echo '<p>' . $description . ': ' . $value . '</p>';
        }
      }

      // Nested arrays need to be checked seperately
      $country = ! empty($site_info['country']) ? $countries[ $site_info['country'] ] : '';

      print_item($site_info['name'], __('Site Name', 'seravo'));
      print_item(date('Y-m-d', strtotime($site_info['created'])), __('Site Created', 'seravo'));
      print_item(date('Y-m-d', strtotime($site_info['termination'])), __('Plan Termination', 'seravo'));
      print_item($country, __('Site Location', 'seravo'));
      print_item($plans[ $site_info['plan']['type'] ], __('Plan Type', 'seravo'));

      if ( isset($site_info['account_manager']) ) {
        print_item(htmlentities($site_info['account_manager']), __('Account Manager', 'seravo'));
      } else {
        echo '<p>' . __('No Account Manager found. Account Manager is only included in Seravo Enterprise plans.', 'seravo') . '</p>';
      }

      print_item($contact_emails, '<a href="tools.php?page=upkeep_page">' . __('Technical Contacts', 'seravo') . '</a>');
    }

    public static function seravo_shadows_postbox() {
      ?>
      <div class="seravo-section">
        <div style="padding: 0px 15px">
          <p><?php _e('Allow easy access to site shadows. Resetting a shadow copies the state of the production site to the shadow. All files under /data/wordpress/ will be replaced and the production database imported. For more information, visit our  <a href="https://seravo.com/docs/deployment/shadows/">Developer documentation</a>.', 'seravo'); ?></p>
          <hr>
        </div>
        <div style="padding: 5px 15px 0 15px">
          <?php
          // Get a list of site shadows
          $api_query = '/shadows';
          $shadow_list = API::get_site_data($api_query);
          if ( is_wp_error($shadow_list) ) {
            die($shadow_list->get_error_message());
          }

          if ( ! empty($shadow_list) ) {
            ?>
            <table id="shadow-table">
              <tr>
                <th><?php _e('Name', 'seravo'); ?></th>
                <td id="shadow-name">
                  <select id="shadow-selector">
                    <option value="" disabled selected hidden><?php _e('Select shadow', 'seravo'); ?></option>
                    <?php
                    foreach ( $shadow_list as $shadow => $shadow_data ) {
                      printf('<option value="%s">%s</option>', $shadow_data['name'], $shadow_data['info']);

                      $shadow_list[$shadow]['domain'] = '';
                      foreach ( $shadow_data['domains'] as $domain ) {
                        if ( $domain['primary'] === $shadow_data['name'] ) {
                          $shadow_list[$shadow]['domain'] = $domain['domain'];
                        }
                      }
                    }
                    ?>
                  </select>
                </td>
              </tr>
              <?php
              function add_shadow( $identifier, $ssh, $created, $domain, $hidden = true ) {
                ?>
                <tbody data-shadow="<?php echo $identifier; ?>" data-domain="<?php echo $domain; ?>" class="<?php echo ($hidden ? 'shadow-hidden ' : ''); ?>shadow-row">
                <tr><th><?php _e('Identifier', 'seravo'); ?></th><td><?php echo $identifier; ?></td></tr>
                <tr><th><?php _e('SSH Port', 'seravo'); ?></th><td><?php echo $ssh; ?></td></tr>
                <tr><th><?php _e('Domain', 'seravo'); ?></th><td><?php echo (empty($domain) ? '-' : $domain); ?></td></tr>
                <tr><th><?php _e('Creation Date', 'seravo'); ?></th><td><?php echo $created; ?></td></tr>
                <?php
                if ( ! empty($identifier) ) {
                  ?>
                  <tr class="data-actions"><th><?php _e('Actions', 'seravo'); ?></th><td><a class="action-link closed" href=""><?php _e('Move Data', 'seravo'); ?><span></span></a></td></tr>
                  <?php
                }
                ?>
                </tbody>
                <?php
              }
              // One empty entry for 'select shadow' in dropdown
              add_shadow('', '', '', ' ', false);
              foreach ( $shadow_list as $shadow_data ) {
                add_shadow($shadow_data['name'], $shadow_data['ssh'], $shadow_data['created'], $shadow_data['domain']);
              }
              ?>
            </table>
            <?php
          } else {
            ?>
            <p style="padding: 15px 15px 0 15px;">
              <?php _e('No shadows found. If your plan is WP Pro or higher, you can request a shadow instance from Seravo admins at <a href="mailto:help@seravo.com"    >help@seravo.com</a>.', 'seravo'); ?>
            </p>
            <?php
          }
          ?>
        </div>
        <div id="shadow-data-actions" class="shadow-hidden">
          <hr>
          <h3 style="margin-top:20px;"><?php _e('Reset shadow from production', 'seravo'); ?></h3>
          <i>production > <span id="shadow-reset-instance">shadow</span></i>
          <p><?php _e('<b>Warning:</b> This will replace everything currently in the <i>/data/wordpress/</i> directory and the database of the shadow with a copy of production site. Be sure to know what you are doing.', 'seravo'); ?></p>
          <form>
            <input type="hidden" name="shadow-reset-production" value="<?php echo str_replace(array( 'https://', 'http://' ), '://', get_home_url()); ?>">
            <table id="shadow-rs-table">
              <tr><th colspan="2"><input type="checkbox" name="shadow-reset-sr" disabled><?php _e('Execute search-replace', 'seravo'); ?></th></tr>
              <tr><td>From: </td><td><input type="text" name="shadow-reset-sr-from" disabled></td></tr>
              <tr><td>To: </td><td><input type="text" name="shadow-reset-sr-to" disabled></td></tr>
            </table>
          </form>
          <div id="shadow-reset-sr-alert" class="shadow-hidden">
            <?php _e("This shadow uses a custom domain. Search-replace can't currently be ran automatically with shadow reset. Please run it manually afterwards with the values above or the shadow can't be accessed. Instructions can be found in <a href='https://help.seravo.com/en/docs/151'>here</a>.", 'seravo'); ?>
          </div>
          <div id="shadow-reset-nosr-alert">
            <?php _e("This shadow doesn't need search-replace to be ran afterwards for it to work.", 'seravo'); ?>
          </div>
          <table class="shadow-reset-row">
            <tr>
              <td><button class="button" id="shadow-reset"><?php _e('Reset from production', 'seravo'); ?></button></td>
              <td id="shadow-reset-status"></td>
            </tr>
          </table>
        </div>
      </div>
      <?php
    }

    public static function seravo_data_integrity() {
      ?>
      <h3>
        <?php _e('WordPress core', 'seravo'); ?>
      </h3>
      <div class="wp_core_verify_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <pre id="wp_core_verify"></pre>
      <h3>Git</h3>
      <div class="git_status_loading">
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <pre id="git_status"></pre>
      <?php
    }

    public static function check_default_settings() {
      // Set the default settings for the user if the settings don't exist in database
      if ( get_option('seravo-image-max-resolution-width') === false || get_option('seravo-image-max-resolution-height') === false ) {
        update_option('seravo-image-max-resolution-width', self::$max_width_default);
        update_option('seravo-image-max-resolution-height', self::$max_height_default);
      }
      if ( get_option('seravo-enable-optimize-images') === false ) {
        update_option('seravo-enable-optimize-images', '');
      }
    }

    public static function seravo_image_max_width_field() {
      $image_max_width = get_option('seravo-image-max-resolution-width');
      echo '<input type="text" class="' . self::get_input_field_attributes()[0] . '" name="seravo-image-max-resolution-width"' . self::get_input_field_attributes()[1] . '
        placeholder="' . __('Width', 'seravo') . '" value="' . $image_max_width . '">';
    }

    public static function seravo_image_max_height_field() {
      $image_max_height = get_option('seravo-image-max-resolution-height');
      echo '<input type="text" class="' . self::get_input_field_attributes()[0] . '" name="seravo-image-max-resolution-height" ' . self::get_input_field_attributes()[1] . ' placeholder="'
        . __('Height', 'seravo') . '" value="' . $image_max_height . '">';
    }

    public static function seravo_image_enabled_field() {
      echo '<input type="checkbox" name="seravo-enable-optimize-images" id="enable-optimize-images" ' . checked('on', get_option('seravo-enable-optimize-images'), false) . '>';
    }

    public static function optimize_images_settings_description() {
      echo '<p>' . __('Optimization reduces image file size. This improves the performance and browsing experience of your site.', 'seravo') . '</p>' .
        '<p>' . __('By setting the maximum image resolution, you can determine the maximum allowed dimensions for images.', 'seravo') . '</p>' .
        '<p>' . __('For further information, refer to our <a href="https://help.seravo.com/article/28-seravo-plugin-optimize-images">knowledgebase article</a>.', 'seravo') . '</p>';
    }

    public static function sanitize_image_width( $width ) {
      if ( $width < self::$min_width && $width !== null && get_option('seravo-enable-optimize-images') === 'on' ) {
        add_settings_error(
          'seravo-image-max-resolution-width',
          'invalid-width',
          sprintf(
            // translators: %s numeric value for the minimum image width
            __('The minimum width for image optimisation is %1$s px. Setting suggested width of %2$s px.', 'seravo'),
            self::$min_width,
            self::$max_width_default
          )
        );
        return self::$max_width_default;
      }
      return $width;
    }

    public static function sanitize_image_height( $height ) {
      if ( $height < self::$min_height && $height !== null && get_option('seravo-enable-optimize-images') === 'on' ) {
        add_settings_error(
          'seravo-image-max-resolution-height',
          'invalid-height',
          // translators: %s numeric value for the minimum image height
          sprintf(__('The minimum height for image optimisation is %1$s px. Setting suggested height of %2$s px.', 'seravo'), self::$min_height, self::$max_height_default)
        );
        return self::$max_height_default;
      }
      return $height;
    }

    public static function get_input_field_attributes() {
      if ( get_option('seravo-enable-optimize-images') === 'on' ) {
        return array( 'max-resolution-field', '' );
      }
      return array( 'max-resolution-field', 'disabled=""' );
    }

    public static function optimize_images_postbox() {
      settings_errors();
      echo '<form method="post" action="options.php" class="seravo-general-form">';
      settings_fields('seravo-optimize-images-settings-group');
      do_settings_sections('optimize_images_settings');
      submit_button(__('Save', 'seravo'), 'primary', 'btnSubmit');
      echo '</form>';
    }
  }

  Site_Status::load();
}
