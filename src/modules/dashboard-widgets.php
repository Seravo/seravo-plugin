<?php
/*
 * Plugin name: Dashboard Widgets
 * Description: Generate WP admin dashboard widgets
 */

 namespace Seravo;

 // Deny direct access to this file
if ( ! defined('ABSPATH') ) {
    die('Access denied!');
}

require_once SERAVO_PLUGIN_SRC . 'modules/login-notification.php';

if ( ! class_exists('Dashboard_Widgets') ) {
  class Dashboard_Widgets {

    /**
     * @var int|null The amount of PHP errors.
     */
    private static $errors;
    /**
     * @var int Major end of life version.
     */
    const EOL_MAJOR = 7;
    /**
     * @var int Major end of life version.
     */
    const EOL_MINOR = 2;

    /**
     * @var float The relative disk usage.
     */
    const LOW_DISK_SPACE_USAGE = 0.9;
    /**
     * @var int Set a transient for 15 minutes.
     */
    const DISK_SPACE_CACHE_TIME = 900;

    /**
     * @return void
     */
    public static function load() {
      // remove wp own PHP nag
      add_action('wp_dashboard_setup', array( __CLASS__, 'remove_wp_php_nag' ));

      if ( current_user_can('administrator') ) {
        // display admin widgets here

        add_action(
          'wp_dashboard_setup',
          function() {
            wp_add_dashboard_widget(
              'seravo-site-widget',
              __('Site Status', 'seravo'),
              array( __CLASS__, 'display_site_status' )
            );
          }
        );

        if ( apply_filters('seravo_dashboard_errors', true) ) {
          self::$errors = Login_Notifications::retrieve_error_count();
        }
        if ( self::$errors > 0 || (PHP_MINOR_VERSION <= self::EOL_MINOR && PHP_MAJOR_VERSION <= self::EOL_MAJOR) ) {
          // display php errors on log and EOL warning notifications
          add_action(
            'wp_dashboard_setup',
            function() {
              wp_add_dashboard_widget(
                'seravo-error-widget',
                __('PHP warnings and errors', 'seravo'),
                array( __CLASS__, 'display_php_warning_widget' )
              );
            }
          );
        }

        if ( self::disk_space_usage()['relative_usage'] >= self::LOW_DISK_SPACE_USAGE ) {
          add_action(
            'wp_dashboard_setup',
            function() {
              wp_add_dashboard_widget(
                'seravo-low-disk-space-widget',
                __('Low disk space', 'seravo'),
                array( __CLASS__, 'display_disk_space_low_warning' )
              );
            }
          );
        }
      }
    }

    /**
     * Remove the WordPress old PHP nag on admin dashboard
     * @return void
     */
    public static function remove_wp_php_nag() {
      remove_meta_box('dashboard_php_nag', 'dashboard', 'normal');
    }

    /**
     * Fetch the full disk space usage, backups and logs excluded. The main logic from sitestatus-ajax.php
     * @return array<string, mixed>
     */
    private static function disk_space_usage() {
      // Directories not counted against plan's quota but can be visible
      // in the front end
      $exclude_dirs = array(
        '--exclude=/data/backups',
        '--exclude=/data/log',
        '--exclude=/data/slog',
      );

      $cached_usage = get_transient('disk_space_usage');
      $data_size = 0;
      $return_code = 0;

      // Get total disk usage
      if ( ! $cached_usage ) {
        $return_code = exec('du -sb /data ' . implode(' ', $exclude_dirs), $data_folder);

        if ( $return_code !== false && ! empty($data_folder) ) {
          // cache only if successful & there's data in it
          set_transient('disk_space_usage', $data_folder, self::DISK_SPACE_CACHE_TIME);
        }
      } else {
        $data_folder = $cached_usage;
      }

      if ( ! empty($data_folder) ) {
        $data_size = preg_split('/\s+/', $data_folder[0]);
        $data_size = $data_size !== false ? $data_size[0] : 0;
      }
      $plan_details = API::get_site_data();

      if ( is_wp_error($plan_details) ) {
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
     * @return void
     */
    public static function display_disk_space_low_warning() {
      $disk_space = self::disk_space_usage();
      $disk_usage_url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=site_status_page#disk_usage_heading" target="_blank">' .
      __('disk space tool', 'seravo') . '</a>';
      $cruft_tool_url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=security_page#cruftfiles_tool" target="_blank">' .
      __('cruft remover tool', 'seravo') . '</a>';
      $msg = wp_sprintf(
        /* translators:
         * %1$s url to the disk space tool
         * %2$s url to the cruft file remover tool
         */
        __('Disk space is running low. You can see more details about the usage on %1$s. You can also check %2$s for excessive files and folders.', 'seravo'),
        $disk_usage_url,
        $cruft_tool_url
      );
      echo '<div>' . $msg . '</div> <br>';
      echo __('Disk space in your plan: ', 'seravo') . '<b>' . $disk_space['plan_limit'] . 'GB </b><br>';
      echo __('Space in use: ', 'seravo') . '<b>' . Helpers::human_file_size($disk_space['disk_usage']) . '</b>';
    }

    /**
     * Display the amount of php-error.log lines that have appeared this week and old PHP version.
     * @return void
     */
    public static function display_php_warning_widget() {

      if ( self::$errors > 0 ) {
        $url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log" target="_blank">php-error.log</a>';
        echo '<h3>' . __('Site Error Count', 'seravo') . '</h3>';
        $msg = wp_sprintf(
          /* translators:
          * %1$s number of errors in the log
          * %2$s url for additional information
          */
          __('The PHP error log has more than %1$s entries this week. Please see %2$s for details. This is usually a sign that something is broken in the code. The developer of the site should be notified.', 'seravo'),
          self::$errors,
          $url
        );
        echo '<div>' . $msg . '</div> <br>';
      }

      if ( PHP_MAJOR_VERSION <= self::EOL_MAJOR && PHP_MINOR_VERSION <= self::EOL_MINOR ) {
        echo '<h3>' . __('Old PHP Version', 'seravo') . '</h3>';
        $php_version = '<b>' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '</b>';
        $php_version_change_url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=upkeep_page#change_php_version" target="_blank">' .
        __('change php version tool', 'seravo') . '</a>';

        $php_msg = wp_sprintf(
              /* translators:
               * %1$s current php version
               * %2$s link for changing PHP version
               */
          __(
            'You are using end of life PHP version %1$s which will no longer be supported. You can see more details about changing PHP version and 
          checking PHP upgrade combatability on %2$s.',
            'seravo'
          ),
          $php_version,
          $php_version_change_url
        );
        echo '<div>' . $php_msg . '</div>';
      }
    }

    /**
     * Display the site status widget which contains HTTPS statistics and some plan details
     * @return mixed|void
     */
    public static function display_site_status() {
      if ( ! Helpers::is_production() ) {
        return _e('This feature is available only on live production sites.', 'seravo');
      }
      $site_info = API::get_site_data();
      $http_requests_limit = 0;
      echo '<h3>' . __('Plan details', 'seravo') . '</h3>';

      if ( is_wp_error($site_info) ) {
        error_log($site_info->get_error_message());
        $url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log" target="_blank">php-error.log</a>';
        $error_msg = wp_sprintf(
        // translators: %1$s url for additional information
          __('Error on fetching plan details. See more from %1$s.', 'seravo'),
          $url
        );
        echo $error_msg;
      } else {
        $http_requests_limit = $site_info['plan']['httplimit'];
        $disk_space_available = $site_info['plan']['disklimit'] . ' GB';
        echo '<div>' . __('Plan type: ', 'seravo') . $site_info['plan']['type'] . '</div>';
        echo '<div>' . __('HTTP requests / month: ', 'seravo') . $http_requests_limit . '</div>';
        echo '<div>' . __('Disk space in your plan: ', 'seravo') . $disk_space_available . '</div><br>';
      }
      // logic ported to PHP from sitestatus page
      ?>
      <div>
      <h3><?php _e('Monthly HTTP requests', 'seravo'); ?></h3>
      <?php
      $reports = glob('/data/slog/html/goaccess-*.html');

      if ( $reports !== false && ! empty($reports) ) {
        $contact_email_url = '<a href="mailto:help@seravo.com">help@seravo.com</a>';
        $msg = wp_sprintf(
        // translators: %1$s contact email link
          __(
            'These monthly reports are generated from the HTTP access logs of your site. If you have more HTTP requests than your plan allows, contact %1$s to upgrade your <a href="https://seravo.com/plans" target="_blank">plan</a>.',
            'seravo'
          ),
          $contact_email_url
        );
        echo $msg;
        ?>
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
        <?php
        // Track max request value to calculate relative bar widths
        $max_requests = 0;
        $report_month_counter = 0;
        $months = array();

        foreach ( array_reverse($reports) as $report ) {
          if ( $report_month_counter === 4 ) {
            // show only limited amount of monthly http requests
            break;
          }
          ++$report_month_counter;

          $total_requests_string = exec("grep -oE 'total_requests\": ([0-9]+),' {$report}");
          if ( $total_requests_string === false ) {
            continue;
          }

          preg_match('/(\d+)/', $total_requests_string, $total_requests_match);
          $total_requests = (int) $total_requests_match[1];
          if ( $total_requests > $max_requests ) {
            $max_requests = $total_requests;
          }

          $months[] = array(
            'month' => substr($report, 25, 7),
            'requests' => $total_requests,
          );
        }

        foreach ( $months as $month ) {
          $month_date = $month['month'];
          $total_requests = $month['requests'];

          $bar_size = $max_requests > 0 ? $total_requests / $max_requests * 100 : 1;

          $bar_border_color = 'none';

          if ( $http_requests_limit != 0 && $total_requests > $http_requests_limit ) {
            // display warning
            $bar_border_color = 'solid 0.5em #e8ba1b';
          }
          // generate table element of this data
          echo '<tr><td><a href="tools.php?x-accel-redirect&report=' . $month_date . '.html" target="_blank"> ' .
          $month_date . ' </a> </td> <td><div style="background: #44A1CB; border-right: ' . $bar_border_color .
          '; color: #fff; padding: 3px; min-width: ' . $bar_size . '%; width: auto; display: inline-block;"><div style="white-space: nowrap;">' .
          $total_requests . '</div></div></td> <td><a href="tools.php?x-accel-redirect&report=' . $month_date .
          '.html" target="_blank" class="button hideMobile">' . __('View report', 'seravo') .
          '<span aria-hidden="true" class="dashicons dashicons-external" style="line-height: 1.4; padding-left: 3px;"></span></a></td></tr>';
        }
        echo '</table></div>';
      } else {
        _e('The site has no HTTP requests statistics yet.', 'seravo');
        echo '</div><br>';
      }
    }
  }

    Dashboard_Widgets::load();
}
