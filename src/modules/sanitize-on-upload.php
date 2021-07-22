<?php
/*
 * Sanitize special characters out from file names on upload.
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('SanitizeOnUpload') ) {
  class SanitizeOnUpload {

    /**
     * @return void
     */
    public static function load() {
      add_filter('wp_handle_upload_prefilter', array( __CLASS__, 'upload_prefilter' ), 10, 1);
    }

    /**
     * @param mixed[] $file Filename to be sanitized.
     * @return mixed[] Sanitized filename.
     */
    public static function upload_prefilter( $file ) {
      // Convert all characters in the filename to HTML entities first and then
      // run a replacement to replace them with standard characters.
      $file['name'] = preg_replace('/&([a-z])[a-z]+;/i', '$1', htmlentities($file['name']));
      return $file;
    }

  }

  if ( get_option('seravo-enable-sanitize-uploads') === 'on' ) {
    SanitizeOnUpload::load();
  }
}
