<?php
/*
 * Description: Custom redirects to enforce primary domain or https
 *
 * This module should be called as early as possible so that the redirect
 * happens as quickly as possible and no other parts of the WordPress are
 * initialized in vain.
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Security_Restrictions') ) {

  class Security_Restrictions {

    // NOTE! This function is executed on every page load

    public static function load() {

      add_action('activate_seravo-plugin/seravo-plugin.php', array( __CLASS__, 'maybe_enable_xml_rpc_blocking' ));

      if ( get_option('seravo-disable-xml-rpc') ) {

        // Block XML-RPC and X-pingback if IP not whitelisted
        add_filter('xmlrpc_enabled', array( __CLASS__, 'maybe_block_xml_rpc' ));

      }

      if ( get_option('seravo-disable-json-user-enumeration') ) {
        /*
         * When this is active any request like
         *   curl -iL https://<siteurl>/wp-json/wp/v2/users/ -H Pragma:no-cache
         * will yield '{"code":"rest_no_route"..'
         */
        add_filter('rest_endpoints', array( __CLASS__, 'disable_user_endpoints' ), 1000);
      }

      if ( get_option('seravo-disable-get-author-enumeration') ) {
        /*
         * When this is active any request like
         *   curl -iL -H Pragma:no-cache https://<siteurl>/?author=7
         * will not redirect to /author/<name>/ and thus not translate used ids
         * to usernames, which some consider as a data leak.
         *
         * In the admin area this must still work otherwise user editing screens
         * stop working.
         */
        if ( ! is_admin() ) {
          add_filter(
            'query_vars',
            function ( $public_query_vars ) {
              $key = array_search('author', $public_query_vars, true);
              if ( false !== $key ) {
                unset($public_query_vars[ $key ]);
              }
              return $public_query_vars;
            }
          );
        }
      }

    }

    public static function disable_x_pingback( $headers ) {
      unset($headers['X-Pingback']);
      return $headers;
    }

    public static function disable_user_endpoints( $endpoints ) {
      // Don't disable API for logged in users, otherwise e.g. the author change
      // dropdown in Gutenberg will emit JavaScript errors and fail to render.
      if ( is_user_logged_in() ) {
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

    public static function maybe_enable_xml_rpc_blocking() {

      // Add option used so no existing options will be overridden
      add_option('seravo-disable-xml-rpc', 'on');

    }

    public function maybe_block_xml_rpc() {
      $whitelist = self::get_jetpack_whitelist();
      $whitelist = apply_filters('seravo_xml_rpc_whitelist', $whitelist);

      $ip = ip2long($_SERVER['REMOTE_ADDR']);
      if ( ! Helpers::ip_in_range($whitelist, $ip) ) {
        // Disable X-Pingback to header
        // since when XML-RPC is disabled pingbacks will not work anyway
        add_filter('wp_headers', array( __CLASS__, 'disable_x_pingback' ));

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

    public function get_jetpack_whitelist() {
      $url = 'https://jetpack.com/ips-v4.json';
      $key = 'jetpack_xml_rpc_whitelist_' . md5($url);

      // Try to fetch data from cache
      $data = get_transient($key);

      // If cachet data wasn't found, fetch it (otherwise, just use cached data)
      if ( ($data === false) || count($data) < 1 ) {
        $whitelist = array();
        // Retrieve data from API
        $response = wp_remote_get(esc_url_raw($url), array( 'user-agent' => 'Seravo/1.0; https://seravo.com' ));
        $data = json_decode(wp_remote_retrieve_body($response));

        if ( ! empty($data) ) {
          foreach ( $data as $ip ) {
            array_push($whitelist, Helpers::cidr_to_range($ip));
          }
          // Cache for 24 hours (DAY_IN_SECONDS)
          set_transient($key, $whitelist, DAY_IN_SECONDS);
          return $whitelist;
        } else {
          return array();
        }
      }
      return $data;
    }

  }

  Security_Restrictions::load();
}
