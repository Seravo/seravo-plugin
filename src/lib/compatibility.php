<?php

namespace Seravo;

/**
 * Class Compatibility
 *
 * Class for PHP/WordPress functions that have changed over
 * different versions we still must support.
 */
class Compatibility {

  /**
   * Returns the portion of string specified by the offset and length parameters.
   * @todo Remove usage after versions older than PHP 8.0 are no longer supported.
   * @see https://www.php.net/manual/en/function.substr.php
   * @param string   $string The input string.
   * @param int      $offset The extraction offset.
   * @param int|null $length The optional extraction length.
   * @return false|string The extracted part of string; or false on failure.
   */
  public static function substr( $string, $offset, $length = null ) {
    if ( $length === null ) {
      // NULL length wasn't accepted until PHP 8.0, lets just not use it
      $substr = \substr($string, $offset);
    } else {
      $substr = \substr($string, $offset, $length);
    }

    if ( strnatcmp(self::get_php_version(), '8.0.0') >= 0 ) {
      // PHP >8.0 returns empty string instead of false on failure
      if ( empty($substr) ) {
        return false;
      }
    }
    return $substr;
  }

  /**
   *
   * @todo Remove usage after versions older than PHP 8.0 are no longer supported.
   * @see https://github.com/phpstan/phpstan/discussions/5376
   * @see Pre 8.0: https://php-legacy-docs.zend.com/manual/php5/en/function.exec
   * @see After 8.0: https://www.php.net/manual/en/function.exec
   * @param string        $command     The command that will be executed.
   * @param string[]|null $output      The output of the command.
   * @param int|null      $result_code The exit code of the command.
   * @return false|string The last line of command output; or false on failure.
   */
  public static function exec( $command, &$output = null, &$result_code = null ) {
    $exec = \exec($command, $output, $result_code);
    if ( strnatcmp(self::get_php_version(), '8.0.0') < 0 ) {
      // PHP <8.0 never returns false
      if ( empty($exec) && $output === null && $result_code === null ) {
        return false;
      }
    }
    return $exec;
  }

  /**
   * Get PHP version in a safe way with multiple fallbacks. If version can't be detected,
   * return '7.0' as it's the lowest version currently supported (shouldn't happen).
   * @return string PHP version string.
   */
  private static function get_php_version() {
    $version = phpversion();
    if ( $version !== false ) {
      return $version;
    }

    if ( defined('PHP_VERSION') ) {
      return PHP_VERSION;
    }

    if ( defined('PHP_MAJOR_VERSION') ) {
      $version = PHP_MAJOR_VERSION;
      if ( defined('PHP_MINOR_VERSION') ) {
        $version .= '.' . PHP_MINOR_VERSION;
        if ( defined('PHP_RELEASE_VERSION') ) {
          $version .= '.' . PHP_RELEASE_VERSION;
        } else {
          $version .= '.0';
        }
      } else {
        $version .= '.0';
      }
      return $version;
    }

    return '7.0.0';
  }

}
