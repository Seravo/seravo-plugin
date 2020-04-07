<?php
/**
 * Plugin name: WP Login Log
 * Description: Logs all login attempts at wp-login.php for security reasons
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('LoginLog') ) {
  class LoginLog {

    public static function load() {

      // At least the username must be correct for 'wp_authenticate_user' to run,
      // so it isn't good enough for our log, which also should log brute force
      // attacks. Instead use 'login_redirect' which is fired right after
      // wp_signon has finished. Unfortunately it also fires on every single
      // wp-login.php load, so we shall not process it unless we really detect a
      // login in progress.
      add_filter('login_redirect', array( __CLASS__, 'wp_login_redirect_log' ), 10, 3);

    }

    public static function wp_login_redirect_log( $redirect_to, $requested_redirect_to, $user ) {

      // Bail out quickly if username and password were not sent on this load
      if ( ! isset($_POST['log']) ||
            empty($_POST['log']) ||
            ! isset($_POST['pwd']) ||
            empty($_POST['pwd'])
          ) {
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
      $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
      $http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

      // Finally write the log to disk
      $log_directory = dirname(ini_get('error_log'));
      if ( empty($log_directory) ) {
        // If there is no log directory, just log one directory above WordPress
        // and hope that directory is writeable but not accessible from the web
        $log_directory = '..';
      }
      $log_fp = fopen($log_directory . '/wp-login.log', 'a');
      fwrite($log_fp, "$remote_addr - $remote_user [$time_local] \"$request\" $status_code 1000 \"$http_referer\" \"$http_user_agent\" $login_status \n");
      fclose($log_fp);

      return $redirect_to;
    }

  }

  LoginLog::load();
}
