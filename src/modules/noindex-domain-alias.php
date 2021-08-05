<?php

namespace Seravo\Module;

/**
 * Class NoIndex
 *
 * Hides the domain alias from search-engines, (a safety mechanism).
 */
final class Noindex {
  use Module;

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  public function init() {
    \add_filter('robots_txt', array( __CLASS__, 'maybe_hide_domain_alias' ), 10, 2);
  }

  /**
   * @param string $output The contents of robots.txt to be outputted.
   * @param bool   $public Whether the site is considered "public".
   * @return string The robots.txt content.
   */
  public static function maybe_hide_domain_alias( $output, $public ) {
    if ( ! $public || ! isset($_SERVER['HTTP_HOST']) ) {
      // Bail early if blog is not public or unknown HTTP_HOST.
      return $output;
    }

    $seravo_domains = array(
      '/^.*\.wp-palvelu\.fi$/',
      '/^.*\.seravo\.fi$/',
      '/^.*\.seravo\.com$/',
      '/^.*\.wp\..*$/',
      '/^.*\.dev\..*$/',
    );

    foreach ( $seravo_domains as $seravo_domain ) {
      // If $_SERVER['HTTP_HOST'] is in Seravo temporary URL form, don't index.
      // TODO: Remove wp-palvelu.fi and seravo.fi once no sites use them.
      if ( \preg_match($seravo_domain, $_SERVER['HTTP_HOST']) === 1 ) {
        $output = "User-agent: *\n";
        $output .= "Disallow: /\n";
        break;
      }
    }

    return $output;
  }

}
