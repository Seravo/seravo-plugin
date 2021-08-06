<?php
namespace Seravo;

/**
 * Class ThirdPartyFixes
 *
 * Seravo-specific modifications/fixes to various plugins
 */
class ThirdPartyFixes {

  /**
   * @var \Seravo\ThirdPartyFixes|null
   */
  public static $instance;

  /**
   * @return \Seravo\ThirdPartyFixes|null
   */
  public static function init() {
    if ( is_null(self::$instance) ) {
      self::$instance = new ThirdPartyFixes();
    }
    return self::$instance;
  }

  public function __construct() {
    // Jetpack whitelisting
    add_filter(
      'jpp_allow_login',
      function ( $ip ) {
        return $this->jetpack_whitelist_seravo();
      },
      10,
      1
    );

    // Set options for Redirection plugin
    // defined twice because Redirection code and documentation has conflicts,
    // ie. just to be sure...
    add_filter(
      'red_default_options',
      function ( array $options ) {
        return $this->redirection_options_filter($options);
      },
      10,
      1
    );
    add_filter(
      'red_save_options',
      function ( array $options ) {
        return $this->redirection_options_filter($options);
      },
      10,
      1
    );
    add_filter(
      'redirection_default_options',
      function ( array $options ) {
        return $this->redirection_options_filter($options);
      },
      10,
      1
    );
    add_filter(
      'redirection_save_options',
      function ( array $options ) {
        return $this->redirection_options_filter($options);
      },
      10,
      1
    );

    // Maybe log SQL queries
    add_action(
      'shutdown',
      function () {
        $this->log_queries();
      }
    );

    // Maybe cache HTTP requests
    add_filter(
      'pre_http_request',
      function ( $preempt, $parsed_args, $url ) {
        return $this->http_maybe_use_cached($preempt, $parsed_args, $url);
      },
      10,
      3
    );
    add_filter(
      'http_response',
      function ( $response, $parsed_args, $url ) {
        return $this->http_maybe_cache($response, $parsed_args, $url);
      },
      10,
      3
    );
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
   **/
  public function http_maybe_use_cached( $preempt, $parsed_args, $url ) {
    $cache_key = 'http_cache_' . md5($url . serialize($parsed_args));
    $cached = get_transient($cache_key);
    if ( $cached !== false ) {
      return unserialize($cached);
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
  public function http_maybe_cache( $response, $parsed_args, $url ) {
    // Parse hostname from the URL
    $host = parse_url($url, PHP_URL_HOST);
    if ( $host === null || $host == false ) {
      return $response;
    }

    $host = str_replace('.', '_', $host);
    $method = strtolower($parsed_args['method']);

    // Check if we should cache requests to this hostname
    // Filter can return either boolean (true = do cache, false don't cache)
    // or integer (how long we should cache)
    $do_cache = apply_filters("seravo_cache_http_{$method}_{$host}", false, $parsed_args, $url);

    if ( $do_cache !== false ) {
      if ( $do_cache === true ) {
        $do_cache = 3600;
      }
      $cache_key = 'http_cache_' . md5($url . serialize($parsed_args));
      set_transient($cache_key, serialize($response), $do_cache);
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
   *
   *
   * Based on <https://stackoverflow.com/a/4660903>
   * @see <https://developer.wordpress.org/reference/classes/wpdb/>
   * @return void
   */
  public function log_queries() {
    if ( ! defined('SAVEQUERIES') || SAVEQUERIES !== true ) {
      return;
    }
    $logfile = '/data/log/sql.log';
    // If logfile is already over 512MB, just stop logging to prevent
    // filling the disk with probably duplicated queries
    if ( file_exists($logfile) && filesize($logfile) > 512 * 1024 * 1024 ) {
      return;
    }

    global $wpdb;
    $handle = fopen($logfile, 'a');

    if ( $wpdb->num_queries > 0 && $handle !== false ) {
      $sid = isset($_SERVER['HTTP_X_SERAVO_REQUEST_ID']) ? $_SERVER['HTTP_X_SERAVO_REQUEST_ID'] : 'none';
      fwrite($handle, '### ' . date(\DateTime::ISO8601) . ' sid:' . $sid . ' total:' . $wpdb->num_queries . chr(10));
    foreach ( $wpdb->queries as $q ) {
        $sql = trim(preg_replace('/[\t\n\r\s]+/', ' ', $q[0]));
        $data = str_replace("\n", '', print_r($q[4], true));
        fwrite($handle, "SQL: {$sql}" . chr(10));
        fwrite($handle, "Time: $q[1] s" . chr(10));
        fwrite($handle, "Calling functions: $q[2]" . chr(10));
        fwrite($handle, "Query begin: $q[3]" . chr(10));
        fwrite($handle, 'Custom data: ' . $data . chr(10) . '--' . chr(10));
      }
      fwrite($handle, '### EOF' . chr(10) . chr(10));
      fclose($handle);
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
   * @since 1.9.15
   * @see <https://redirection.me/developer/wordpress-hooks/>
   **/
  public function redirection_options_filter( $options ) {
    $updated = array(
      'redirect_cache' => -1,
    );
    return array_merge($options, $updated);
  }

  /**
   * Retrieve whitelisted IPs from our API.
   *
   * JSON contains just a list of IP addresses (both IPv4, IPv6),
   *     ['123.45.67.89', '2a00:....', ...]
   *
   * @since 1.9.4
   * @version 1.0
   * @return string[] List of Seravo monitoring IPs.
   **/
  public function retrieve_whitelist() {
    $url = 'https://api.seravo.com/v0/infrastructure/monitoring-hosts.json';
    $key = 'seravo_jetpack_whitelist_' . md5($url);

    // Try to fetch data from cache
    $data = get_transient($key);

    // If cachet data wasn't found, fetch it (otherwise, just use cached data)
    if ( $data === false || ! is_array($data) || count($data) < 1 ) {
      // Retrieve data from API
      $response = wp_remote_get(esc_url_raw($url));
      if ( is_wp_error($response) ) {
        // Not much we can do but lets not cache this
        return array();
      }

      $data = json_decode(wp_remote_retrieve_body($response));
      if ( $data === null || ! is_array($data) ) {
        // JSON decode failed
        return array();
      }

      // Cache for 24 hours (DAY_IN_SECONDS)
      set_transient($key, $data, DAY_IN_SECONDS);
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
   * @since 1.9.4
   * @version 1.1
   * @see <https://developer.jetpack.com/hooks/jpp_allow_login/>
   * @see <https://developer.jetpack.com/tag/jpp_allow_login/>
   * @return bool
   **/
  public function jetpack_whitelist_seravo() {
    if ( ! function_exists('jetpack_protect_get_ip') ) {
      return false;
    }
    $ip = jetpack_protect_get_ip();
    $whitelist = $this->retrieve_whitelist();
    return in_array($ip, $whitelist, true);
  }
}
