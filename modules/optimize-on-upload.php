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
      add_filter( 'image_make_intermediate_size', array( __CLASS__, 'optimize_images_on_upload' ), 10, 1 );
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
      exec('wp-optimize-images ' . $filename . ' &');
      return $filename;
    }
  }

  // Only load if image optimization is enabled
  if ( get_option( 'seravo-enable-optimize-images' ) == 'on' ) {
    OptimizeImagesOnUpload::load();
  }
}
