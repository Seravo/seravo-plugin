<?php
/*
 * Plugin name: Optimize Images
 * Description: Enable users to set the maximum resolution of images on the site,
 * thus reducing image file size
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Optimize_Images') ) {
  class Optimize_Images {

    // Default maximum resolution for images
    private static $max_width_default = 4000;
    private static $max_height_default = 4000;

    // Minimum resolution for images. Can't be set any lower by user.
    private static $min_width = 500;
    private static $min_height = 500;

    public static function load() {
      add_action('admin_init', array( __CLASS__, 'register_optimize_image_settings' ));
      add_action('admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_styles' ));

      seravo_add_postbox(
        'optimize-images',
        __('Optimize Images (beta)', 'seravo'),
        array( __CLASS__, 'optimize_images_postbox' ),
        'tools_page_development_page',
        'normal'
      );
    }

    public static function register_optimize_image_settings() {
      add_settings_section(
        'seravo-optimize-images-settings',
        '',
        array( __CLASS__, 'optimize_images_settings_description' ),
        'optimize_images_settings'
      );

      register_setting('seravo-optimize-images-settings-group', 'seravo-enable-optimize-images');
      register_setting(
        'seravo-optimize-images-settings-group',
        'seravo-image-max-resolution-width',
        array( 'sanitize_callback' => array( __CLASS__, 'sanitize_image_width' ) )
      );
      register_setting(
        'seravo-optimize-images-settings-group',
        'seravo-image-max-resolution-height',
        array( 'sanitize_callback' => array( __CLASS__, 'sanitize_image_height' ) )
      );

      add_settings_field(
        'seravo-images-enabled-field',
        __('Optimize Images', 'seravo'),
        array( __CLASS__, 'seravo_image_enabled_field' ),
        'optimize_images_settings',
        'seravo-optimize-images-settings'
      );
      add_settings_field(
        'seravo-images-max-width-field',
        __('Maximum Image Width (px)', 'seravo'),
        array( __CLASS__, 'seravo_image_max_width_field' ),
        'optimize_images_settings',
        'seravo-optimize-images-settings'
      );
      add_settings_field(
        'seravo-images-max-height-field',
        __('Maximum Image Height (px)', 'seravo'),
        array( __CLASS__, 'seravo_image_max_height_field' ),
        'optimize_images_settings',
        'seravo-optimize-images-settings'
      );

      self::check_default_settings();
    }

    public static function admin_enqueue_styles( $page ) {
      wp_register_script('optimize-images', plugin_dir_url(__DIR__) . '/js/optimize-images.js', array(), Helpers::seravo_plugin_version());
      wp_register_style('optimize-images', plugin_dir_url(__DIR__) . 'style/optimize-images.css', array(), Helpers::seravo_plugin_version());

      if ( $page === 'tools_page_development_page' ) {
        wp_enqueue_script('optimize-images');
        wp_enqueue_style('optimize-images');
      }
    }

    public static function check_default_settings() {
      // Set the default settings for the user if the settings don't exist in database
      if ( get_option('seravo-image-max-resolution-width') === false || get_option('seravo-image-max-resolution-height') === false ) {
        update_option('seravo-image-max-resolution-width', self::$max_width_default);
        update_option('seravo-image-max-resolution-height', self::$max_height_default);
      }
      if ( get_option('seravo-enable-optimize-images') === false ) {
        update_option('seravo-enable-optimize-images', '');
      }
    }

    public static function seravo_image_max_width_field() {
      $image_max_width = get_option('seravo-image-max-resolution-width');
      echo '<input type="text" class="' . self::get_input_field_attributes()[0] . '" name="seravo-image-max-resolution-width"' . self::get_input_field_attributes()[1] . '
        placeholder="' . __('Width', 'seravo') . '" value="' . $image_max_width . '">';
    }

    public static function seravo_image_max_height_field() {
      $image_max_height = get_option('seravo-image-max-resolution-height');
      echo '<input type="text" class="' . self::get_input_field_attributes()[0] . '" name="seravo-image-max-resolution-height" ' . self::get_input_field_attributes()[1] . ' placeholder="'
              . __('Height', 'seravo') . '" value="' . $image_max_height . '">';
    }

    public static function seravo_image_enabled_field() {
      echo '<input type="checkbox" name="seravo-enable-optimize-images" id="enable-optimize-images" ' . checked('on', get_option('seravo-enable-optimize-images'), false) . '>';
    }

    public static function optimize_images_settings_description() {
      echo '<p>' . __('Optimization reduces image file size. This improves the performance and browsing experience of your site.', 'seravo') . '</p>' .
      '<p>' . __('By setting the maximum image resolution, you can determine the maximum allowed dimensions for images.', 'seravo') . '</p>' .
      '<p>' . __('For further information, refer to our <a href="https://help.seravo.com/en/knowledgebase/23-managing-wordpress/docs/119-seravo-plugin-optimize-images">knowledgebase article</a>.', 'seravo') . '</p>';
    }

    public static function sanitize_image_width( $width ) {
      if ( $width < self::$min_width && $width !== null && get_option('seravo-enable-optimize-images') === 'on' ) {
        add_settings_error(
          'seravo-image-max-resolution-width',
          'invalid-width',
          sprintf(
            // translators: %s numeric value for the minimum image width
            __('The minimum width for image optimisation is %1$s px. Setting suggested width of %2$s px.', 'seravo'),
            self::$min_width,
            self::$max_width_default
          )
        );
        return self::$max_width_default;
      }
      return $width;
    }

    public static function sanitize_image_height( $height ) {
      if ( $height < self::$min_height && $height !== null && get_option('seravo-enable-optimize-images') === 'on' ) {
        add_settings_error(
          'seravo-image-max-resolution-height',
          'invalid-height',
          // translators: %s numeric value for the minimum image height
          sprintf(__('The minimum height for image optimisation is %1$s px. Setting suggested height of %2$s px.', 'seravo'), self::$min_height, self::$max_height_default)
        );
        return self::$max_height_default;
      }
      return $height;
    }

    public static function get_input_field_attributes() {
      if ( get_option('seravo-enable-optimize-images') === 'on' ) {
        return array( 'max-resolution-field', '' );
      }
      return array( 'max-resolution-field', 'disabled=""' );
    }

    public static function optimize_images_postbox() {
      settings_errors();
      echo '<form method="post" action="options.php" class="seravo-general-form">';
      settings_fields('seravo-optimize-images-settings-group');
      do_settings_sections('optimize_images_settings');
      submit_button(__('Save', 'seravo'), 'primary', 'btnSubmit');
      echo '</form>';
    }
  }
  Optimize_Images::load();
}
