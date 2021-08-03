<?php
namespace Seravo;

/**
 * Class Helpers
 *
 * Helpers for this plugin and other modules.
 */
class Helpers {

  /**
   * Check if this if the site is running in Vagrant
   *
   * @return bool
   */
  public static function is_development() {
     return \getenv('WP_ENV') === 'development';
  }

  /**
   * Check if this is a live production site
   *
   * @return bool
   */
  public static function is_production() {
     return \getenv('WP_ENV') === 'production';
  }

  /**
   * Check if this is staging shadow
   * There shouldn't be difference between this and production,
   * but might be useful in the future.
   *
   * @return bool
   */
  public static function is_staging() {
     return \getenv('WP_ENV') === 'staging';
  }

  /**
   * @return string Seravo Plugin version string. If SERAVO_PLUGIN_DEBUG is enabled, random number is appended.
   */
  public static function seravo_plugin_version() {
     $version = \get_file_data(SERAVO_PLUGIN_DIR . 'seravo-plugin.php', array( 'Version' ), 'plugin')[0];

     // Development cache bursting
     if ( \defined('SERAVO_PLUGIN_DEBUG') && SERAVO_PLUGIN_DEBUG ) {
       $version .= '.' . \random_int(10000, 99999);
     }

     return $version;
  }

  /**
   * Get PHP version in a safe way with multiple fallbacks. If version can't be detected,
   * return '7.0' as it's the lowest version currently supported (shouldn't happen).
   * @return string PHP version string.
   */
  public static function get_php_version() {
    if ( \defined('PHP_MAJOR_VERSION') ) {
      $version = PHP_MAJOR_VERSION;
      if ( \defined('PHP_MINOR_VERSION') ) {
        $version .= '.' . PHP_MINOR_VERSION;
        if ( \defined('PHP_RELEASE_VERSION') ) {
          $version .= '.' . PHP_RELEASE_VERSION;
        } else {
          $version .= '.0';
        }
      } else {
        $version .= '.0';
      }
      return $version;
    }

    $version = \phpversion();
    if ( $version !== false ) {
      return $version;
    }

    if ( \defined('PHP_VERSION') ) {
      return PHP_VERSION;
    }

    return '7.0.0';
  }

  /**
   * @param int $size      The size in bytes.
   * @param int $precision The amount of decimal places.
   * @return string The size in human readable format.
   */
  public static function human_file_size( $size, $precision = 2 ) {
    $i = 0;
    for ( $i = 0; ($size / 1024) > 0.9; ) {
      ++$i;
      $size /= 1024;
    }
    return \round($size, $precision) . array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' )[$i];
  }

  /**
   * Get IP range limits from CIDR range format. Index 0
   * is lower limit and index 1 upper limit.
   *
   * Example result:
   *  [0] => 3221250250,
   *  [1] => 3221254346,
   *
   * @param string $cidr IPv4 range in CIDR format (eg. xxx.xxx.xxx.xxx/20)
   * @return int[] Upper and lower limits in ip2long format.
   * @version 1.0
   * @see https://gist.github.com/tott/7684443
   */
  public static function cidr_to_range( $cidr ) {
    $cidr = \explode('/', $cidr);
    $range = array();
    $range[0] = (\ip2long($cidr[0])) & ((-1 << (32 - (int) $cidr[1])));
    $range[1] = (int) ($range[0] + 2 ** (32 - (int) $cidr[1]) - 1);
    return $range;
  }

  /**
   * @param int[][] $range Upper and lower limits in ip2long format.
   * @param int     $ip    The IP to check in ip2long format.
   * @return bool Whether the IP is in range or not.
   */
  public static function ip_in_range( $range, $ip ) {
    foreach ( $range as $limits ) {
      if ( $ip >= $limits[0] && $ip <= $limits[1] ) {
        return true;
      }
    }
    return false;
  }

  /**
   * @param string $file The filepath to sanitize.
   * @return string Sanitized path.
   */
  public static function sanitize_full_path( $file ) {
    $path = \explode('/', $file);
    foreach ( $path as $index => $part ) {
      $path[$index] = \sanitize_file_name($part);
    }
    return \implode('/', $path);
  }

  /**
   * @return string Escaped adminer_url
   */
  public static function adminer_link() {
    if ( ! self::is_production() ) {
      return \esc_url(\str_replace('//', '//adminer.', \get_site_url()));
    }
    return \esc_url(\get_site_url(null, '.seravo/adminer'));
  }

  }
