<?php

namespace Seravo\Module;

use \Seravo\Helpers;

/**
 * Class ImageUpload
 *
 * Adds SVG support, image optimization
 * and image sanization on upload.
 */
final class ImageUpload {
  use Module;

  /**
   * Check whether the module should be loaded or not.
   * @return bool Whether to load.
   */
  protected function should_load() {
    return \is_user_logged_in();
  }

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    // Optimize images on upload
    if ( \get_option('seravo-enable-optimize-images') === 'on' ) {
      /*
      * Run optimizer for all thumbnail sizes. Don't use 'handle_upload' as it
      * fires too early and applies only to the original image. Instead hooking
      * into the thumbnail generation ensures that the resulting images that
      * are used in blog posts are optimized while the original image is left
      * untouched at upload time, and optimize it only after backups have run
      * and the batch optimization run executes.
      */
      \add_filter('image_make_intermediate_size', array( __CLASS__, 'optimize_images_on_upload' ), 10, 1);
      /*
      * Modify the default jpeg_quality
      * https://developer.wordpress.org/reference/hooks/jpeg_quality/
      */
      \add_filter('jpeg_quality', array( __CLASS__, 'jpeg_thumbnail_quality' ), 10, 1);
    }

    // Sanitize images on upload
    if ( \get_option('seravo-enable-sanitize-uploads') === 'on' ) {
      \add_filter('wp_handle_upload_prefilter', array( __CLASS__, 'sanitize_images_on_upload' ), 10, 1);
    }

    // Allow SVG files
    \add_filter('upload_mimes', array( __CLASS__, 'add_to_mime_types' ));
    \add_filter('wp_handle_upload_prefilter', array( __CLASS__, 'sanitize_svg' ));
  }

  /**
   * Optimize images using command line tool
   *
   * Simply pass the image file path and exec to the background so the command
   * line tool will continue to do it's job. Tail logs in /data/log/ to verify
   * that the optimization ran.
   *
   * If the file does not exists or is not an image, the optimizer will just
   * quickly bail out without any problems.
   *
   * @param string $filename Name of the image file to be optimized.
   * @return string The filename that was passed in.
   */
  public static function optimize_images_on_upload( $filename ) {
    $max_width = \get_option('seravo-image-max-resolution-width');
    $max_height = \get_option('seravo-image-max-resolution-height');

    // Include --enable and max dimensions so that wp-optimize-images does not
    // need to invoke 'wp get option' itself and thus save ~1500 ms per image
    \exec(
      'wp-optimize-images --enable ' .
      ((\get_option('seravo-enable-strip-image-metadata') === 'on') ? '--strip-metadata' : '') . ' ' .
      '--set-max-resolution-width=' . (int) $max_width . ' ' .
      '--set-max-resolution-height=' . (int) $max_height . ' ' .
      '"' . $filename . '"  > /dev/null &'
    );
    // Redirect output AND background command so that PHP execution proceeds
    // and does not wait for command in exec to complete
    return $filename;
  }

  /**
   * Default WordPress JPEG quality is 82, which some find too low.
   * Override that with 90, which is almost always a high quality level.
   * @return int
   */
  public static function jpeg_thumbnail_quality() {
    return 90;
  }

  /**
   * Sanitize special characters of uploaded image filename.
   * @param mixed[] $file Filename to be sanitized.
   * @return mixed[] Sanitized filename.
   */
  public static function sanitize_images_on_upload( $file ) {
    // Convert all characters in the filename to HTML entities first and then
    // run a replacement to replace them with standard characters.
    $file['name'] = \preg_replace('/&([a-z])[a-z]+;/i', '$1', \htmlentities($file['name']));
    return $file;
  }

  /**
   * Add SVG to allowed mime types.
   * @param array<string,string> $mimes Allowed file-extensions and their mime types.
   * @return array<string,string> Allowed mime types with SVG added.
   */
  public static function add_to_mime_types( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
  }

  /**
   * Check if the file is SVG and sanitize if it is.
   * @param mixed[] $file Details of the image uploaded.
   * @return mixed[] Details of the image uploaded after sanization.
   */
  public static function sanitize_svg( $file ) {
    if ( ! isset($file['type']) || $file['type'] !== 'image/svg+xml' ) {
      return $file;
    }

    $sanitizer = new \enshrined\svgSanitize\Sanitizer();
    $sanitizer->minify(true);

    $dirty = \file_get_contents($file['tmp_name']);
    if ( $dirty === false ) {
      // Couldn't read the file
      $file['error'] = __(
        "This file couldn't be sanitized so for security reasons it wasn't uploaded",
        'seravo'
      );
      return $file;
    }

    $clean = $sanitizer->sanitize($dirty);
    if ( $clean === '' ) {
      $file['error'] = __(
        "This file couldn't be sanitized so for security reasons it wasn't uploaded",
        'seravo'
      );
    } else {
      // Replace unsanitized file content with sanitized
      \file_put_contents($file['tmp_name'], $clean);
    }

    return $file;
  }

}
