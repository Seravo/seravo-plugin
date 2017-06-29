<?php
/*
* Plugin name: Login Notifications
* Description: Show notifications to admin when logging in: last WordPress login and site error count (php-error.log)
*/
namespace Seravo;

require_once(__DIR__ . '/logs.php');

if ( ! class_exists('Login_notifications') ) {
    class Login_notifications {

        private static $login;
        private static $errors;
        // How many rows will be retrieved when reading logs, affects dashboard load time
        private static $max_rows = 200;

        public static function load() {
            add_action('load-index.php', array( __CLASS__, 'retrieve_notification_data') );
        }

        public static function retrieve_notification_data() {
            wp_enqueue_style('login-notification', plugin_dir_url(__DIR__) . 'style/login-notification.css');
            // Retrieve last login notification only if the user has just logged in
            if ( isset($_SERVER['HTTP_REFERER']) ) {
                if ( apply_filters('seravo_dashboard_login', true) && strpos($_SERVER['HTTP_REFERER'], 'wp-login.php') !== false ) {
                    self::$login = self::retrieve_last_login();
                }
            }
            if ( apply_filters('seravo_dashboard_errors', true) ) {
                self::$errors = self::retrieve_error_count();
            }
            // Display logins and/or errors if retrieved succesfully
            if ( ! empty(self::$login) ) {
                add_action('admin_notices', array( __CLASS__, 'display_admin_logins_notification') );
            }
            if ( self::$errors > 0 ) {
                add_action('wp_dashboard_setup', function() {
                    wp_add_dashboard_widget('seravo-error-widget', __('Site Error Count', 'seravo'),
                        array( __CLASS__, 'display_admin_errors_notification' ));
                });
            }
        }

        public static function retrieve_error_count() {
            // Check the first day of week from wp options, and transform to last day of week
            $wp_first_day = get_option('start_of_week');
            if ( $wp_first_day === 0) {
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
            $last_day_of_week = strtotime('last ' . $days[$last_day_int]);

            // Read and reverse the php error logfile
            $output = Logs::read_log_lines_backwards('/data/log/php-error.log', -1, self::$max_rows);
            $output_reversed = array_reverse($output);

            $php_errors = 0;
            // Loop through all the log lines
            foreach ( $output_reversed as $line ) {
                // Split the line from spaces and retrieve the error date from line
                $output_array = explode( ' ', $line );
                $date_str = substr($output_array[0], 1, strlen($output_array[0]));

                // Just jump over the lines that don't contain dates, add an error though
                if ( preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-([a-z]|[A-Z]){3}-[0-9]{4}.*$/", $date_str) ) {
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

        public static function retrieve_last_login() {
            // Read login log file and reverse it
            $output = Logs::read_log_lines_backwards('/data/log/wp-login.log', -1, self::$max_rows);
            $output_reversed = array_reverse($output);

            $already_skipped = false;

            foreach ( $output_reversed as $line ) {
                // Skip the first login from the current user because it is most likely the latest,
                // however don't skip any logins from other users
                if ( preg_match('/.*SUCCESS.*/', $line) && preg_match('/.*' . get_userdata(wp_get_current_user()->ID)->user_login . '.*/', $line) && ! $already_skipped ) {
                    $already_skipped = true;
                    continue;
                }
                if ( preg_match(  '/.*SUCCESS.*/', $line ) ) {
                    // Fetch login date from the successful login
                    $output_array = explode(' ', $line);
                    $user = $output_array[2];
                    $date = substr($output_array[3], 1, strlen($output_array[3]));
                    return array(
                        'user' => $user,
                        'date' => $date
                    );
                }
            }
            return array(); // If no previous logins were found
        }

        public static function display_admin_logins_notification() {
            echo '<div class="seravo-last-login notice notice-info is-dismissible">' .
                wp_sprintf(__('Welcome %s!', 'seravo'), get_userdata(wp_get_current_user()->ID)->user_login) . ' ' .
                wp_sprintf(__('Previously logged into WordPress by %s on %s (%s).', 'seravo' ), self::$login['user'],
                self::$login['date'], date_default_timezone_get()) . '</div>';
        }

        public static function display_admin_errors_notification() {
            $url = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page' . '"">' .
                __('logs', 'Seravo') . '</a>';
            if ( self::$errors < self::$max_rows ) {
                $msg = wp_sprintf(__('You have a total of %s lines in your error log this week. Check %s for details.',
                    'seravo'), self::$errors, $url);
            } else {
                $msg = wp_sprintf(__('You have at least %s lines in your error log this week. Check %s for details.', 'seravo'),  self::$errors, $url);
            }
            echo '<div>' . $msg  . '</div>';
        }
    }
    Login_notifications::load();
}
