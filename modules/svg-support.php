<?php
/*
 * Support SVG images
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('SVGSupport') ) {

  class SVGSupport {
    public static function load() {
      add_filter('upload_mimes', array( __CLASS__, 'add_to_mime_types' ));
    }

    // Add SVG to allowed mime types
    public static function add_to_mime_types( $mimes ) {
      $mimes['svg'] = 'image/svg+xml';
      $mimes['svgz'] = 'image/svg+xml';
      return $mimes;
    }
  }

  SVGSupport::load();
}
