<?php
/**
 * Plugin name: ThirdpartyFixes
 * Description: Seravo-specific modifications/fixes to various plugins
 **/
namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists( 'ThirdpartyFixes' ) ) {
  class ThirdpartyFixes {
    public static $instance;

    public static function init() {
      if ( is_null( self::$instance ) ) {
        self::$instance = new ThirdpartyFixes();
      }
      return self::$instance;
    }

    public function __construct() {
      // Jetpack whitelisting
      add_filter( 'jpp_allow_login', array( $this, 'jetpack_whitelist_seravo' ), 10, 1 );
    }

    /**
     * Retrieve whitelisted IPs from our API.
     *
     * JSON contains just a list of IP addresses (both IPv4, IPv6),
     *     ['123.45.67.89', '2a00:....', ...]
     *
     * @since 1.9.4
     * @version 1.0
     **/
    public function retrieve_whitelist() {
      $url = 'https://api.seravo.com/v0/infrastructure/monitoring-hosts.json';
      $key = 'seravo_jetpack_whitelist_' . md5( $url );

      // Try to fetch data from cache
      $data = get_transient( $key );

      // If cachet data wasn't found, fetch it (otherwise, just use cached data)
      if ( ( $data === false ) || count( $data ) < 1 ) {
        // Retrieve data from API
        $response = wp_remote_get( esc_url_raw( $url ) );
        $data = json_decode( wp_remote_retrieve_body( $response ) );

        // Cache for 24 hours (DAY_IN_SECONDS)
        set_transient( $key, $data, DAY_IN_SECONDS );
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
     **/
    public function jetpack_whitelist_seravo( $ip ) {
      if ( ! function_exists( 'jetpack_protect_get_ip' ) ) {
        return false;
      }
      $ip = jetpack_protect_get_ip();
      $whitelist = $this->retrieve_whitelist();
      return in_array( $ip, $whitelist );
    }
  }
  ThirdpartyFixes::init();
}
