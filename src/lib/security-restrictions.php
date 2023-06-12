<?php
namespace Seravo;

/**
 * Class SecurityRestrictions
 *
 * Takes care of custom security settings.
 */
class SecurityRestrictions {

  /**
   * NOTE! This function is executed on every page load
   * @return void
   */
  public static function load() {
    \add_action('activate_seravo-plugin/seravo-plugin.php', array( __CLASS__, 'maybe_enable_xml_rpc_blocking' ));

    if ( \get_option('seravo-disable-xml-rpc-all-methods') === 'on' ) {
      // Block XML-RPC completely
      \add_filter('xmlrpc_enabled', '__return_false');
      \add_filter('xmlrpc_methods', array( __CLASS__, 'remove_xmlrpc_methods' ));

      if ( isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) && \strpos($_SERVER['REQUEST_URI'], 'xmlrpc.php') !== false ) {
        header('Status: 403 Forbidden');
        header('HTTP/1.1 403 Forbidden');
        \wp_die('XML-RPC blocked.');
      }
    } elseif ( \get_option('seravo-disable-xml-rpc') === 'on' ) {
      // Block XML-RPC and X-pingback if IP not whitelisted
      // NOTE! Filter xmlrpc_enabled affects only authenticated XML-RPC requests,
      // and *not* XML-RPC in general.
      // See https://www.scottbrownconsulting.com/2020/03/two-ways-to-fully-disable-wordpress-xml-rpc/
      \add_filter('xmlrpc_enabled', array( __CLASS__, 'maybe_block_xml_rpc' ));
    }

    if ( \get_option('seravo-disable-json-user-enumeration') === 'on' ) {
      /*
         * When this is active any request like
         *   curl -iL https://<siteurl>/wp-json/wp/v2/users/ -H Pragma:no-cache
         * will yield '{"code":"rest_no_route"..'
         */
      \add_filter('rest_endpoints', array( __CLASS__, 'disable_user_endpoints' ), 1000);
    }

    /*
     * When this is active any request like
     *   curl -iL -H Pragma:no-cache https://<siteurl>/?author=7
     * will not redirect to /author/<name>/ and thus not translate used ids
     * to usernames, which some consider as a data leak.
     *
     * In the admin area this must still work otherwise user editing screens
     * stop working.
     */
    if ( \get_option('seravo-disable-get-author-enumeration') === 'on' && ! \is_admin() ) {
      \add_filter(
        'query_vars',
        function ( $public_query_vars ) {
          $key = \array_search('author', $public_query_vars, true);
          if ( false !== $key ) {
            unset($public_query_vars[$key]);
          }
          return $public_query_vars;
        }
      );
    }
  }

  /**
   * Prevent XML-RPC for responding to anything by simply making sure the
   * list of supported methods is empty.
   * See https://developer.wordpress.org/reference/hooks/xmlrpc_methods/
   * @return mixed[]
   */
  public static function remove_xmlrpc_methods() {
     return array();
  }

  /**
   * @param mixed[] $headers Headers with X-Pingback possibly in.
   * @return mixed[] Headers after removing X-Pingback.
   */
  public static function disable_x_pingback( $headers ) {
    unset($headers['X-Pingback']);
    return $headers;
  }

  /**
   * @param mixed[] $endpoints Endpoints about to be enabled.
   * @return mixed[] Endpoints with user endpoints disabled.
   */
  public static function disable_user_endpoints( $endpoints ) {
    // Don't disable API for logged in users, otherwise e.g. the author change
    // dropdown in Gutenberg will emit JavaScript errors and fail to render.
    if ( \is_user_logged_in() ) {
      // Bail out without filtering anything
      return $endpoints;
    }

    // Disable listing users
    if ( isset($endpoints['/wp/v2/users']) ) {
      unset($endpoints['/wp/v2/users']);
    }
    // Disable fetching a single user
    if ( isset($endpoints['/wp/v2/users/(?P<id>[\d]+)']) ) {
      unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
  }

  /**
   * @return void
   */
  public static function maybe_enable_xml_rpc_blocking() {
     // Add option used so no existing options will be overridden
    \add_option('seravo-disable-xml-rpc', 'on');
  }

  /**
   * @return bool|void
   */
  public static function maybe_block_xml_rpc() {
    $whitelist = self::get_jetpack_whitelist();
    $whitelist = \apply_filters('seravo_xml_rpc_whitelist', $whitelist);

    $ip = \ip2long($_SERVER['REMOTE_ADDR']);
    if ( $ip !== false && ! Helpers::ip_in_range($whitelist, $ip) ) {
      // Disable X-Pingback to header
      // since when XML-RPC is disabled pingbacks will not work anyway
      \add_filter('wp_headers', array( __CLASS__, 'disable_x_pingback' ));

      /*
         * When this is active any request like:
         *  curl -d '<?xml version="1.0"?> <methodCall> <methodName>wp.getUsersBlogs</methodName> \
         *    <params> <param> <value>username</value> </param> <param> <value>password</value> </param>\
         *    </params> </methodCall>' https://<siteurl>/xmlrpc.php
         * will yield 'faultCode 405' and 'XML-RPC is not available'
         */
      return false;
    }
  }

  /**
   * @return array<int, mixed[]>|mixed[]|mixed
   */
  public static function get_jetpack_whitelist() {
    $url = 'https://jetpack.com/ips-v4.json';
    $key = 'jetpack_xml_rpc_whitelist_' . \md5($url);

    // Try to fetch data from cache
    $data = \get_transient($key);

    // If cachet data wasn't found, fetch it (otherwise, just use cached data)
    if ( ($data === false) || \count($data) < 1 ) {
      $whitelist = array();
      // Retrieve data from API
      $response = \wp_remote_get(\esc_url_raw($url), array( 'user-agent' => 'Seravo/1.0; https://seravo.com' ));

      if ( \is_wp_error($response) ) {
        // Jetpack.com didn't respond, not much we can do
        return array();
      }

      $data = \json_decode(\wp_remote_retrieve_body($response));

      if ( $data !== array() ) {
        foreach ( $data as $ip ) {
          $whitelist[] = Helpers::cidr_to_range($ip);
        }
        // Cache for 24 hours (DAY_IN_SECONDS)
        \set_transient($key, $whitelist, DAY_IN_SECONDS);
        return $whitelist;
      }
      return array();
    }
    return $data;
  }
}
