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

    /**
     * @return void;
     */
    public static function load() {
      add_filter('upload_mimes', array( __CLASS__, 'add_to_mime_types' ));
      add_filter('wp_handle_upload_prefilter', array( __CLASS__, 'sanitize_svg' ));
    }

    /**
     * Add SVG to allowed mime types
     * @param array<string,string> $mimes Allowed file-extensions and their mime types.
     * @return array<string,string> Allowed mime types with SVG added.
     */
    public static function add_to_mime_types( $mimes ) {
      $mimes['svg'] = 'image/svg+xml';
      $mimes['svgz'] = 'image/svg+xml';
      return $mimes;
    }

    /**
     * Check if the file is SVG and sanitize if it is
     * @param mixed[] $file Details of the image uploaded.
     * @return mixed[] Details of the image uploaded after sanization.
     */
    public static function sanitize_svg( $file ) {
      $sanitizer = new \enshrined\svgSanitize\Sanitizer();
      $sanitizer->minify(true);
      if ( $file['type'] === 'image/svg+xml' ) {
        $dirty = file_get_contents($file['tmp_name']);
        if ( $dirty === false ) {
          // Couldn't read the file
          $file['error'] = __(
            "This file couldn't be sanitized so for security reasons it wasn't uploaded",
            'seravo'
          );
          return $file;
        }

        $clean = $sanitizer->sanitize($dirty);
        if ( empty($clean) ) {
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
