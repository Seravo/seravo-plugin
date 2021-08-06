<?php

namespace Seravo\Page;

use \Seravo\Helpers;
use \Seravo\DashboardWidgets;   // TODO: Not good, get rid of
use \Seravo\SiteHealth;         // TODO: Not good, get rid of (??)
use \Seravo\API;
use \Seravo\Compatibility;
use \Seravo\Page\Upkeep;        // TODO: Not good, get rid of

use \Seravo\Ajax;
use \Seravo\Ajax\AjaxResponse;

use \Seravo\Postbox;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Settings;
use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;
use \Seravo\Postbox\Requirements;

/**
 * Class SiteStatus
 *
 * SiteStatus is a page for general management
 * and info of the site
 */
class SiteStatus extends Toolpage {

  /**
   * @var int Default maximum resolution for images.
   */
  const IMAGE_MAX_SIZE_DEFAULT = 2560;
  /**
   * @var int Minimum resolution for images. Can't be set any lower by user.
   */
  const IMAGE_MIN_SIZE = 500;

  /**
   * @var string Object-cache file location.
   */
  const OBJECT_CACHE_PATH = '/data/wordpress/htdocs/wp-content/object-cache.php';

  /**
   * @var \Seravo\Page\SiteStatus|null Instance of this page.
   */
  private static $instance;

  /**
   * Function for creating an instance of the page. This should be
   * used instead of 'new' as there can only be one instance at a time.
   * @return \Seravo\Page\SiteStatus Instance of this page.
   */
  public static function load() {
    if ( self::$instance === null ) {
      self::$instance = new SiteStatus();
    }

    return self::$instance;
  }

  /**
   * Constructor for SiteStatus. Will be called on new instance.
   * Basic page details are given here.
   */
  public function __construct() {
    parent::__construct(
      \__('Site Status', 'seravo'),
      'tools_page_site_status_page',
      'site_status_page',
      'Seravo\Postbox\seravo_postboxes_page'
    );
  }

  /**
   * Will be called for page initialization. Includes scripts
   * and enables toolpage features needed for this page.
   */
  public function init_page() {
    self::init_postboxes($this);

    self::check_default_settings();
    \add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));
    \add_action('wp_ajax_seravo_ajax_site_status', 'Seravo\seravo_ajax_site_status');

    $this->enable_charts();
    $this->enable_ajax();
  }

  /**
   * Will be called for setting requirements. The requirements
   * must be as strict as possible but as loose as the
   * postbox with the loosest requirements on the page.
   * @param \Seravo\Postbox\Requirements $requirements Instance to set requirements to.
   */
  public function set_requirements( Requirements $requirements ) {
    $requirements->can_be_production = \true;
    $requirements->can_be_staging = \true;
    $requirements->can_be_development = \true;
  }

  /**
   * Register scripts.
   * @param string $screen The current screen.
   * @return void
   */
  public static function enqueue_scripts( $screen ) {
    if ( $screen !== 'tools_page_site_status_page' ) {
      return;
    }

    \wp_enqueue_script('seravo-site-status-js', SERAVO_PLUGIN_URL . 'js/sitestatus.js', array(), Helpers::seravo_plugin_version());
    \wp_enqueue_script('seravo-speedtest-js', SERAVO_PLUGIN_URL . 'js/speedtest.js', array( 'jquery' ), Helpers::seravo_plugin_version());
    \wp_enqueue_style('seravo-site-status-css', SERAVO_PLUGIN_URL . 'style/sitestatus.css', array(), Helpers::seravo_plugin_version());

    $loc_translation = array(
      'no_data'             => \__('No data returned for the section.', 'seravo'),
      'failed'              => \__('Failed to load. Please try again.', 'seravo'),
      'no_reports'          => \__('No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.', 'seravo'),
      'view_report'         => \__('View report', 'seravo'),
      'success'             => \__('Success!', 'seravo'),
      'failure'             => \__('Failure!', 'seravo'),
      'error'               => \__('Error!', 'seravo'),
      'confirm'             => \__('Are you sure? This replaces all information in the selected environment.', 'seravo'),
      'ajaxurl'             => \admin_url('admin-ajax.php'),
      'ajax_nonce'          => \wp_create_nonce('seravo_site_status'),
    );
    \wp_localize_script('seravo-site-status-js', 'seravo_site_status_loc', $loc_translation);
  }

  /**
   * Init postboxes.
   * @param \Seravo\Postbox\Toolpage $page Page to init postboxes to.
   * @return void
   */
  public static function init_postboxes( Toolpage $page ) {
    if ( \getenv('WP_ENV') === 'production' ) {
      \Seravo\Postbox\seravo_add_raw_postbox(
        'shadows',
        \__('Shadows', 'seravo'),
        array( __CLASS__, 'seravo_shadows_postbox' ),
        'tools_page_site_status_page',
        'side'
      );
    }

    /**
     * Site info postbox
     */
    $site_info = new Postbox\Postbox('site-info');
    $site_info->set_title(\__('Site Information', 'seravo'));
    $site_info->set_data_func(array( __CLASS__, 'get_site_info' ), 300);
    $site_info->set_build_func(array( __CLASS__, 'build_site_info' ));
    $site_info->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
    $page->register_postbox($site_info);

    /**
     * HTTP Request Statistics  postbox
     */
    $http_stats = new Postbox\LazyLoader('http-request-statistics');
    $http_stats->set_title(\__('HTTP Request Statistics', 'seravo'));
    $http_stats->set_build_func(array( __CLASS__, 'build_http_statistics' ));
    $http_stats->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
    $http_stats->set_ajax_func(array( __CLASS__, 'get_http_statistics' ));
    $page->register_postbox($http_stats);

    /**
     * Site checks postbox
     */
    $site_checks = new Postbox\FancyForm('site-checks');
    $site_checks->set_title(\__('Site checks', 'seravo'));
    $site_checks->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $site_checks->set_ajax_func(array( __CLASS__, 'run_site_checks' ));
    $site_checks->set_button_text(\__('Run site checks', 'seravo'));
    $site_checks->set_spinner_text(\__(' Running site checks', 'seravo'));
    $site_checks->set_title_text(\__(' Click "Run site checks" to run the tests', 'seravo'));
    $site_checks->add_paragraph(\__('Site checks provide a report about your site health and show potential issues. Checks include for example php related errors, inactive themes and plugins.', 'seravo'));
    $page->register_postbox($site_checks);

    /**
     * Sanitize uploads postbox
     */
    $sanitize_uploads = new Postbox\SettingsForm('sanitize-uploads', 'side');
    $sanitize_uploads->set_title(\__('Sanitize Uploads', 'seravo'));
    $sanitize_uploads->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $sanitize_uploads->add_setting_section(self::get_sanitize_uploads_settings());
    $page->register_postbox($sanitize_uploads);

    /**
     * Disk Usage postbox
     */
    $disk_usage = new Postbox\LazyLoader('disk-usage');
    $disk_usage->set_build_func(array( __CLASS__, 'build_disk_usage' ));
    $disk_usage->use_hr(false);
    $disk_usage->set_title(\__('Disk Usage', 'seravo'));
    $disk_usage->set_ajax_func(array( __CLASS__, 'get_disk_usage' ));
    $disk_usage->set_requirements(
      array(
        Requirements::CAN_BE_PRODUCTION => true,
        Requirements::CAN_BE_STAGING => true,
      )
    );
    $page->register_postbox($disk_usage);

    /**
     * Cache status postbox
     */
    $http_cache = new Postbox\Postbox('cache-status');
    $http_cache->set_title(\__('Cache Status', 'seravo'));
    $http_cache->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $http_cache->set_build_func(array( __CLASS__, 'build_cache_status' ));
    self::init_cache_status($http_cache);
    $page->register_postbox($http_cache);

    /**
     * Speed test postbox
     */
    $speed_test = new Postbox\SimpleForm('speed-test');
    $speed_test->set_title(\__('Speed test', 'seravo'));
    $speed_test->set_build_form_func(array( __CLASS__, 'build_speed_test' ));
    $speed_test->set_ajax_func(array( __CLASS__, 'run_speed_test' ));
    $speed_test->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $speed_test->set_button_text(\__('Run Test', 'seravo'));
    $page->register_postbox($speed_test);

    /**
     * Optimize images postbox
     */
    $optimize_images = new Postbox\SettingsForm('optimize-images', 'side');
    $optimize_images->set_title(\__('Optimize Images', 'seravo'));
    $optimize_images->add_paragraph(\__('Optimization reduces image file size. This improves the performance and browsing experience of your site.', 'seravo'));
    $optimize_images->add_paragraph(\__('By setting the maximum image resolution, you can determine the maximum allowed dimensions for images.', 'seravo'));
    $optimize_images->add_paragraph(\__('By enabling metadata stripping, you can further reduce image sizes by removing metadata. Please note that occasionally metadata can be useful.', 'seravo'));
    $optimize_images->add_paragraph(\__('For further information, refer to our <a href="https://help.seravo.com/article/28-seravo-plugin-optimize-images" target="_BLANK">knowledgebase article</a>.', 'seravo'));
    $optimize_images->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $optimize_images->add_setting_section(self::get_optimize_images_settings());
    $page->register_postbox($optimize_images);
  }

  /**
   * Helper method for initializing cache status postbox.
   * @param Postbox\Postbox $postbox The postbox to init AJAX handlers for.
   * @return void
   */
  public static function init_cache_status( Postbox\Postbox $postbox ) {
    // Object cache hit rate
    $cache_status = new Ajax\LazyLoader('cache-status-ajax');
    $cache_status->use_hr(false);
    $cache_status->set_ajax_func(array( __CLASS__, 'get_cache_hit_rate' ), 600);
    // HTTP cache tests
    $http_cache_wrapper = new Ajax\FancyForm('http-cache-wrapper');
    $http_cache_wrapper->set_button_text(\__('Run cache tests', 'seravo'));
    $http_cache_wrapper->set_spinner_text(\__('Running cache tests...', 'seravo'));
    $http_cache_wrapper->set_title_text(\__('Click "Run cache tests" to run the cache tests', 'seravo'));
    $http_cache_wrapper->set_ajax_func(array( __CLASS__, 'run_cache_tests' ));
    // Object cache missing handler
    $object_cache = new Ajax\SimpleForm('object-cache-status');
    $object_cache->set_ajax_func(array( __CLASS__, 'enable_object_cache' ));
    $object_cache->set_button_text(\__('Enable', 'seravo'));
    $object_cache->set_spinner_flip(true);

    $postbox->add_ajax_handler($object_cache);
    $postbox->add_ajax_handler($http_cache_wrapper);
    $postbox->add_ajax_handler($cache_status);
  }

  /**
   * Set default settings if they haven't been set.
   * @return void
   */
  public static function check_default_settings() {
    if ( \get_option('seravo-image-max-resolution-width') === false || \get_option('seravo-image-max-resolution-height') === false ) {
      \update_option('seravo-image-max-resolution-width', self::IMAGE_MAX_SIZE_DEFAULT);
      \update_option('seravo-image-max-resolution-height', self::IMAGE_MAX_SIZE_DEFAULT);
    }
    if ( \get_option('seravo-enable-optimize-images') === false ) {
      \update_option('seravo-enable-optimize-images', 'off');
    }
    if ( \get_option('seravo-enable-strip-image-metadata') === false ) {
      \update_option('seravo-enable-strip-image-metadata', 'off');
    }
    if ( \get_option('seravo-enable-sanitize-uploads') === false ) {
      \update_option('seravo-enable-sanitize-uploads', 'off');
    }
  }

  /**
   * Get setting section for sanitize uploads postbox.
   * @return \Seravo\Postbox\Settings Setting section instance.
   */
  public static function get_sanitize_uploads_settings() {
    $sanitize_settings = new Settings('seravo-sanitize-uploads-settings');
    $sanitize_settings->add_field(
      'seravo-enable-sanitize-uploads',
      \__('Sanitize uploads', 'seravo'),
      '',
      '<p>' . \__('Special characters in filenames, such as ä, ö or æ, may cause problems with the site.', 'seravo') . '</p>' .
      '<p>' . \__('Toggling this on replaces such characters with other standard letters, like ä -> a, ö -> o or æ -> a when a file is uploaded.', 'seravo') . '</p>',
      Settings::FIELD_TYPE_BOOLEAN,
      'off'
    );
    return $sanitize_settings;
  }

  /**
   * Get setting section for optimize images postbox.
   * @return \Seravo\Postbox\Settings Setting section instance.
   */
  public static function get_optimize_images_settings() {
    $optimize_settings = new Settings('seravo-optimize-images-settings');
    $optimize_settings->add_field('seravo-enable-optimize-images', \__('Optimize Images', 'seravo'), '', '', Settings::FIELD_TYPE_BOOLEAN, 'off');
    $optimize_settings->add_field('seravo-enable-strip-image-metadata', \__('Strip Image Metadata', 'seravo'), '', '', Settings::FIELD_TYPE_BOOLEAN, 'off');
    // Image max width field
    $optimize_settings->add_field(
      'seravo-image-max-resolution-width',
      \__('Maximum Image Width (px)', 'seravo'),
      '',
      '',
      Settings::FIELD_TYPE_INTEGER,
      self::IMAGE_MAX_SIZE_DEFAULT,
      function( $value ) use ( $optimize_settings ) {
        return self::check_image_optimization_resolution($value, $optimize_settings);
      }
    );
    // Image max height field
    $optimize_settings->add_field(
      'seravo-image-max-resolution-height',
      \__('Maximum Image Height (px)', 'seravo'),
      '',
      '',
      Settings::FIELD_TYPE_INTEGER,
      self::IMAGE_MAX_SIZE_DEFAULT,
      function( $value ) use ( $optimize_settings ) {
        return self::check_image_optimization_resolution($value, $optimize_settings);
      }
    );
    return $optimize_settings;
  }

  /**
   * Check that image optimization resolution is in limits.
   * @param string                   $value             Value from the form.
   * @param \Seravo\Postbox\Settings $optimize_settings The optimize setting section.
   * @return int The value to be set.
   */
  public static function check_image_optimization_resolution( $value, $optimize_settings ) {
    $value = $optimize_settings->sanitize_integer_field($value, \get_option('seravo-image-max-resolution-height'));
    if ( $value < self::IMAGE_MIN_SIZE ) {
      $optimize_settings->add_notification(
        'size-under-limit',
        // translators: %1$s is minimum size in pixels and %2$s is the recommended maximum.
        \sprintf(\__('The minimum size for image optimisation is %1$s px. Setting suggested size of %2$s px.', 'seravo'), self::IMAGE_MIN_SIZE, self::IMAGE_MAX_SIZE_DEFAULT)
      );
      return self::IMAGE_MAX_SIZE_DEFAULT;
    }
    return $value;
  }

  /**
   * Build the cache status postbox.
   * @param \Seravo\Postbox\Component $base    Postbox's base element to add children to.
   * @param \Seravo\Postbox\Postbox   $postbox Postbox The box.
   * @return void
   */
  public static function build_cache_status( Component $base, Postbox\Postbox $postbox ) {
    if ( ! \file_exists(self::OBJECT_CACHE_PATH) ) {
      $notice = new Component('', '<table><tr>', '</tr></table>');
      $notice->add_child(new Component(\__('Object cache is currently disabled!', 'seravo'), '<td><b>', '</b></td>'));
      $notice->add_child($postbox->get_ajax_handler('object-cache-status')->get_component()->set_wrapper('<td>', '</td>'));
      $base->add_child(Template::nag_notice($notice, 'notice-error', true));
    }

    $base->add_child(Template::paragraph(\__('Caching decreases the load time of the website. The cache hit rate represents the efficiency of cache usage. Read about caching from the <a href="https://help.seravo.com/article/36-how-does-caching-work/" target="_BLANK">documentation</a> or <a href="https://seravo.com/tag/cache/" target="_BLANK">blog</a>.', 'seravo')));
    $base->add_child(Template::section_title(\__('Object Cache in Redis', 'seravo')));
    $base->add_child(Template::paragraph(\__('Persistent object cache implemented with <a href="https://seravo.com/blog/faster-wordpress-with-transients/" target="_BLANK">transients</a> can be stored in Redis. Instructions on how to activate the object cache can be found from the <a href="https://help.seravo.com/article/38-active-object-cache/" target="_BLANK">documentation</a>.', 'seravo')));

    // Redis & Nginx cache status
    $base->add_child(Component::from_raw('<h4>' . \__('Cache hit rate', 'seravo') . '</h4><div id="redis-hit-rate-chart"></div>'));
    $base->add_child($postbox->get_ajax_handler('cache-status-ajax')->get_component());

    // HTTP cache status wrapper
    $base->add_child(Template::section_title(\__('Nginx HTTP Cache', 'seravo')));
    $base->add_child(Template::paragraph(\__("Test the functionality of your site's front cache. This can also be done via command line with command <code>wp-check-http-cache</code>.", 'seravo')));
    $base->add_child($postbox->get_ajax_handler('http-cache-wrapper')->get_component());
  }

  /**
   * AJAX function for enabling object cache.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function enable_object_cache() {
    $response = new AjaxResponse();
    $object_cache_url = 'https://raw.githubusercontent.com/Seravo/wordpress/master/htdocs/wp-content/object-cache.php';

    // Remove all possible object-cache.php.* files
    $files = \glob(self::OBJECT_CACHE_PATH . '.*');
    if ( $files !== false ) {
      foreach ( $files as $file ) {
        \unlink($file);
      }
    }

    // Get the newest file and write it
    $object_cache_content = \file_get_contents($object_cache_url);
    if ( $object_cache_content === false ) {
      // Downloading failed
      $response->is_success(false);
      $response->set_error(\__('Error with downloading the latest object-cache file. Please try again later.', 'seravo'));
      return $response;
    }

    $object_cache_file = \fopen(self::OBJECT_CACHE_PATH, 'w');
    if ( $object_cache_file === false ) {
      // Failed to open file handle
      $response->is_success(false);
      $response->set_error(\__('Error with writing the latest object-cache file. Please try again later.', 'seravo'));
      return $response;
    }

    $write_object_cache = \fwrite($object_cache_file, $object_cache_content);
    \fclose($object_cache_file);

    if ( $write_object_cache === false ) {
      // Failed to write
      $response->is_success(false);
      $response->set_error(\__('Error with writing the latest object-cache file. Please try again later.', 'seravo'));
      return $response;
    }

    // All good!
    $response->is_success(true);
    $response->set_data(
      array(
        'output' => Template::paragraph(\__('Object cache is now enabled!', 'seravo'), 'success bold')->to_html(),
      )
    );
    return $response;
  }

  /**
   * AJAX function for fetching cache data and generating charts section.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function get_cache_hit_rate() {
    $response = new AjaxResponse();

    // Fetch the redis data
    $redis = new \Redis();
    $redis->connect('127.0.0.1', 6379);
    $stats = $redis->info('stats');

    // Fetch the HTTP cache
    $access_logs = \glob('/data/slog/*_total-access.log');
    if ( $access_logs === false ) {
      // Glob had an error
      $access_logs = array();
    }

    $hit = 0;
    $miss = 0;
    $stale = 0;
    $bypass = 0;

    foreach ( $access_logs as $access_log ) {
      $file = \fopen($access_log, 'r');
      if ( $file !== false ) {
        while ( ! \feof($file) ) {
          $line = \fgets($file);
          if ( $line === false ) {
            continue;
          }

          // " is needed to match the log file
          if ( \strpos($line, '" HIT') !== false ) {
            ++$hit;
          } elseif ( \strpos($line, '" MISS') !== false ) {
            ++$miss;
          } elseif ( \strpos($line, '" STALE') !== false ) {
            ++$stale;
          } elseif ( \strpos($line, '" BYPASS') !== false ) {
            ++$bypass;
          }
        }
      }
    }

    $expired_keys = '<p>' . \__('Expired keys: ', 'seravo') . $stats['expired_keys'] . Template::tooltip(\__('The number of keys deleted.', 'seravo'))->to_html();
    $evicted_keys = '<br>' . \__('Evicted keys: ', 'seravo') . $stats['evicted_keys'] . Template::tooltip(\__("The number of keys being deleted because the memory usage has hit it's limit.", 'seravo'))->to_html() . '</p>';
    $http_hit_rate_title = Template::section_title(\__('HTTP Cache', 'seravo'))->to_html();
    $http_cache_text = Template::paragraph(\__("The HTTP cache hit rate is calculated from all Nginx's access logs. It describes the long-term cache usage situation.", 'seravo'))->to_html();
    $http_hit_rate = $http_hit_rate_title . $http_cache_text . '<h4>' . \__('Cache hit rate', 'seravo') . '</h4><div id="http-hit-rate-chart"></div>';
    $bypasses = '<p>' . \__('Bypasses: ', 'seravo') . $bypass . Template::tooltip(\__('The amount of cache bypasses which occur when requesting a non-cached version of the site.', 'seravo'))->to_html() . '</p>';

    $response->is_success(true);
    $response->set_data(
      array(
        'output' => $expired_keys . $evicted_keys . $http_hit_rate . $bypasses,
        'redis_data' => array(
          'hits' => $stats['keyspace_hits'],
          'misses' => $stats['keyspace_misses'],
        ),
        'http_data' => array(
          'hit' => $hit,
          'miss' => $miss,
          'stale' => $stale,
        ),
      )
    );

    return $response;
  }

  /**
   * AJAX function for running cache tests and wrapping the output.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function run_cache_tests() {
    $response = new AjaxResponse();
    \exec('wp-check-http-cache ' . \get_site_url(), $output);
    \array_unshift($output, '$ wp-check-http-cache ' . \get_site_url());

    $message = \__('HTTP cache not working', 'seravo');
    $status_color = Ajax\FancyForm::STATUS_RED;

    if ( \strpos(\implode("\n", $output), "\nSUCCESS: ") == true ) {
      $message = \__('HTTP cache working', 'seravo');
      $status_color = Ajax\FancyForm::STATUS_GREEN;
    }

    $response->is_success(true);
    $response->set_data(
      array(
        'output' => '<pre>' . \implode("\n", $output) . '</pre>',
        'title' => $message,
        'color' => $status_color,
      )
    );

    return $response;
  }

  /**
   * Build function for the disk usage postbox.
   * @param Component       $base    Postbox base component to add elements.
   * @param Postbox\Postbox $postbox The postbox the func is building.
   * @return void
   */
  public static function build_disk_usage( Component $base, Postbox\Postbox $postbox ) {
    $base->add_child(Template::side_by_side(Component::from_raw('<div id="disk-usage-donut" style="width: 100px;"></div>'), $postbox->get_ajax_handler('disk-usage')->get_component(), 'evenly'));
    $base->add_child(Component::from_raw('<span id="disk-use-notification" style="display: none;">' . \__('Disk space low! ', 'seravo') . '<a href="https://help.seravo.com/article/280-seravo-plugin-site-status#diskusage" target="_BLANK">' . \__('Read more.', 'seravo') . '</a></span>'));
    $base->add_child(Component::from_raw('<hr><b>' . \__('Disk usage by directory', 'seravo') . '</b>'));
    $base->add_child(Component::from_raw('<div id="disk-bars-single" style="width: 100%"></div><hr>'));
    $base->add_child(Template::paragraph(\__("Logs and automatic backups don't count against your quota.", 'seravo') . '<br>' . \__('Use <a href="tools.php?page=security_page#cruftfiles_tool">cruft remover</a> to remove unnecessary files.', 'seravo')));
  }

  /**
   * Helper function for the disk usage AJAX.
   * @return array<string, array<array<string, float|int|string>|string>>
   */
  public static function report_disk_usage() {
    $dir_max_limit = 1000000;
    $dir_threshold = 100000000;

    // Directories not counted against plan's quota but can be visible
    // in the front end
    $exclude_dirs = array(
      '--exclude=/data/backups',
      '--exclude=/data/log',
      '--exclude=/data/slog',
    );
    // Directories not shown in the front-end even if their size
    // exceed $dir_threshold. Produces a list string of the directories
    // in a format accepted by grep:  /data/dir_1\|/data/dir_1\| ...
    $hidden_dirs = \implode(
      '\|',
      array(
        '/data/backups',
      )
    );

    // Get total disk usage
    $cached_usage = \get_transient('disk_space_usage');

    if ( $cached_usage === false ) {
      \exec('du -sb /data ' . \implode(' ', $exclude_dirs), $data_folder);
      \set_transient('disk_space_usage', $data_folder, DashboardWidgets::DISK_SPACE_CACHE_TIME);
    } else {
      $data_folder = $cached_usage;
    }

    list($data_size, $data_name) = \preg_split('/\s+/', $data_folder[0]);

    // Get the sizes of certain directories and directories with the
    // size larger than $dir_threshold, ones in $hidden_dirs will be
    // excluded from the output using grep
    \exec(
      '(
      du --separate-dirs -b --threshold=' . $dir_threshold . ' /data/*/ &&
      du -sb /data/wordpress/htdocs/wp-content/uploads/ &&
      du -sb /data/wordpress/htdocs/wp-content/themes/ &&
      du -sb /data/wordpress/htdocs/wp-content/plugins/ &&
      du -sb /data/wordpress/htdocs/wordpress/wp-includes/ &&
      du -sb /data/wordpress/htdocs/wordpress/wp-admin/ &&
      du -sb /data/redis/ &&
      du -sb /data/reports/ &&
      du -sb /data/db/
      ) | grep -v "' . $hidden_dirs . '" | sort -hr',
      $data_sub
    );

    // Generate sub folder array
    $data_folders = array();
    foreach ( $data_sub as $folder ) {
      list($folder_size, $folder_name) = \preg_split('/\s+/', $folder);
      $folder_name = \str_replace('/data/wordpress/htdocs/wordpress/wp-', '.../wp-', $folder_name);
      $folder_name = \str_replace('/data/wordpress/htdocs/wp-content/', '.../wp-content/', $folder_name);

      if ( $folder_size > $dir_max_limit ) {
        $data_folders[$folder_name] = array(
          'percentage' => (($folder_size / $data_size) * 100),
          'human'      => Helpers::human_file_size((int) $folder_size),
          'size'       => $folder_size,
        );
      }
    }
    // Create output array
    $output = array(
      'data'        => array(
        'human' => Helpers::human_file_size((int) $data_size),
        'size'  => $data_size,
      ),
      'dataFolders' => $data_folders,
    );
    return $output;
  }

  /**
   * AJAX function for Disk Usage postbox.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function get_disk_usage() {
    $response = new AjaxResponse();
    $api_response = API::get_site_data();

    if ( \is_wp_error($api_response) ) {
      \error_log($api_response->get_error_message());
      $response->is_success(false);
      $response->set_error(\__('An API error occured, please try again later.', 'seravo'));
      return $response;
    }

    $disk_usage = self::report_disk_usage();
    $disk_usage['data']['disk_limit'] = $api_response['plan']['disklimit'];
    $output = Template::text(\__('Disk space in your plan: ', 'seravo') . $disk_usage['data']['disk_limit'] . 'GB <br>' . \__('Space in use: ', 'seravo') . $disk_usage['data']['human'], 'space-info')->to_html();

    $response->is_success(true);
    $response->set_data(
      array(
        'data' => $disk_usage['data'],
        'folders' => $disk_usage['dataFolders'],
        'output' => $output,
      )
    );

    return $response;
  }

  /**
   * Build the HTTP Request Statistics postbox.
   * @param Component $base Postbox's base element to add children to.
   * @param Postbox\Postbox $postbox Postbox The box.
   * @return void
   */
  public static function build_http_statistics( Component $base, Postbox\Postbox $postbox ) {
    $base->add_child(Template::paragraph(\__('These monthly reports are generated from the HTTP access logs of your site. All HTTP requests for the site are included, with traffic from both humans and bots. Requests blocked at the firewall level (for example during a DDOS attack) are not logged. The log files can also be accessed directly on the server at <code>/data/slog/html/goaccess-*.html</code>.', 'seravo')));
    $base->add_child($postbox->get_ajax_handler('http-request-statistics')->get_component());
  }

  /**
   * AJAX function for HTTP Request Statistics postbox.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function get_http_statistics() {
    $response = new AjaxResponse();

    $reports = \glob('/data/slog/html/goaccess-*.html');
    if ( $reports === false ) {
      $reports = array();
    }

    if ( $reports !== array() ) {
      $column_titles = array( \__('Month', 'seravo'), \__('HTTP Requests', 'seravo'), \__('Report', 'seravo') );

      // Track max request value to calculate relative bar widths
      $max_requests = 0;
      $months = array();

      foreach ( \array_reverse($reports) as $report ) {
        $total_requests_string = Compatibility::exec("grep -oE 'total_requests\": ([0-9]+),' {$report}");
        if ( $total_requests_string === false ) {
          continue;
        }

        \preg_match('/(\d+)/', $total_requests_string, $total_requests_match);
        $total_requests = (int) $total_requests_match[1];
        if ( $total_requests > $max_requests ) {
          $max_requests = $total_requests;
        }

        $month = \substr($report, 25, 7);
        $stats_link = 'tools.php?x-accel-redirect&report=' . $month . '.html';
        $min_width = ($max_requests > 0 ? $total_requests / $max_requests * 100 : 1);

        $months[] = array(
          'month' => Template::link($month, $stats_link, $month, 'link')->to_html(),
          'requests' => '<div class="statistics" style="min-width: ' . $min_width . '%;">' . $total_requests . '</div>',
          'span' => Template::button_link_with_icon($stats_link, \__('View report', 'seravo'))->to_html(),
        );
      }

      $output = Template::table_view('widefat striped', 'th', 'td', $column_titles, $months)->to_html();
    } else {
      $output = Template::error_paragraph(\__('The site has no HTTP requests statistics yet.', 'seravo'))->to_html();
    }

    $response->is_success(true);
    $response->set_data(
      array(
        'output' => $output,
      )
    );

    return $response;
  }

  /**
   * Build the site-info postbox.
   * @param Component       $base    Postbox's base element to add children to.
   * @param Postbox\Postbox $postbox Postbox The box.
   * @param mixed           $data    Data returned by data func.
   * @return void
   */
  public static function build_site_info( Component $base, Postbox\Postbox $postbox, $data ) {
    if ( isset($data['error']) ) {
      $base->add_child(Template::error_paragraph($data['error']));
      return;
    }

    $base->add_children(
      array(
        isset($data['site_name']) ? Template::paragraph($data['site_name']) : null,
        isset($data['site_created']) ? Template::paragraph($data['site_created']) : null,
        isset($data['termination']) ? Template::paragraph($data['termination']) : null,
        isset($data['country']) ? Template::paragraph($data['country']) : null,
        isset($data['plan_type']) ? Template::paragraph($data['plan_type']) : null,
        isset($data['account_manager']) ? Template::paragraph($data['account_manager']) : null,
        isset($data['contacts']) ? Template::paragraph($data['contacts']) : null,
      )
    );
  }

  /**
   * Fetch the plan details. This is a data func for site-info postbox.
   * @return array<string, string>
   */
  public static function get_site_info() {
    $info = \Seravo\Page\Upkeep::seravo_admin_get_site_info();
    $data = array();

    if ( \is_wp_error($info) ) {
      $data['error'] = \__('An API error occured. Please try again later', 'seravo');
      \error_log($info->get_error_message());
      return $data;
    }

    $plans = array(
      'demo'       => \__('Demo', 'seravo'),
      'mini'       => \__('WP Mini', 'seravo'),
      'pro'        => \__('WP Pro', 'seravo'),
      'business'   => \__('WP Business', 'seravo'),
      'enterprise' => \__('WP Enterprise', 'seravo'),
    );
    $countries = array(
      'fi'       => \__('Finland', 'seravo'),
      'se'       => \__('Sweden', 'seravo'),
      'de'       => \__('Germany', 'seravo'),
      'us'       => \__('USA', 'seravo'),
      'anywhere' => \__('No preference', 'seravo'),
    );

    // These values always exists
    $data = array(
      'site_name' => \__('Site Name') . ': ' . $info['name'],
      'site_created' => \__('Site Created') . ': ' . \date('Y-m-d', \strtotime($info['created'])),
      'plan_type' => \__('Plan Type') . ': ' . $plans[$info['plan']['type']],
    );

    // Check for account manger
    if ( isset($info['account_manager']) ) {
      $data['account_manager'] = \__('Account Manager', 'seravo') . ': ' . $info['account_manager'];
    } else {
      $data['account_manager'] = \__('No Account Manager found. Account Manager is only included in Seravo Enterprise plans.', 'seravo');
    }
    // Check for termination date (hide 1970-01-01)
    if ( isset($info['termination']) && $info['termination'] !== '' && \date('Y-m-d', \strtotime($info['termination'])) !== '1970-01-01' ) {
      $data['termination'] = \__('Plan Termination', 'seravo') . ': ' . \date('Y-m-d', \strtotime($info['termination']));
    }
    // Check for location
    if ( isset($info['country']) && $info['country'] !== '' ) {
      $data['country'] = \__('Site Location', 'seravo') . ': ' . $countries[$info['country']];
    }
    // Check for contacts
    $contacts = isset($info['contact_emails']) ? \implode(', ', $info['contact_emails']) : \__('No contacts found', 'seravo');
    $data['contacts'] = '<a href="tools.php?page=upkeep_page#contacts">' . \__('Technical Contacts', 'seravo') . '</a>: ' . $contacts;

    return $data;
  }

  /**
   * @return void
   */
  public static function seravo_shadows_postbox() {
    ?>
    <div class="seravo-section">
      <div>
        <p><?php \_e('Manage the site shadows.', 'seravo'); ?></p>
        <p><?php \_e('<strong>Warning: </strong>Resetting a shadow copies the state of the production site to the shadow. All files under <code>/data/wordpress/</code> will be replaced and the production database imported. For more information, visit our  <a href="https://seravo.com/docs/deployment/shadows/" target="_BLANK">Developer documentation</a>.', 'seravo'); ?></p>
      </div>
      <div>
        <?php
        // Get a list of site shadows
        $api_query = '/shadows';
        $shadow_list = API::get_site_data($api_query);
        if ( \is_wp_error($shadow_list) ) {
          die($shadow_list->get_error_message());
        }
        ?>
        <!-- Alerts after shadow reset -->
        <div class="alert" id="alert-success">
          <button class="closebtn">&times;</button>
          <p><?php \_e('Success!', 'seravo'); ?></p>
          <!-- Search-replace info -->
          <div class="shadow-reset-sr-alert alert">
            <p><?php \_e('Because this shadow uses a custom domain, <strong>please go to the shadow and run search-replace there with the values below</strong> for the shadow to be accessible after reset: ', 'seravo'); ?></p>
            <p>
              <?php
                \_e('<strong>From:</strong> ', 'seravo');
                echo \str_replace(array( 'https://', 'http://' ), '://', \get_home_url());
                \_e('<br><strong>To:</strong> ', 'seravo');
              ?>
              ://<span id="shadow-primary-domain"></span>
            </p>
            <p><?php \_e('When you\'re in the shadow, run search-replace either on "Tools --> Database --> Search-Replace Tool" or with wp-cli. Instructions can be found from <a href="https://help.seravo.com/en/docs/151" target="_BLANK">documentation</a>.', 'seravo'); ?></p>
          </div>
        </div>
        <div class="alert" id="alert-failure"><button class="closebtn">&times;</button><p><?php \_e('Failure!', 'seravo'); ?></p></div>
        <div class="alert" id="alert-timeout"><button class="closebtn">&times;</button><p><?php \_e('The shadow reset is still running on the background. You should check the status of the shadow after a few minutes. If there are problems with the shadow, see the documentation from the link above.', 'seravo'); ?></p></div>
        <div class="alert" id="alert-error"><button class="closebtn">&times;</button><p><?php \_e('Error!', 'seravo'); ?></p></div>
        <?php
        if ( $shadow_list !== array() ) {
          ?>
          <table id="shadow-table">
            <?php
            foreach ( $shadow_list as $shadow_data ) {
              $primary_domain = '';
              // Find primary domain of the shadow
              foreach ( $shadow_data['domains'] as $domain ) {
                $primary_domain = $domain['primary'] === $shadow_data['name'] ? $domain['domain'] : '';
              }
            ?>
              <!-- Two rows per shadow: by default, one visibe and another hidden -->
              <tbody id="<?php echo ($shadow_data['name']); ?>">
                <tr class="view" >
                  <td class="open-folded"><?php echo $shadow_data['name']; ?></td>
                  <td><button class="button reset"><?php \_e('Reset', 'seravo'); ?></button></td>
                  <td class="open-folded" id="shadow-reset-status"></td>
                  <td class="open-folded closed-icon"><span></span></td>
                </tr>
                <tr class="fold">
                  <td colspan="4">
                    <!-- More info of the shadow -->
                    <p><?php \_e('Port: ', 'seravo'); ?> <?php echo $shadow_data['ssh']; ?></p>
                    <p><?php \_e('Creation Date: ', 'seravo'); ?> <?php echo $shadow_data['created']; ?></p>
                    <p><?php \_e('Information: ', 'seravo'); ?> <?php echo $shadow_data['info']; ?></p>
                    <p><?php \_e('Domain: ', 'seravo'); ?> <?php echo ($primary_domain === '' ? '-' : $primary_domain); ?></p>
                    <!-- Search-replace info -->
                    <form>
                      <div class="shadow-reset-sr-alert shadow-hidden">
                        <p><?php \_e('This shadow uses a custom domain. <strong>For the shadow to be accessible after reset, please run search-replace in the shadow with the values below:</strong>', 'seravo'); ?></p>
                        <p>
                          <?php
                            \_e('<strong>From:</strong> ', 'seravo');
                            echo \str_replace(array( 'https://', 'http://' ), '://', \get_home_url());
                            \_e('<br><strong>To:</strong> ://', 'seravo');
                            echo $primary_domain;
                          ?>
                        </p>
                        <p><?php \_e('When you\'re in the shadow, you can run search-replace either on "Tools --> Database --> Search-Replace Tool" or with wp-cli. Instructions can be found from <a href="https://help.seravo.com/en/docs/151" target="_BLANK">documentation</a>.', 'seravo'); ?></p>
                      </div>
                      <input type="hidden" name="shadow-reset-production" value="<?php echo \str_replace(array( 'https://', 'http://' ), '://', \get_home_url()); ?>">
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
            <?php \_e('No shadows found. If your plan is WP Pro or higher, you can request a shadow instance from Seravo admins at <a href="mailto:help@seravo.com">help@seravo.com</a>.', 'seravo'); ?>
          </p>
          <?php
        }
        ?>
      </div>
    </div>
    <?php
  }

  /**
   * @return void
   */
  public static function seravo_data_integrity() {
    ?>
    <h3>
      <?php \_e('WordPress core', 'seravo'); ?>
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

  /**
   * Build form func for the speed test postbox.
   * @param \Seravo\Postbox\Component $base Base component of the postbox to add items.
   * @return void
   */
  public static function build_speed_test( Component $base ) {
    $target_location = isset($_GET['speed_test_target']) ? $_GET['speed_test_target'] : '';
    $label = Component::from_raw('<label for="speed-test-url" class="wrap-anywhere"> ' . \get_home_url() . '/</label>');
    $field = Component::from_raw('<input type="text" style="width: 100%;" placeholder="' . \__('Front Page by Default', 'seravo') . '" id="speed-test-url" name="speed-test-url" value="' . $target_location . '">');
    $clear = Template::button('', 'clear-url', 'notice-dismiss');

    $base->add_child(Template::paragraph(\__('Speed test measures the time how long it takes for PHP to produce the HTML output for the WordPress page.', 'seravo')));
    $base->add_child(Template::n_by_side(array( $label, Template::side_by_side($field, $clear) )));
    $base->add_child(Component::from_raw('<div id="speed-test-results"></div>'));
    $base->add_child(Component::from_raw('<div id="speed-test-error"></div>'));
  }

  /**
   * @return void
   */
  public static function speed_test() {
    $target_location = isset($_GET['speed_test_target']) ? $_GET['speed_test_target'] : '';
    echo ('<p>' . \__('Speed test measures the time how long it takes for PHP to produce the HTML output for the WordPress page.', 'seravo') . '</p>');
    echo('<br><label for="speed_test_url" class="speed_test_form" for="sr-from"> ' . \get_home_url() . '/</label> <input class="speed_test_input" type="text" placeholder="' . \__('Front Page by Default', 'seravo') . '" id="speed_test_url" value="' . $target_location . '"><br>');
    echo('<button type="button" class="button-primary" id="run-speed-test">' . \__('Run Test', 'seravo') . '</button>');
    echo('<div id="speed-test-results"></div>');
    echo('<div id="speed-test-error"></div>');
  }

  /**
   * AJAX function for running speed test.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function run_speed_test() {
    $response = new AjaxResponse();

    // Take location for the speed test from the ajax call. If there is not one, use WP home
    $url = isset($_POST['location']) ? \get_home_url() . '/' . \trim($_POST['location']) : \get_home_url();
    // Make sure there is one / at the end of the url
    $url = \rtrim($url, '/') . '/';

    // use filter_var to make sure the resulting url is a valid url
    if ( \filter_var($url, FILTER_VALIDATE_URL) === false ) {
      $response->is_success(false);
      $response->set_error(\__('Error! Invalid url', 'seravo'));
      return $response;
    }

    // Check whether to test cached version or not. Default not.
    $cached = isset($_POST['cached']) && $_POST['cached'] === 'true';

    // Prepare curl settings which are same for all requests
    $ch = \curl_init($url);
    if ( $ch === false ) {
      $response->is_success(false);
      $response->set_error(\__('Error! Curl not available', 'seravo'));
      return $response;
    }

    \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // equals the command line -k option

    if ( ! $cached ) {
      \curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Pragma: no-cache' ));
    }
    \curl_exec($ch);
    $httpcode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ( \curl_error($ch) !== '' || $httpcode !== 200 ) {
      $response->is_success(false);
      $response->set_error(\__('Error! HTTP response code:', 'seravo') . ' ' . $httpcode);
      return $response;
    }
    $curl_info_arr = \curl_getinfo($ch);
    \curl_close($ch);

    $response->is_success(true);
    $response->set_data(
      array(
        'data' => array(
          'starttransfer_time' => $curl_info_arr['starttransfer_time'],
        ),
      )
    );

    return $response;
  }

  /**
   * AJAX function for Site checks postbox.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function run_site_checks() {
    $response = new AjaxResponse();
    $results = SiteHealth::check_site_status(true);
    $output = $results[0];
    $title = $results[1];
    $status_color = $results[2];

    $response->is_success(true);
    $response->set_data(
      array(
        'output' => $output,
        'title' => $title,
        'color' => $status_color,
      )
    );

    return $response;
  }
}
