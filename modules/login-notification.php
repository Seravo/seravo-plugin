<?php
/*
 * Plugin name: Login Notifications
 * Description: Show notifications to admin when logging in: last WordPress login and site error count (php-error.log)
 */
namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once __DIR__ . '/logs.php';

if ( ! class_exists('Login_Notifications') ) {
  class Login_Notifications {

    private static $login;
    private static $errors;

    // How many rows will be retrieved when reading logs, affects dashboard load time
    private static $max_rows = 200;

    public static function load() {
      add_action('load-index.php', array( __CLASS__, 'retrieve_notification_data' ));
      add_action('load-profile.php', array( __CLASS__, 'retrieve_notification_data' ));
    }

    /**
    * Retreives login notification data when loading the dashboard page.
    */
    public static function retrieve_notification_data() {
      wp_enqueue_style('login-notification', plugin_dir_url(__DIR__) . 'style/login-notification.css', '', Helpers::seravo_plugin_version());

      // Retrieve last login notification only if the user has just logged in
      if ( isset($_SERVER['HTTP_REFERER']) ) {
        if ( apply_filters('seravo_dashboard_login', true) && strpos($_SERVER['HTTP_REFERER'], 'wp-login.php') !== false ) {
          self::$login = self::retrieve_last_login();
        }
      }

      // Display logins and/or errors if retrieved succesfully
      if ( ! empty(self::$login) ) {
        add_action('admin_notices', array( __CLASS__, 'display_admin_logins_notification' ));
      }

      // Show site error count dashboard widget only to site admins
      if ( current_user_can('administrator') ) {

        if ( apply_filters('seravo_dashboard_errors', true) ) {
          self::$errors = self::retrieve_error_count();
        }

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
      }
    }

    /**
    * Get the amount of php-error.log lines that have been added this week. The
    * first day of the week is retreived from WordPress settings.
    *
    * @return int The amount of php-error.log lines appended this week
    */
    public static function retrieve_error_count() {
      // Check the first day of week from wp options, and transform to last day of week
      $wp_first_day = get_option('start_of_week');
      if ( $wp_first_day === 0 ) {
        $last_day_int = 6;
      } else {
        $last_day_int = $wp_first_day - 1;
      }
      $days = array(
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
      );
      $last_day_of_week = strtotime('last ' . $days[ $last_day_int ]);

      $log_read = Logs::read_log_lines_backwards('/data/log/php-error.log', -1, self::$max_rows);

      // Display an error if the log file is abnormal
      if ( $log_read['status'] === 'LARGE_LOG_FILE' ) {
        // Display admin dashboard error message
        add_action(
          'wp_dashboard_setup',
          function() {
            wp_add_dashboard_widget(
              'seravo-error-widget',
              __('Large error log', 'seravo'),
              array( __CLASS__, 'display_admin_log_read_notification' )
            );
          }
        );
      } elseif ( $log_read['status'] === 'BAD_LOG_FILE' ) {
        add_action(
          'wp_dashboard_setup',
          function() {
            wp_add_dashboard_widget(
              'seravo-error-widget',
              __('Error log read failure', 'seravo'),
              array( __CLASS__, 'display_admin_broken_log_read_notification' )
            );
          }
        );
        return 0;
      } elseif ( $log_read['status'] === 'NO_LOG_FILE' ) {
        return 0;
      }

      // Read and reverse the php error logfile
      $output_reversed = array_reverse($log_read['output']);

      $php_errors = 0;
      // Loop through all the log lines
      foreach ( $output_reversed as $line ) {
        // Split the line from spaces and retrieve the error date from line
        $output_array = explode(' ', $line);
        $date_str = substr($output_array[0], 1, strlen($output_array[0]));

        // Just jump over the lines that don't contain dates, add an error though
        if ( preg_match('/^(0[1-9]|[1-2][0-9]|3[0-1])-([a-z]|[A-Z]){3}-[0-9]{4}.*$/', $date_str) ) {
          // Return the amount of errors if the date is already from the previous week
          $date = strtotime($date_str);
          if ( $date <= $last_day_of_week ) {
            break;
          }
        }
        ++$php_errors;
      }
      return $php_errors;
    }

    /**
    * Get the last login details from the current user logged into WordPress.
    *
    * @return array Previous login ip address and date, or empty array if not found
    */
    public static function retrieve_last_login() {
      // Read login log file and reverse it
      $log_read = Logs::read_log_lines_backwards('/data/log/wp-login.log', -1, self::$max_rows);
      if ( ! $log_read ) {
        return;
      }

      $output_reversed = array_reverse($log_read['output']);

      $already_skipped = false;

      foreach ( $output_reversed as $line ) {
        preg_match('/^(?<ip>[.:0-9a-f]+) - (?<name>[\w\-_.*@ ]+) \[(?<datetime>[\d\/\w: +]+)\] .* (?<status>[A-Z]+$)/', $line, $entry);

        $is_current_user_login = (get_userdata(wp_get_current_user()->ID)->user_login === $entry['name']);

        // Handle only successful logins
        if ( $entry['status'] == 'SUCCESS' && $is_current_user_login ) {
          // Skip the first login from the current user as it is most likely the latest
          if ( ! $already_skipped ) {
            $already_skipped = true;
            continue;
          }

          // Fetch login IP and the reverse domain name
          $ip = $entry['ip'];
          $domain = gethostbyaddr($ip);

          // Fetch login date and time
          $timezone = get_option('timezone_string');
          $datetime = \DateTime::createFromFormat('d/M/Y:H:i:s T', $entry['datetime']);
          $datetime->setTimezone(new \DateTimeZone(empty($timezone) ? 'UTC' : $timezone));

          return array(
            'date'   => $datetime->format(get_option('date_format')),
            'time'   => $datetime->format(get_option('time_format')),
            'ip'     => $ip,
            'domain' => $domain,
          );
        }
      }
      return array();
    }

    /**
    * Display the latest login of the current user logged in.
    */
    public static function display_admin_logins_notification() {
      $user_data = get_userdata(wp_get_current_user()->ID);

      if ( empty(self::$login['domain']) ) {
        $msg = wp_sprintf(
          /* translators:
          * %1$s username of the current user
          * %2$s date of last login
          * %3$s time of last login
          * %4$s reverse domain of the last login
          */
          __('Welcome, %1$s! Your previous login was on %2$s at %3$s from %4$s.', 'seravo'),
          $user_data->user_firstname == '' ? $user_data->user_login : $user_data->user_firstname,
          self::$login['date'],
          self::$login['time'],
          self::$login['ip']
        );
      } else {
        $msg = wp_sprintf(
          /* translators:
          * %1$s username of the current user
          * %2$s date of last login
          * %3$s time of last login
          * %4$s IP address of the last login
          */
          __('Welcome, %1$s! Your previous login was on %2$s at %3$s from %4$s.', 'seravo'),
          $user_data->user_firstname == '' ? $user_data->user_login : $user_data->user_firstname,
          self::$login['date'],
          self::$login['time'],
          self::$login['domain']
        );
      }

      echo '<div class="seravo-last-login notice notice-info is-dismissible">' . $msg . '</div>';
    }
    /**
    * Display the amount of php-error.log lines that have appeared this week.
    */
    public static function display_admin_errors_notification() {
      $url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log" target="_blank">php-error.log</a>';
      $msg = wp_sprintf(
        /* translators:
        * %1$s number of errors in the log
        * %2$s url for additional information
        */
        __('The PHP error log has more than %1$s entries this week. Please see %2$s for details. This is usually a sign that something is broken in the code. The developer of the site should be notified.', 'seravo'),
        self::$errors,
        $url
      );
      echo '<div>' . $msg . '</div>';
    }

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
        echo '<div>' . __('Plan type: ', 'seravo') . $site_info['plan']['type'] . '</div>';
        echo '<div>' . __('HTTP requests / month: ', 'seravo') . $http_requests_limit . '</div><br>';
      }
      // logic ported to PHP from sitestatus page
      ?>
      <div>
      <h3><?php _e('Monthly HTTP requests', 'seravo'); ?></h3>
      <?php
      $reports = glob('/data/slog/html/goaccess-*.html');

      if ( count($reports) !== 0 ) {
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

        foreach ( $reports as $report ) {
          if ( $report_month_counter === 4 ) {
            // show only limited amount of monthly http requests
            break;
          }
          $report_month_counter++;
          $total_requests_string = exec("grep -oE 'total_requests\": ([0-9]+),' $report");
          preg_match('/([0-9]+)/', $total_requests_string, $total_requests_match);
          $total_requests = intval($total_requests_match[1]);
          if ( $total_requests > $max_requests ) {
            $max_requests = $total_requests;
          }

          array_push(
            $months,
            array(
              'month' => substr($report, 25, 7),
              'requests' => $total_requests,
            )
          );
        }

        foreach ( array_reverse($months) as $month ) {
          $month_date = $month['month'];
          $total_requests = $month['requests'];
          $bar_css = '';

          if ( $max_requests > 0 ) {
            $bar_size = $total_requests / $max_requests * 100;
          } else {
            $bar_size = 1;
          }

          if ( $bar_size <= 10 ) {
            $bar_css = 'auto';
          } else {
            $bar_css = strval($bar_size) . '%';
          }
          $bar_border_color = 'none';

          if ( $http_requests_limit != 0 && $total_requests > $http_requests_limit ) {
            // display warning
            $bar_border_color = 'solid 0.5em #e8ba1b';
          }
          // generate table element of this data
          echo '<tr><td><a href="tools.php?x-accel-redirect&report=' . $month_date . '.html" target="_blank"> ' .
          $month_date . ' </a> </td> <td><div style="background: #44A1CB; border-right: ' . $bar_border_color .
          '; color: #fff; padding: 3px; width: ' . $bar_css . '; display: inline-block;"><div style="white-space: nowrap;">' .
          $total_requests . '</div></div></td> <td><a href="tools.php?x-accel-redirect&report=' . $month_date .
          '.html" target="_blank" class="button hideMobile">' . __('View report', 'seravo') .
          '<span aria-hidden="true" class="dashicons dashicons-external" style="line-height: 1.4; padding-left: 3px;"></span></a></td></tr>';
        }
        echo '</table></div>';
      } else {
        _e('The site has no HTTP requests statistics yet.', 'seravo');
        echo '</div><br>';
      }

      if ( self::$errors > 0 ) {
        echo '<h3>' . __('PHP error status', 'seravo') . '</h3>';
        self::display_admin_errors_notification();
      }
    }

    /**
    * Display an error if the php-error.log file is exceptionally large
    */
    public static function display_admin_large_log_read_notification() {
      $url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log">php-error.log</a>';
      $msg = wp_sprintf(
        __('The PHP error log is abnormally large. This is a sign that something is broken in the code. The developer of the site should be notified.', 'seravo')
      );
      echo '<div>' . $msg . '</div>';
    }

    /**
    * Display an error if there is problem reading the php-error.log file
    */
    public static function display_admin_broken_log_read_notification() {
      $url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log">php-error.log</a>';
      $msg = wp_sprintf(
        __('There is a problem trying to read the php-error.log. This is a sign that something is broken in the code. The developer of the site should be notified.', 'seravo')
      );
      echo '<div>' . $msg . '</div>';
    }
  }
  Login_Notifications::load();
}
