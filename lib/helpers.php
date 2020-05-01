<?php
/*
 * Description: Helpers for this plugin and other modules
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Helpers') ) {

  class Helpers {

    // Check if this if the site is running in Vagrant
    public static function is_development() {
      return (getenv('WP_ENV') && getenv('WP_ENV') === 'development');
    }

    // Check if this is a live production site
    public static function is_production() {
      return (getenv('WP_ENV') && getenv('WP_ENV') === 'production');
    }

    // Check if this is staging shadow
    // There shouldn't be difference between this and production,
    // but might be useful in the future.
    public static function is_staging() {
      return (getenv('WP_ENV') && getenv('WP_ENV') === 'staging');
    }

    public static function seravo_plugin_version() {
      return get_file_data(plugin_dir_path(dirname(__FILE__)) . 'seravo-plugin.php', array( 'Version' ), 'plugin')[0];
    }

    public static function human_file_size( $size, $precision = 2 ) {
      $size = (int) $size; // 'wp db size' returns value with non-numeric characters
      for ( $i = 0; ($size / 1024) > 0.9; ) {
        $i++;
        $size /= 1024;
      }
      return round($size, $precision) . array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' )[ $i ];
    }

    /**
     * Get IP range limits from CIDR range format. Index 0
     * is lower limit and index 1 upper limit.
     *
     * Example result:
     *  [0] => 3221250250,
     *  [1] => 3221254346,
     *
     * @param string IPv4 range in CIDR format (eg. xxx.xxx.xxx.xxx/20)
     * @return array Upper and lower limits in ip2long format.
     * @version 1.0
     * @see https://gist.github.com/tott/7684443
     **/
    public static function cidr_to_range( $cidr ) {
      $cidr = explode('/', $cidr);
      $range = array();
      $range[0] = (ip2long($cidr[0])) & ((-1 << (32 - (int) $cidr[1])));
      $range[1] = $range[0] + pow(2, (32 - (int) $cidr[1])) - 1;
      return $range;
    }

    public static function ip_in_range( $range, $ip ) {
      foreach ( $range as $limits ) {
        if ( $ip >= $limits[0] && $ip <= $limits[1] ) {
          return true;
        }
      }
      return false;
    }

  }

}
