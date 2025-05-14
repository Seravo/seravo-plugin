<?php

namespace Seravo\Module;

/**
 * Class ThirdPartyFixes
 *
 * Seravo-specific modifications/fixes to various plugins.
 */
final class ThirdPartyFixes {
  use Module;

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    // Jetpack whitelisting
    \add_filter('jpp_allow_login', array( __CLASS__, 'jetpack_whitelist_seravo' ), 10, 2);

    // Redirection plugin documentation is incorrect about the filter names.
    // Prefix 'red_' is correct one, not the 'redirection_'.
    \add_filter('red_default_options', array( __CLASS__, 'redirection_options_filter' ));
    \add_filter('red_save_options', array( __CLASS__, 'redirection_options_filter' ));

    // SQL query log
    \add_action('shutdown', array( __CLASS__, 'log_queries' ));

    // Maybe cache HTTP requests
    \add_filter('pre_http_request', array( __CLASS__, 'http_maybe_use_cached' ), 10, 3);
    \add_filter('http_response', array( __CLASS__, 'http_maybe_cache' ), 10, 3);

    // Prevent MainWP from deleting README.html by default
    \add_filter('option_mainwp_security', array( __CLASS__, 'mainwp_readme' ), 10, 3);
  }

  /**
   * Prevent MainWP from removing README.html from WP core
   *
   * If this file is absent, WordPress core checksum verification will fail,
   * which is much worse than the data leak caused by the existence of this
   * file. If third party wants to identify WordPress core version, (s)he can
   * always look at eg. static assets to identify exact version.
   *
   * This sets mainwp_security['readme'] always to false, which should make
   * MainWP by default keep README.html
   */
  public static function mainwp_readme( $value, $option ) {
    if ( is_array($value) && in_array('readme', $value)) {
      $value['readme'] = false;
    }
    return $value;
  }

  /**
   * Maybe used cached results for HTTP request
   *
   * Related WordPress core code:
   * <https://github.com/WordPress/WordPress/blob/c463e94a3313ca26c305993a0862e758c0ea3dfe/wp-includes/class-http.php#L239-L257>
   *
   * @param false|\WP_Error|mixed[] $preempt     A preemptive return value of an HTTP request.
   * @param mixed[]                 $parsed_args HTTP request arguments.
   * @param string                  $url         The request URL.
   * @return false|\WP_Error|mixed[] The $preempt passed.
   */
  public static function http_maybe_use_cached( $preempt, $parsed_args, $url ) {
    $cache_key = 'http_cache_' . \md5($url . \serialize($parsed_args));
    $cached = \get_transient($cache_key);
    if ( $cached !== false ) {
      return \unserialize($cached);
    }

    return $preempt;
  }

  /**
   * Maybe cache results of HTTP request
   *
   * This hooks to WordPress core HTTP request handling, and, when developer
   * has chosen so, caches responses from specific hosts. This makes it
   * possible to cache.
   *
   * Related:
   * <https://github.com/WordPress/WordPress/blob/c463e94a3313ca26c305993a0862e758c0ea3dfe/wp-includes/class-http.php#L438-L446>
   *
   * @param mixed[] $response    A preemptive return value of an HTTP request.
   * @param mixed[] $parsed_args HTTP request arguments.
   * @param string  $url         The request URL.
   * @return mixed[] The $response passed.
   */
  public static function http_maybe_cache( $response, $parsed_args, $url ) {
    // Parse hostname from the URL
    $host = \parse_url($url, PHP_URL_HOST);
    if ( $host === null || $host == false ) {
      return $response;
    }

    $host = \str_replace('.', '_', $host);
    $method = \strtolower($parsed_args['method']);

    // Check if we should cache requests to this hostname
    // Filter can return either boolean (true = do cache, false don't cache)
    // or integer (how long we should cache)
    $do_cache = \apply_filters("seravo_cache_http_{$method}_{$host}", false, $parsed_args, $url);

    if ( $do_cache !== false ) {
      if ( $do_cache === true ) {
        $do_cache = 3600;
      }
      $cache_key = 'http_cache_' . \md5($url . \serialize($parsed_args));
      \set_transient($cache_key, \serialize($response), $do_cache);
    }
    return $response;
  }

  /**
   * Log SQL queries
   *
   * Sometimes we encounter plugins and/or themes that use excessive amounts
   * of database resources. This helper function makes it easier to toggle
   * SQL logging for a site temporarily.
   *
   * Based on <https://stackoverflow.com/a/4660903>
   * @see <https://developer.wordpress.org/reference/classes/wpdb/>
   * @return void
   */
  public static function log_queries() {
    if ( ! \defined('SAVEQUERIES') || SAVEQUERIES !== true ) {
      return;
    }

    $logfile = '/data/log/sql.log';
    // If logfile is already over 512MB, just stop logging to prevent
    // filling the disk with probably duplicated queries
    if ( \file_exists($logfile) && \filesize($logfile) > 512 * 1024 * 1024 ) {
      return;
    }

    global $wpdb;
    $handle = \fopen($logfile, 'a');

    if ( $wpdb->queries === null ) {
      // Some other plugin might have blocked 'SAVEQUERIES' from working
      return;
    }

    if ( $wpdb->num_queries > 0 && $handle !== false ) {
      $sid = isset($_SERVER['HTTP_X_SERAVO_REQUEST_ID']) ? $_SERVER['HTTP_X_SERAVO_REQUEST_ID'] : 'none';
      \fwrite($handle, '### ' . \date(\DateTime::ISO8601) . ' sid:' . $sid . ' total:' . $wpdb->num_queries . \chr(10));
      foreach ( $wpdb->queries as $q ) {
        $sql = \trim(\preg_replace('/[\t\n\r\s]+/', ' ', $q[0]));
        $data = \str_replace("\n", '', \print_r($q[4], true));
        \fwrite($handle, "SQL: {$sql}" . \chr(10));
        \fwrite($handle, "Time: $q[1] s" . \chr(10));
        \fwrite($handle, "Calling functions: $q[2]" . \chr(10));
        \fwrite($handle, "Query begin: $q[3]" . \chr(10));
        \fwrite($handle, 'Custom data: ' . $data . \chr(10) . '--' . \chr(10));
      }
      \fwrite($handle, '### EOF' . \chr(10) . \chr(10));
      \fclose($handle);
    }
  }

  /**
   * Set default options for Redirection plugin
   *
   * This disables redirect cache, at seems to cause redirect loops
   * quite often, and we don't like that. Our infrastructure does
   * caching anyways.
   *
   * @param mixed[] $options User-provided (or default) options
   * @return mixed[] Updated options with our customizations
   * @see <https://redirection.me/developer/wordpress-hooks/>
   */
  public static function redirection_options_filter( $options ) {
    $updated = array(
      'redirect_cache' => -1,
    );
    return \array_merge($options, $updated);
  }

  /**
   * Retrieve whitelisted IPs from our API.
   *
   * JSON contains just a list of IP addresses (both IPv4, IPv6),
   *     ['123.45.67.89', '2a00:....', ...]
   *
   * @return string[] List of Seravo monitoring IPs.
   */
  public static function retrieve_whitelist() {
    $url = 'https://api.seravo.com/v0/infrastructure/monitoring-hosts.json';
    $key = 'seravo_jetpack_whitelist_' . \md5($url);

    // Try to fetch data from cache
    $data = \get_transient($key);

    // If cachet data wasn't found, fetch it (otherwise, just use cached data)
    if ( $data === false || ! \is_array($data) || \count($data) < 1 ) {
      // Retrieve data from API
      $response = \wp_remote_get(\esc_url_raw($url));
      if ( \is_wp_error($response) ) {
        // Not much we can do but lets not cache this
        return array();
      }

      $data = \json_decode(\wp_remote_retrieve_body($response));
      if ( $data === null || ! \is_array($data) ) {
        // JSON decode failed
        return array();
      }

      // Cache for 24 hours (DAY_IN_SECONDS)
      \set_transient($key, $data, DAY_IN_SECONDS);
    }

    return $data;
  }

  /**
   * Whitelist Seravo infrastructure in Jetpack.
   *
   * We've noticed that sometimes Jetpack thinks that our
   * requests are bruteforcing, while we're just doing site
   * status checks.
   *
   * So, let's fetch list of IP addresses that should be whitelisted
   * and always allowed.
   *
   * @see <https://developer.jetpack.com/hooks/jpp_allow_login/>
   * @see <https://developer.jetpack.com/tag/jpp_allow_login/>
   * @param bool   $allow_login Whether to allow login for $ip.
   * @param string $ip          The IP to check.
   * @return bool Whether login is allowed or not.
   */
  public static function jetpack_whitelist_seravo( $allow_login, $ip ) {
    return \in_array($ip, self::retrieve_whitelist(), true) ? true : $allow_login;
  }

}
