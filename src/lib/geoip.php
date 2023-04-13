<?php

namespace Seravo;

/**
 * Class GeoIP
 *
 * Class for country code and location based login restriction related functions.
 */
class GeoIP {

  /**
   * Convert a two-letter country code in to the full country name.
   *
   * @param string $country_code The two-letter country code.
   * @return false|string        The full country name or false on failure (invalid code).
   */
  public static function country_code_to_name( $country_code ) {
    $country_code = \strtoupper($country_code);

    // Country code is always two letters
    if ( \strlen($country_code) !== 2 ) {
      return false;
    }

    // Get the full English name
    $country_name = \Locale::getDisplayRegion('-' . \strtoupper($country_code), 'en');

    // If the name is false, empty or just the country code, the code was invalid
    if ( $country_name === '' || $country_name === $country_code ) {
      return false;
    }

    return $country_name;
  }

  /**
   * Check whether geologin is enabled. Geologin is enabled if there's at
   * least one country on the list of countries from which login is allowed.
   *
   * @return bool Whether geologin is enabled.
   */
  public static function is_geologin_enabled() {
    // There's at least one allowed country on the blog's list
    if ( get_option('seravo-allow-login-countries', array()) !== array() ) {
      return true;
    }

    // There's at least one allowed country on the network-wide list
    if ( is_multisite() && get_site_option('seravo-allow-login-countries', array()) !== array() ) {
      return true;
    }

    return false;
  }

  /**
   * Check whether login is allowed from a country.
   *
   * @param string $country_code The two-letter code of the country.
   * @return bool                Whether login is allowed.
   */
  public static function is_login_allowed( $country_code ) {
    $country_code = \strtoupper($country_code);

    // Check the current blog's list
    if ( in_array($country_code, get_option('seravo-allow-login-countries', array()), true) ) {
      return true;
    }

    // Check the network-wide list
    if ( is_multisite() ) {
      if ( in_array($country_code, get_site_option('seravo-allow-login-countries', array()), true) ) {
        return true;
      }
    }

    return false;
  }

  /**
   * Add country to the list of countries from which login is allowed.
   *
   * @param string $country_code The two-letter country code.
   * @param bool   $network      Whether to allow network-wide.
   * @return bool                True on success and false if the code was invalid
   *                             or the country was already on the list.
   */
  public static function allow_geologin( $country_code, $network ) {
    $country_code = \strtoupper($country_code);

    // Get the old list of allowed countries
    if ( $network && is_multisite() ) {
      $allowed_countries = get_site_option('seravo-allow-login-countries', array());
    } else {
      $allowed_countries = get_option('seravo-allow-login-countries', array());
    }

    // Check if the country has already been allowed
    if ( in_array($country_code, $allowed_countries, true) ) {
      return false;
    }

    // Append the code to the list of allowed countries
    $allowed_countries[] = $country_code;
    sort($allowed_countries);

    if ( $network && is_multisite() ) {
      update_site_option('seravo-allow-login-countries', $allowed_countries);
    } else {
      update_option('seravo-allow-login-countries', $allowed_countries);
    }

    return true;
  }

  /**
   * Remove a country from the list of countries from which login is allowed.
   *
   * @param string $country_code The two-letter country code.
   * @param bool   $network      Whether to remove from the network-wide list.
   * @return bool                Whether the country was removed or not.
   */
  public static function disallow_geologin( $country_code, $network ) {
    $country_code = \strtoupper($country_code);

    // Get the old list of allowed countries
    if ( $network && is_multisite() ) {
      $allowed_countries = get_site_option('seravo-allow-login-countries', array());
    } else {
      $allowed_countries = get_option('seravo-allow-login-countries', array());
    }

    $index = array_search($country_code, $allowed_countries, true);
    if ( $index === false ) {
      // The country wasn't allowed, can't disallow
      return false;
    }

    // Disallow the country
    unset($allowed_countries[$index]);

    if ( $network && is_multisite() ) {
      update_site_option('seravo-allow-login-countries', $allowed_countries);
    } else {
      update_option('seravo-allow-login-countries', $allowed_countries);
    }

    return true;
  }

}
