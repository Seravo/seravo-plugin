<?php
/**
 * Plugin name: WP Login Log
 * Description: Logs all login attempts at wp-login.php for security reasons
 */

namespace Seravo;

if ( ! class_exists('LoginLog') ) {
  class LoginLog {

    public static function load() {

      // At least the username must be correct for 'wp_authenticate_user' to run,
      // so it isn't good enough for our log, which also should log brute force
      // attacks. Instead use 'login_redirect' which is fired right after
      // wp_signon has finished. Unfortunately it also fires on every single
      // wp-login.php load, so we shall not process it unless we really detect a
      // login in progress.
      add_filter( 'login_redirect', array( __CLASS__, 'wp_login_redirect_log' ), 10, 3 );

    }

    public static function wp_login_redirect_log( $redirect_to, $requested_redirect_to, $user ) {

      // Bail out quickly if username and password were not sent on this load
      if ( ! isset($_POST['log']) || empty($_POST['log']) ||
           ! isset($_POST['pwd']) || empty($_POST['pwd']) ) {
        return $redirect_to;
      }

      // Check if login was successful record username
      if ( is_wp_error($user) ) {
        $login_status = 'FAIL';
        $status_code = 401;
        $remote_user = sanitize_user($_POST['log']);
      } else {
        $login_status = 'SUCCESS';
        $status_code = 200;
        $remote_user = $user->user_login;
      }

      // Mimic our extended Nginx log format:
      //   '$remote_addr - $remote_user [$time_local] "$request" '
      //   '$status $body_bytes_sent "$http_referer" '
      //   '"$http_user_agent" $upstream_cache_status "$http_x_forwarded_for"';
      //   ==>
      //   192.168.140.1 - - [27/Mar/2017:00:11:53 +0000] "GET /wp-admin/css/login.css?ver=4.7.3 HTTP/1.1"
      //   200 4148 "https://wordpress.local/wp-login.php"
      //   "Mozilla/5.0 (X11; Linux x86_64) (KHTML, like Gecko) Chrome/49.0.2623.87" - "-"

      $remote_addr = $_SERVER['REMOTE_ADDR'];
      $time_local = date('j/M/Y:H:i:s O');
      $request = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
      $http_referer = $_SERVER['HTTP_REFERER'];
      $http_user_agent = $_SERVER['HTTP_USER_AGENT'];
      $log_directory = dirname( ini_get('error_log') );
      $login_log_file = $log_directory . '/wp-login.log';

      // Write the log to disk if the log file exists
      if ( file_exists( $login_log_file ) ) {
          $log_fp = fopen( $login_log_file );
          fwrite( $log_fp, "$remote_addr - $remote_user [$time_local] \"$request\" " .
                           "$status_code 1000 \"$http_referer\" " .
                           "\"$http_user_agent\" $login_status " .
                           "\n"
          );
          fclose( $log_fp );
      }

      return $redirect_to;
    }

  }

  LoginLog::load();
}
