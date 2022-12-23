<?php

namespace Seravo;

use Seravo\API\SWD;

/**
 * Class Shadow
 *
 * Helpers for shadow management.
 */
class Shadow {

  /**
   * Load list of shadow instances from Seravo API (if available)
   * @return bool|mixed
   */
  public static function load_shadow_list() {
    // If not in production, the Seravo API is not accessible and it is not
    // even possible know what shadows exists, so just return an empty list.
    if ( ! Helpers::is_production() ) {
      return false;
    }

    $shadow_list = \get_transient('seravo_shadow_list');

    // Check if shadows were cached
    if ( $shadow_list === false ) {
      $shadow_list = SWD::get_site_shadows();
      
      if ( \is_wp_error($shadow_list) ) {
        return false;
      }

      // Cache the shadow list
      \set_transient('seravo_shadow_list', $shadow_list, 10 * MINUTE_IN_SECONDS);
    }

    return $shadow_list;
  }


  /**
   * Get production domain from shadow. This should be called from shadow only.
   * @return string The production domain or empty if undetermined.
   */
  public static function get_production_domain() {
    if ( isset($_COOKIE['seravo_shadow']) && $_COOKIE['seravo_shadow'] !== '' ) {
      // Seravo_shadow cookie indicates cookie based access, no separate domain
      return '';
    }
    if ( isset($_GET['seravo_production']) && $_GET['seravo_production'] !== '' && $_GET['seravo_production'] !== 'clear' ) {
      // With seravo_production param, shadow uses domain based access
      // Tested before cookie as it may contain newer data
      return $_GET['seravo_production'];
    }
    if ( isset($_COOKIE['seravo_production']) && $_COOKIE['seravo_production'] !== '' ) {
      // With seravo_production cookie, shadow uses domain based access
      return $_COOKIE['seravo_production'];
    }
    if ( $_SERVER['SERVER_NAME'] !== \getenv('DEFAULT_DOMAIN') && \substr_count($_SERVER['SERVER_NAME'], '.') >= 2 ) {
      // TODO: This is bad solution, fix this
      // If domain consists of 3 or more parts, remove the downmost
      // Notice that this DOES NOT necessarily work for multilevel TLD (eg. co.uk)
      // Slash at end means that only hostname should be used (no path/query etc)
      // It should be used when redirecting might be needed
      return \explode('.', $_SERVER['SERVER_NAME'], 2)[1] . '/';
    }
    // If none of the others work, trust in redirecting
    return \getenv('DEFAULT_DOMAIN') . '/';
  }

}
