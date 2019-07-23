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

    // Check if this is whitelabel site
    public static function is_whitelabel() {
      $whitelabel = constant('USE_SERAVO_WHITELABEL');

      error_log($whitelabel);

      if ( $whitelabel ) {
        error_log('Whitelabel enabled!');
        return true;
      } else {
        error_log('Whitelabel disabled!');
        return false;
      }
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
      return round($size, $precision) . [ 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ][ $i ];
    }

  }

}
