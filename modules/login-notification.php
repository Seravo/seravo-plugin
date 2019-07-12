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

        if ( self::$errors > 0 ) {
          add_action(
            'wp_dashboard_setup',
            function() {
              wp_add_dashboard_widget(
                'seravo-error-widget',
                __('Site Error Count', 'seravo'),
                array( __CLASS__, 'display_admin_errors_notification' )
              );
            }
          );
        }
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

      // Read and reverse the php error logfile
      $output = Logs::read_log_lines_backwards('/data/log/php-error.log', -1, self::$max_rows);
      if ( ! $output ) {
        return 0;
      }
      $output_reversed = array_reverse($output);

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
      $output = Logs::read_log_lines_backwards('/data/log/wp-login.log', -1, self::$max_rows);
      if ( ! $output ) {
        return;
      }
      $output_reversed = array_reverse($output);

      $already_skipped = false;

      foreach ( $output_reversed as $line ) {
        $output_array = explode(' ', $line);
        $is_successful_login = (bool) preg_match('/.*SUCCESS.*/', $output_array[ count($output_array) - 1 ]);
        $is_current_user_login = (get_userdata(wp_get_current_user()->ID)->user_login === $output_array[2]);

        // Handle only successful logins
        if ( $is_successful_login && $is_current_user_login ) {
          // Skip the first login from the current user as it is most likely the latest
          if ( ! $already_skipped ) {
            $already_skipped = true;
            continue;
          }

          // Fetch login ip and date
          $ip = $output_array[0];
          $date = substr($output_array[3], 1, strlen($output_array[3]));
          return array(
            'ip'   => $ip,
            'date' => $date,
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
      $msg = wp_sprintf(
        /* translators:
        * %1$s username of the current user
        * %2$s datetime of last login
        * %3$s timezone used to represent the datetime of the last login
        * %4$s IP address of the last login
        */
        __('Welcome, %1$s! Your previous login was on %2$s (%3$s) from %4$s.', 'seravo'),
        $user_data->user_firstname == '' ? $user_data->user_login : $user_data->user_firstname,
        preg_replace('/:/', ' ', self::$login['date'], 1),
        date_default_timezone_get(),
        self::$login['ip']
      );
      echo '<div class="seravo-last-login notice notice-info is-dismissible">' . $msg . '</div>';
    }
    /**
    * Display the amount of php-error.log lines that have appeared this week.
    */
    public static function display_admin_errors_notification() {
      $url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log">php-error.log</a>';
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
  }
  Login_Notifications::load();
}
