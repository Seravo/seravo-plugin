<?php

namespace Seravo\Module;

use \Seravo\GeoIP;

/**
 * Class GeoLogin
 *
 * Allows logging in to dashboard only from specified countries.
 */
final class GeoLogin {
  use Module;

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    // Verify the login country with priority 99, so it can
    // hide other errors (like invalid pass, user not found, etc.)
    add_filter('authenticate', array( __CLASS__, 'verify_login_country' ), 99);
    add_filter('wp_authenticate_user', array( __CLASS__, 'verify_login_country' ), 99);
  }

  /**
   * Check whether the user trying to log in from a country that's
   * in the list of countries from which login is allowed.
   *
   * @param \WP_User|\WP_Error|null $user User object or WP error from previous callbacks.
   * @return \WP_User|\WP_Error|null User object on success and WP error on failure.
   */
  public static function verify_login_country( $user ) {
    // Check whether the login is geo restricted
    if ( ! GeoIP::is_geologin_enabled() ) {
      return $user;
    }

    // Get the country found by GeoIP
    if ( isset($_SERVER['HTTP_X_SERAVO_GEO_COUNTRY_CODE']) ) {
      if ( GeoIP::is_login_allowed($_SERVER['HTTP_X_SERAVO_GEO_COUNTRY_CODE']) ) {
        // Login is restricted to specific countries only but the user is in one of them.
        // Login OK!
        return $user;
      }
    }

    // The user is not in a country from which login is allowed
    $message = __('<strong>Error</strong>: Your connection is from a country from which login on this site is not allowed.', 'seravo');
    return new \WP_Error('seravo_geologin_failure', $message);
  }

}
