<?php

namespace Seravo\Module;

/**
 * Class LoginLog
 *
 * Logs all login attempts at wp-login.php for security reasons.
 */
final class LoginLog {
  use Module;

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    // At least the username must be correct for 'wp_authenticate_user' to run,
    // so it isn't good enough for our log, which also should log brute force
    // attacks. Instead use 'login_redirect' which is fired right after
    // wp_signon has finished. Unfortunately it also fires on every single
    // wp-login.php load, so we shall not process it unless we really detect a
    // login in progress.
    \add_filter('login_redirect', array( __CLASS__, 'wp_login_redirect_log' ), 10, 3);
  }

  /**
   * Called on all login attempts.
   * @param string             $redirect_to           The redirect destination URL.
   * @param string             $requested_redirect_to The requested redirect destination URL passed as a parameter.
   * @param \WP_User|\WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
   * @return string The $redirect_to passed.
   */
  public static function wp_login_redirect_log( $redirect_to, $requested_redirect_to, $user ) {
    // Bail out quickly if username and password were not sent on this load
    if ( ! isset($_POST['log']) || $_POST['log'] === '' || ! isset($_POST['pwd']) || $_POST['pwd'] === '' ) {
      return $redirect_to;
    }

    // Check if login was successful record username
    if ( \is_wp_error($user) ) {
      $login_status = 'FAIL';
      $status_code = 401;
      $remote_user = \sanitize_user($_POST['log']);
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
    $time_local = \date('j/M/Y:H:i:s O');
    $request = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
    $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    // Finally write the log to disk

    $log_fp = \fopen('/data/log/wp-login.log', 'a');
    if ( $log_fp === false ) {
      // Couldn't open the file
      self::error_log("Critical security error: wp-login.log can't be written to!");
      return $redirect_to;
    }

    \fwrite($log_fp, "{$remote_addr} - {$remote_user} [{$time_local}] \"{$request}\" {$status_code} 1000 \"{$http_referer}\" \"{$http_user_agent}\" {$login_status} \n");
    \fclose($log_fp);

    return $redirect_to;
  }

}
