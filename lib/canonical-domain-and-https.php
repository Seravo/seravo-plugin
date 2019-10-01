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

if ( ! class_exists('Canonical_Domain_And_Https') ) {

  class Canonical_Domain_And_Https {

    // NOTE! This function is executed on every page load.
    // Be careful to keep the overhead here minimal.
    public static function load() {

      // Check if siteurl and home both include https addresses. If so, enforce
      // their use by doing a redirect if the request was not already https.
      $siteurl = get_option('siteurl');
      $home = get_option('home');

      if ( strpos($siteurl, 'https') !== false && strpos($home, 'https') !== false ) {
        // Site uses https
        if ( headers_sent() === false && is_ssl() === false && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) ) {
          // Request did not use https, force redirect
          $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
          // error_log("Redirect to $url");

          // wp_redirect() is not available at this stage, cannot be used
          header("Location: $url", true, 301);
          exit; // Nothing more to see here!
        }
      }

    }

  }

  Canonical_Domain_And_Https::load();
}
