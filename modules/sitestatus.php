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
      add_action('wp_ajax_seravo_speed_test', 'seravo_speed_test');

      if ( getenv('WP_ENV') === 'production' ) {
        seravo_add_postbox(
          'site-info',
          __('Site Information', 'seravo'),
          array( __CLASS__, 'seravo_site_info' ),
          'toplevel_page_toolkit_index_page',
          'normal'
        );
      }

      // Add HTTP request stats postbox
      seravo_add_postbox(
        'http-request-statistics',
        __('HTTP Request Statistics', 'seravo'),
        array( __CLASS__, 'seravo_http_request_statistics' ),
        'toplevel_page_toolkit_index_page',
        'normal'
      );

      // Add cache status postbox
      seravo_add_postbox(
        'cache-status',
        __('Cache Status', 'seravo'),
        array( __CLASS__, 'seravo_cache_status' ),
        'toplevel_page_toolkit_index_page',
        'normal'
      );

      if ( getenv('WP_ENV') === 'production' ) {
        seravo_add_postbox(
          'shadows',
          __('Shadows', 'seravo'),
          array( __CLASS__, 'seravo_shadows_postbox' ),
          'toplevel_page_toolkit_index_page',
          'side'
        );
      }

      // Add disk usage postbox
      seravo_add_postbox(
        'disk-usage',
        __('Disk Usage', 'seravo'),
        array( __CLASS__, 'seravo_disk_usage' ),
        'toplevel_page_toolkit_index_page',
        'side'
      );

      seravo_add_postbox(
        'optimize-images',
        __('Optimize Images', 'seravo'),
        array( __CLASS__, 'optimize_images_postbox' ),
        'toplevel_page_toolkit_index_page',
        'side'
      );

      seravo_add_postbox(
        'speed-test',
        __('Speed test', 'seravo'),
        array( __CLASS__, 'speed_test' ),
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
      wp_register_script('apexcharts-js', 'https://cdn.jsdelivr.net/npm/apexcharts', null, Helpers::seravo_plugin_version(), true);
      wp_register_script('seravo_site_status', plugin_dir_url(__DIR__) . '/js/sitestatus.js', '', Helpers::seravo_plugin_version());
      wp_register_style('seravo_site_status', plugin_dir_url(__DIR__) . '/style/sitestatus.css', '', Helpers::seravo_plugin_version());
      if ( $page === 'toplevel_page_toolkit_index_page' ) {
        wp_enqueue_style('seravo_site_status');
        wp_enqueue_script('apexcharts-js');
        wp_enqueue_script('color-hash', plugins_url('../js/color-hash.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('reports-chart', plugins_url('../js/reports-chart.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('cache-status-charts', plugins_url('../js/cache-status-charts.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
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
          'avg_latency'         => __('Avg latency: ', 'seravo'),
          'avg_cached_latency'  => __('Avg cached latency: ', 'seravo'),
          'latency'             => __('Latency', 'seravo'),
          'cached_latency'      => __('Cached latency', 'seravo'),
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
      <div>
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
      <p><?php _e('Caching decreases the load time of the website. The cache hit rate represents the efficiency of cache usage. Read about caching from the <a href="https://help.seravo.com/article/36-how-does-caching-work/" target="_BLANK">documentation</a> or <a href="https://seravo.com/tag/cache/" target="_BLANK">blog</a>.', 'seravo'); ?></p>
      <h3><?php _e('Object Cache in Redis', 'seravo'); ?></h3>
      <p><?php _e('Persistent object cache implemented with <a href="https://seravo.com/blog/faster-wordpress-with-transients/" target="_BLANK">transients</a> can be stored in Redis. Instructions on how to activate the object cache can be found from the <a href="https://help.seravo.com/article/38-active-object-cache/" target="_BLANK">documentation</a>.', 'seravo'); ?></p>
      <h4><?php _e('Cache hit rate', 'seravo'); ?></h4>
      <div class='redis_info_loading'>
        <img src='/wp-admin/images/spinner.gif'>
      </div>
      <div id='redis-hit-rate-chart'></div>
      <p>
        <?php _e('Expired keys: ', 'seravo'); ?>
        <span id='redis-expired-keys'></span>
        <span class='tooltip dashicons dashicons-info'>
          <span class='tooltiptext'>
            <?php _e('The number of keys deleted.', 'seravo'); ?>
          </span>
        </span>
        <?php _e('<br>Evicted keys: ', 'seravo'); ?>
        <span id='redis-evicted-keys'></span>
        <span class='tooltip dashicons dashicons-info'>
          <span class='tooltiptext'>
            <?php _e('The number of keys being deleted because the memory usage has hit it\'s limit.', 'seravo'); ?>
          </span>
        </span>
      </p>
      <h3><?php _e('HTTP Cache', 'seravo'); ?></h3>
      <p><?php _e('The HTTP cache hit rate is calculated from all Nginx\'s access logs. It describes the long-term cache usage situation.', 'seravo'); ?></p>
      <h4><?php _e('Cache hit rate', 'seravo'); ?></h4>
      <div class='longterm_cache_loading'>
        <img src='/wp-admin/images/spinner.gif'>
      </div>
      <div id='http-hit-rate-chart'></div>
      <p><?php _e('There are <span id=\'http-cache-bypass\'></span> bypasses.', 'seravo'); ?></p>
      <h3><?php _e('Nginx HTTP Cache', 'seravo'); ?></h3>
      <div id='front_cache_status'>
        <p><?php _e('Test the functionality of your site\'s front cache. This can also be done via command line with command <code>wp-check-http-cache</code>.', 'seravo'); ?></p>
        <button type='button' class='button-primary' id='run-cache-tests'><?php _e('Run cache tests', 'seravo'); ?></button>
        <div class='seravo-cache-test-result-wrapper'>
          <div class='seravo_cache_tests_status front_cache_status'>
            <?php _e('Click "Run cache tests" to run the cache tests', 'seravo'); ?>
          </div>
          <div class='seravo-cache-test-result'>
            <pre id='seravo_cache_tests'></pre>
          </div>
          <div class='seravo_cache_test_show_more_wrapper hidden'>
            <a href='' class='seravo_cache_test_show_more'><?php _e('Toggle Details', 'seravo'); ?>
              <div class='dashicons dashicons-arrow-down-alt2' id='seravo_arrow_cache_show_more'>
              </div>
            </a>
          </div>
        </div>
      </div>
      <?php
    }

    public static function seravo_disk_usage() {
      ?>
      <p id="disk_usage_heading">
        <?php
          $api_response = API::get_site_data();
          if ( is_wp_error($api_response) ) {
            die($api_response->get_error_message());
          }
          $max_disk = $api_response['plan']['disklimit']; // in GB
        ?>
        <div id="donut_single" style="width: 30%; float: right"></div>
        <div class="disk_usage_desc">
          <?php _e('Disk space in your plan: ', 'seravo'); ?>
          <span id="maximum_disk_space"><?php echo $max_disk; ?></span>GB
          <br>
          <?php _e('Space in use: ', 'seravo'); ?>
          <span id="total_disk_usage"></span>
          <br>
          <span id="disk_use_notification" style="display: none">
            <?php _e('Disk space low! ', 'seravo'); ?>
            <a href='https://help.seravo.com/article/280-seravo-plugin-site-status#diskusage' target='_BLANK'>
              <?php _e('Read more.', 'seravo'); ?>
            </a>
          </span>
        </div>
        <div class="folders_chart_loading">
          <img src="/wp-admin/images/spinner.gif">
        </div>
      </p>
      <p>
        <hr>
        <br>
        <b>
          <?php _e('Disk usage by directory', 'seravo'); ?>
        </b>
        <div class="folders_chart_loading">
          <img src="/wp-admin/images/spinner.gif">
        </div>
        <div class="pie_container">
          <div id="bars_single" style="width: 100%"></div>
        </div>
      </p>
      <?php _e('Automatic backups don\'t count against your quota.', 'seravo'); ?>
      <br>
      <?php _e('Use <a href="tools.php?page=security_page">cruft remover</a> to remove unnecessary files.', 'seravo'); ?>
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
        <div>
          <p><?php _e('Manage the site shadows.', 'seravo'); ?></p>
          <p><?php _e('<strong>Warning: </strong>Resetting a shadow copies the state of the production site to the shadow. All files under <code>/data/wordpress/</code> will be replaced and the production database imported. For more information, visit our  <a href="https://seravo.com/docs/deployment/shadows/" target="_BLANK">Developer documentation</a>.', 'seravo'); ?></p>
        </div>
        <div>
          <?php
          // Get a list of site shadows
          $api_query = '/shadows';
          $shadow_list = API::get_site_data($api_query);
          if ( is_wp_error($shadow_list) ) {
            die($shadow_list->get_error_message());
          }
          ?>
          <!-- Alerts after shadow reset -->
          <div class="alert" id="alert-success">
            <button class="closebtn">&times;</button>
            <p><?php _e('Success!', 'seravo'); ?></p>
            <!-- Search-replace info -->
            <div class="shadow-reset-sr-alert alert">
              <p><?php _e('Because this shadow uses a custom domain, <strong>please go to the shadow and run search-replace there with the values below</strong> for the shadow to be accessible after reset: ', 'seravo'); ?></p>
              <p>
                <?php
                  _e('<strong>From:</strong> ', 'seravo');
                  echo str_replace(array( 'https://', 'http://' ), '://', get_home_url());
                  _e('<br><strong>To:</strong> ', 'seravo');
                ?>
                ://<span id="shadow-primary-domain"></span>
              </p>
              <p><?php _e('When you\'re in the shadow, run search-replace either on "Tools --> Database --> Search-Replace Tool" or with wp-cli. Instructions can be found from <a href="https://help.seravo.com/en/docs/151" target="_BLANK">documentation</a>.'); ?></p>
            </div>
          </div>
          <div class="alert" id="alert-failure"><button class="closebtn">&times;</button><p><?php _e('Failure!', 'seravo'); ?></p></div>
          <div class="alert" id="alert-error"><button class="closebtn">&times;</button><p><?php _e('Error!', 'seravo'); ?></p></div>
          <?php
          if ( ! empty($shadow_list) ) {
            ?>
            <table id="shadow-table">
              <?php
              foreach ( $shadow_list as $shadow => $shadow_data ) {
                $primary_domain = '';
                // Find primary domain of the shadow
                foreach ( $shadow_data['domains'] as $domain ) {
                  if ( $domain['primary'] === $shadow_data['name'] ) {
                    $primary_domain = $domain['domain'];
                  } else {
                    $primary_domain = '';
                  }
                }
              ?>
                <!-- Two rows per shadow: by default, one visibe and another hidden -->
                <tbody id="<?php echo ($shadow_data['name']); ?>">
                  <tr class="view" >
                    <td class="open-folded"><?php echo $shadow_data['name']; ?></td>
                    <td><button class="button reset"><?php _e('Reset', 'seravo'); ?></button></td>
                    <td class="open-folded" id="shadow-reset-status"></td>
                    <td class="open-folded closed-icon"><span></span></td>
                  </tr>
                  <tr class="fold">
                    <td colspan="4">
                      <!-- More info of the shadow -->
                      <p><?php _e('Port: ', 'seravo'); ?> <?php echo $shadow_data['ssh']; ?></p>
                      <p><?php _e('Creation Date: ', 'seravo'); ?> <?php echo $shadow_data['created']; ?></p>
                      <p><?php _e('Information: ', 'seravo'); ?> <?php echo $shadow_data['info']; ?></p>
                      <p><?php _e('Domain: ', 'seravo'); ?> <?php echo (empty($primary_domain) ? '-' : $primary_domain); ?></p>
                      <!-- Search-replace info -->
                      <form>
                        <div class="shadow-reset-sr-alert shadow-hidden">
                          <p><?php _e('This shadow uses a custom domain. <strong>For the shadow to be accessible after reset, please run search-replace in the shadow with the values below:</strong>', 'seravo'); ?></p>
                          <p>
                            <?php
                              _e('<strong>From:</strong> ', 'seravo');
                              echo str_replace(array( 'https://', 'http://' ), '://', get_home_url());
                              _e('<br><strong>To:</strong> ://', 'seravo');
                              echo $primary_domain;
                            ?>
                          </p>
                          <p><?php _e('When you\'re in the shadow, you can run search-replace either on "Tools --> Database --> Search-Replace Tool" or with wp-cli. Instructions can be found from <a href="https://help.seravo.com/en/docs/151" target="_BLANK">documentation</a>.', 'seravo'); ?></p>
                        </div>
                        <input type="hidden" name="shadow-reset-production" value="<?php echo str_replace(array( 'https://', 'http://' ), '://', get_home_url()); ?>">
                        <input type="hidden" name="shadow-domain" value="<?php echo ($primary_domain); ?>">                        
                        <table class="shadow-rs-table shadow-hidden">
                          <tr><td><input type="text" name="shadow-reset-sr-from" disabled></td></tr>
                          <tr><td><input type="text" name="shadow-reset-sr-to" disabled></td></tr>
                        </table>
                      </form>
                    </td>
                  </tr>
                </tbody>
                <?php
              }
              ?>
            </table>
            <?php
          } else {
            ?>
            <p style="padding: 15px 15px 0 15px;">
              <?php _e('No shadows found. If your plan is WP Pro or higher, you can request a shadow instance from Seravo admins at <a href="mailto:help@seravo.com">help@seravo.com</a>.', 'seravo'); ?>
            </p>
            <?php
          }
          ?>
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
        '<p>' . __('For further information, refer to our <a href="https://help.seravo.com/article/28-seravo-plugin-optimize-images" target="_BLANK">knowledgebase article</a>.', 'seravo') . '</p>';
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

    public static function speed_test() {
      $target_location = isset($_GET['speed_test_target']) ? $_GET['speed_test_target'] : '';
      _e('Speed test measures the time how long it takes for PHP to produce the HTML output for the WordPress page.', 'seravo');
      echo('<br><label for="speed_test_url" class="speed_test_form" for="sr-from"> ' . get_home_url() . '/</label> <input class="speed_test_input" type="text" id="speed_test_url" value="' . $target_location . '"><br>');
      echo('<button type="button" class="button-primary" id="run-speed-test">' . __('Run Speed Test', 'seravo') . '</button>');
      echo('<div id="speed-test-results"></div>');
      echo('<div id="speed-test-error"></div>');
    }


  }

  Site_Status::load();
}
