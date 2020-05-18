<?php
/*
 * Optimize images automatically right after upload
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('OptimizeImagesOnUpload') ) {

  class OptimizeImagesOnUpload {

    public static function load() {
      /*
       * Run optimizer for all thumbnail sizes. Don't use 'handle_upload' as it
       * fires too early and applies only to the original image. Instead hooking
       * into the thumbnail generation ensures that the resulting images that
       * are used in blog posts are optimized while the original image is left
       * untouched at upload time, and optimize it only after backups have run
       * and the batch optimization run executes.
       */
      add_filter('image_make_intermediate_size', array( __CLASS__, 'optimize_images_on_upload' ), 10, 1);

      /*
       * Modify the default jpeg_quality
       * https://developer.wordpress.org/reference/hooks/jpeg_quality/
       */
      add_filter('jpeg_quality', array( __CLASS__, 'jpeg_thumbnail_quality' ), 10, 1);
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
     */
    public static function optimize_images_on_upload( $filename ) {
      $max_width = get_option('seravo-image-max-resolution-width');
      $max_height = get_option('seravo-image-max-resolution-height');

      // Include --enable and max dimensions so that wp-optimize-images does not
      // need to invoke 'wp get option' itself and thus save ~1500 ms per image
      exec(
        'wp-optimize-images --enable ' .
        '--set-max-resolution-width=' . intval($max_width) . ' ' .
        '--set-max-resolution-height=' . intval($max_height) . ' ' .
        '"' . $filename . '"  > /dev/null &'
      );
      // Redirect output AND background command so that PHP execution proceeds
      // and does not wait for command in exec to complete
      return $filename;
    }

    /*
     * Default WordPress JPEG quality is 82, which some find too low.
     * Override that with 90, which is almost always a high quality level.
     */
    public static function jpeg_thumbnail_quality() {
      return 90;
    }
  }

  // Only load if image optimization is enabled
  if ( get_option('seravo-enable-optimize-images') === 'on' ) {
    OptimizeImagesOnUpload::load();
  }
}
