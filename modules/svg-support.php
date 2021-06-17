<?php
/*
 * Support SVG images
 */

namespace Seravo;

require_once SERAVO_PLUGIN_DIR . 'vendor/autoload.php';

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('SVGSupport') ) {

  class SVGSupport {

    // The sanitizer
    protected $sanitizer;

    public static function load() {
      add_filter('upload_mimes', array( __CLASS__, 'add_to_mime_types' ));
      add_filter('wp_handle_upload_prefilter', array( __CLASS__, 'sanitize_svg' ));
    }

    // Add SVG to allowed mime types
    public static function add_to_mime_types( $mimes ) {
      $mimes['svg'] = 'image/svg+xml';
      $mimes['svgz'] = 'image/svg+xml';
      return $mimes;
    }

    // Check if the file is SVG and sanitize if it is
    public static function sanitize_svg( $file ) {
      $sanitizer = new \enshrined\svgSanitize\Sanitizer();
      $sanitizer->minify(true);
      if ( $file['type'] === 'image/svg+xml' ) {
        $dirty = file_get_contents($file['tmp_name']);
        $clean = $sanitizer->sanitize($dirty);
        if ( $clean === false ) {
          $file['error'] = __(
            "This file couldn't be sanitized so for security reasons it wasn't uploaded",
            'seravo'
          );
        } else {
          // Replace unsanitized file content with sanitized
          file_put_contents($file['tmp_name'], $clean);
        }
      }
      return $file;
    }
  }

  SVGSupport::load();
}
