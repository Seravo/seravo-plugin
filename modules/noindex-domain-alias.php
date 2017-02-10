<?php
/**
 * Plugin name: Don't index domain alias
 * Description: Hides the domain alias from search-engines, (a safety mechanism)
 * Version: 1.0
 */

add_filter( 'robots_txt', 'maybe_hide_domain_alias', 10, 2 );
function maybe_hide_domain_alias( $output, $public ) {
  if( '0' == $public ) {
    // bail early if blog is not public
    return $output;
  }

  // if $_SERVER['HTTP_HOST'] is in form of *.wp-palvelu.fi, don't index
  if ( isset( $_SERVER['HTTP_HOST'] ) ) {

    if ( preg_match( '/^.*\.wp-palvelu\.fi$/', $_SERVER['HTTP_HOST'] ) ||
         preg_match( '/^.*\.seravo\.fi$/', $_SERVER['HTTP_HOST'] ) ||
         preg_match( '/^.*\.seravo\.com$/', $_SERVER['HTTP_HOST'] ) ) {

      $output = "User-agent: *\n";
      $output .= "Disallow: /\n";

    }

  }

  return $output;
}
