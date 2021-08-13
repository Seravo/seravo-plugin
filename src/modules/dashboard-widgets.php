<?php
namespace Seravo;

use \Seravo\Logs;
use \Seravo\Compatibility;
use Seravo\Postbox\Template;
use Seravo\Postbox\Component;
use Seravo\Postbox\Requirements;

/**
 * Class DashboardWidgets
 *
 * WordPress admin dashboard widgets are generated here.
 */
class DashboardWidgets {

  /**
   * @var int The amount of PHP errors.
   */
  private static $errors = 0;

  /**
   * @var string End of Life PHP version.
   */
  const PHP_EOL_VERSION = '7.2.34';

  /**
   * @var float The relative disk usage.
   */
  const LOW_DISK_SPACE_USAGE = 0.9;
  /**
   * @var int Set a transient for 15 minutes.
   */
  const DISK_SPACE_CACHE_TIME = 900;

  /**
   * @var int Show limit for the HTTP requests statistics.
   */
  const SHOW_REPORT_LIMIT = 5;

  /**
   * @var \Seravo\Postbox\Postbox[] Dashboard widgets.
   */
  private static $widgets = array();

  /**
   * Initiliaze and load dashboard widgets module.
   * @return void
   */
  public static function load() {
    // Remove the specified WordPress default dashboard widgets.
    \add_action('wp_dashboard_setup', array( __CLASS__, 'remove_wp_dashboard_widgets' ));

    if ( \current_user_can('administrator') ) {
      // display admin widgets here
      \add_action('wp_dashboard_setup', array( __CLASS__, 'init_dashboard_widgets' ));

      if ( (bool) \apply_filters('seravo_dashboard_errors', true) ) {
        $errors = Logs::get_week_error_count();
        self::$errors = $errors === false ? 0 : $errors;
      }
    }
  }

  /**
   * Initialize the dashboard widgets and register them after.
   * @return void
   */
  public static function init_dashboard_widgets() {
    /**
     * Site status & plan details widget
     */
    $site_status = new Postbox\Postbox('sitestatus-widget');
    $site_status->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
    $site_status->set_title(\__('Site Status', 'seravo'));
    $site_status->set_build_func(array( __CLASS__, 'build_site_status' ));
    $site_status->set_data_func(array( __CLASS__, 'get_site_status' ), 600);
    self::$widgets[] = $site_status;

    /**
     * PHP warnings & errors widget
     */
    if ( self::$errors > 0 || \version_compare(Helpers::get_php_version(), self::PHP_EOL_VERSION, '<=') ) {
      $php_widget = new Postbox\Postbox('php-warning-widget');
      $php_widget->set_requirements(
        array(
          Requirements::CAN_BE_PRODUCTION => true,
          Requirements::CAN_BE_STAGING => true,
          Requirements::CAN_BE_DEVELOPMENT => true,
        )
      );
      $php_widget->set_title(\__('PHP warnings and errors', 'seravo'));
      $php_widget->set_build_func(array( __CLASS__, 'build_php_widget' ));
      self::$widgets[] = $php_widget;
    }

    /**
     * Disk space low widget
     */
    if ( Helpers::is_production() && self::get_disk_space_usage()['relative_usage'] >= self::LOW_DISK_SPACE_USAGE ) {
        $disk_space = new Postbox\Postbox('low-disk-space');
        $disk_space->set_requirements(
          array(
            Requirements::CAN_BE_PRODUCTION => true,
          )
        );
        $disk_space->set_title(\__('Low disk space', 'seravo'));
        $disk_space->set_build_func(array( __CLASS__, 'build_disk_space_low' ));
        $disk_space->set_data_func(array( __CLASS__, 'get_disk_space_usage' ), self::DISK_SPACE_CACHE_TIME);
        self::$widgets[] = $disk_space;
    }

    self::register_widgets();
  }

  /**
   * Register and add the dashboard widgets to admin dashboard.
   * @return void
   */
  public static function register_widgets() {
    foreach ( self::$widgets as $widget ) {
      if ( $widget->_is_allowed() ) {
        \wp_add_dashboard_widget(
          $widget->get_id(),
          $widget->get_title(),
          function () use ( $widget ) {
            $widget->_build();
          }
        );
      }
    }
  }

  /**
   * Remove the specified WordPress default dashboard widgets.
   * @return void
   */
  public static function remove_wp_dashboard_widgets() {
    \remove_meta_box('dashboard_php_nag', 'dashboard', 'normal');
  }

  /**
   * Build disk space low widget.
   * @param Component       $base    Base of the postbox.
   * @param Postbox\Postbox $postbox Postbox building.
   * @param array<mixed>    $data    Data returned by data func.
   * @return void
   */
  public static function build_disk_space_low( Component $base, Postbox\Postbox $postbox, $data ) {
    $disk_usage_url = '<a href="' . \get_option('siteurl') . '/wp-admin/tools.php?page=site_status_page#seravo-postbox-disk-usage" target="_blank">' .
      \__('disk space tool', 'seravo') . '</a>';
    $cruft_tool_url = '<a href="' . \get_option('siteurl') . '/wp-admin/tools.php?page=security_page#seravo-postbox-cruftfiles" target="_blank">' .
      \__('cruft remover tool', 'seravo') . '</a>';
    /* translators:
     * %1$s url to the disk space tool
     * %2$s url to the cruft file remover tool
     */
    $msg = \sprintf(\__('Disk space is running low. You can see more details about the usage on %1$s. You can also check %2$s for excessive files and folders.', 'seravo'), $disk_usage_url, $cruft_tool_url);
    $base->add_child(Template::text($msg));
    $base->add_child(Template::text('<br>' . \__('Disk space in your plan:', 'seravo') . ' <b>' . $data['plan_limit'] . 'GB </b><br>'));
    $base->add_child(Template::text(\__('Space in use:', 'seravo') . ' <b>' . Helpers::human_file_size($data['disk_usage']) . '</b>'));
  }

  /**
   * Fetch the full disk space usage, backups and logs excluded.
   * @return array<string, mixed> Data for disk usage and plan limit.
   */
  public static function get_disk_space_usage() {
    // Directories not counted against plan's quota but can be visible
    // in the front end
    $exclude_dirs = array(
      '--exclude=/data/backups',
      '--exclude=/data/log',
      '--exclude=/data/slog',
    );

    $cached_usage = \get_transient('disk_space_usage');
    $data_size = 0;
    $return_code = 0;

    // Get total disk usage
    if ( $cached_usage === false ) {
      $return_code = Compatibility::exec('du -sb /data ' . \implode(' ', $exclude_dirs), $data_folder);

      if ( $return_code !== false && $data_folder !== array() ) {
        // cache only if successful & there's data in it
        \set_transient('disk_space_usage', $data_folder, self::DISK_SPACE_CACHE_TIME);
      }
    } else {
      $data_folder = $cached_usage;
    }

    if ( $data_folder !== array() ) {
      $data_size = \preg_split('/\s+/', $data_folder[0]);
      $data_size = $data_size !== false ? $data_size[0] : 0;
    }

    $plan_details = API::get_site_data();
    if ( \is_wp_error($plan_details) ) {
      $plan_disk_limit = 0;
    } else {
      $plan_disk_limit = $plan_details['plan']['disklimit']; // in GB
    }

    if ( $plan_disk_limit !== 0 && $data_size !== 0 ) {
      // Calculate the data size in MB
      $data_size_human = ($data_size / 1024) / 1024;
      $relative_disk_space_usage = $data_size_human / ($plan_disk_limit * 1000);
    } else {
      $relative_disk_space_usage = 0;
    }

    return array(
      'relative_usage' => $relative_disk_space_usage,
      'disk_usage' => $data_size,
      'plan_limit' => $plan_disk_limit,
    );
  }

  /**
   * Build func for PHP notification widget.
   * @param Component $base Base of the postbox building.
   * @return void
   */
  public static function build_php_widget( Component $base ) {
    if ( self::$errors > 0 ) {
      $url = '<a href="' . \get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log" target="_blank">php-error.log</a>';
      /* translators:
       * %1$s number of errors in the log
       * %2$s url for additional information
       */
      $msg = \sprintf(\__('The PHP error log has more than %1$s entries this week. Please see %2$s for details. This is usually a sign that something is broken in the code. The developer of the site should be notified.', 'seravo'), self::$errors, $url);
      $base->add_child(Template::section_title(\__('Site Error Count', 'seravo')));
      $base->add_child(Template::paragraph($msg));
    }

    if ( \version_compare(Helpers::get_php_version(), self::PHP_EOL_VERSION, '<=') ) {
      $base->add_child(Template::section_title(\__('Old PHP Version', 'seravo')));
      $php_version = '<b>' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '</b>';
      $php_version_change_url = '<a href="' . \get_option('siteurl') . '/wp-admin/tools.php?page=upkeep_page#seravo-postbox-change-php-version" target="_blank">' .
        \__('change php version tool', 'seravo') . '</a>';
      /* translators:
       * %1$s current php version
       * %2$s link for changing PHP version
       */
      $php_msg = \sprintf(\__('You are using end of life PHP version %1$s which will no longer be supported. You can see more details about changing PHP version and checking PHP upgrade combatability on %2$s.', 'seravo'), $php_version, $php_version_change_url);
      $base->add_child(Template::paragraph($php_msg));
    }
  }

  /**
   * Build site status widget.
   * @param Component       $base    Base of the postbox.
   * @param Postbox\Postbox $postbox Postbox building.
   * @param array<mixed>    $data    Data returned by data func.
   * @return void
   */
  public static function build_site_status( Component $base, Postbox\Postbox $postbox, $data ) {
    if ( isset($data['error']) ) {
      $base->add_child(Template::error_paragraph($data['error']));
      return;
    }

    $base->add_children(
      array(
        Template::section_title(\__('Plan details', 'seravo')),
        Template::text(\__('Plan type:') . ' <b>' . $data['plan_type'] . '</b>'),
        Template::text(\__('HTTP requests / month:') . ' <b>' . $data['plan_limit'] . '</b>'),
        Template::text(\__('Disk space in your plan:') . ' <b>' . $data['disk_space'] . '</b>'),
        Component::from_raw('<br>'),
        Template::section_title(\__('Monthly HTTP requests', 'seravo')),
        Template::paragraph(\__('These monthly reports are generated from the HTTP access logs of your site. If you have more HTTP requests than your plan allows, please contact <a href="mailto:help@seravo.com">help@seravo.com</a> to upgrade your <a href="https://seravo.com/plans" target="_blank">plan</a>.', 'seravo')),
        $data['http_stats'],
      )
    );
  }

  /**
   * Data func for site status widget.
   * @return array<mixed>
   */
  public static function get_site_status() {
    $data = array();
    $site_info = API::get_site_data();

    if ( \is_wp_error($site_info) ) {
      \error_log($site_info->get_error_message());
      $url = '<a href="' . \get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log" target="_blank">php-error.log</a>';
      // translators: %1$s url for additional information
      $error_msg = \sprintf(\__('Error on fetching plan details. See more from %1$s.', 'seravo'), $url);
      $data['error'] = $error_msg;

      return $data;
    }
    $http_requests_limit = $site_info['plan']['httplimit'];
    $disk_space_available = $site_info['plan']['disklimit'] . ' GB';
    $data['plan_type'] = $site_info['plan']['type'];
    $data['plan_limit'] = $http_requests_limit;
    $data['disk_space'] = $disk_space_available;

    // fetch the http requests
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

        if ( \count($months) === self::SHOW_REPORT_LIMIT ) {
          // Limit the reports on dashboard
          break;
        }

        $fetch_requests = \preg_match('/(\d+)/', $total_requests_string, $total_requests_match);

        if ( $fetch_requests !== 1 ) {
          continue;
        }

        $total_requests = (int) $total_requests_match[1];
        if ( $total_requests > $max_requests ) {
          $max_requests = $total_requests;
        }

        $month = Compatibility::substr($report, 25, 7);

        if ( $month === false ) {
          continue;
        }
        $stats_link = 'tools.php?x-accel-redirect&report=' . $month . '.html';
        $min_width = ($max_requests > 0 ? $total_requests / $max_requests * 100 : 1);
        $border = ($http_requests_limit != 0 && $total_requests > $http_requests_limit) ? 'border-right: solid 0.5em #e8ba1b;' : '';

        $months[] = array(
          'month' => Template::link($month, $stats_link, $month, 'link')->to_html(),
          'requests' => '<div class="statistics" style="min-width: ' . $min_width . '%;' . $border . '">' . $total_requests . '</div>',
          'span' => Template::button_link_with_icon($stats_link, \__('View report', 'seravo'))->to_html(),
        );
      }
      $statistics = Template::table_view('widefat striped', 'th', 'td', $column_titles, $months);
    } else {
      $statistics = Template::error_paragraph(\__('The site has no HTTP requests statistics yet.', 'seravo'));
    }
    $data['http_stats'] = $statistics;

    return $data;
  }

}
