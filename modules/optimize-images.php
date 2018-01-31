<?php
/*
 * Plugin name: Optimize Images
 * Description: Enable users to set the maximum resolution of images on the site,
 * thus reducing image file size
 * Version: 1.0
 */

namespace Seravo;

if ( ! class_exists('Optimize_images') ) {
    class Optimize_images {

        // Default maximum resolution for images. Can't be set any lower by user.
        private static $max_width_default = 4000;
        private static $max_height_default = 4000;

        public static function load() {
            add_action( 'admin_init', array( __CLASS__, 'register_optimize_image_settings' ) );
            add_action( 'admin_menu', array( __CLASS__, 'register_optimize_images_page' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_styles' ) );
        }

        public static function register_optimize_image_settings() {
            add_settings_section(
              'seravo-optimize-images-settings', __( 'Optimize Images', 'seravo' ) . ' (beta)',
              array( __CLASS__, 'optimize_images_settings_description' ), 'optimize_images_settings'
            );

            register_setting( 'seravo-optimize-images-settings-group', 'seravo-enable-optimize-images' );
            register_setting(
              'seravo-optimize-images-settings-group', 'seravo-image-max-resolution-width',
              array( 'sanitize_callback' => array( __CLASS__, 'sanitize_image_width' ) )
            );
            register_setting(
              'seravo-optimize-images-settings-group', 'seravo-image-max-resolution-height',
              array( 'sanitize_callback' => array( __CLASS__, 'sanitize_image_height' ) )
            );

            add_settings_field(
              'seravo-images-enabled-field', __( 'Limit Image Resolution', 'seravo' ),
              array( __CLASS__, 'seravo_image_enabled_field' ), 'optimize_images_settings', 'seravo-optimize-images-settings'
            );
            add_settings_field(
              'seravo-images-max-width-field', __( 'Maximum Image Width (px)', 'seravo' ),
              array( __CLASS__, 'seravo_image_max_width_field' ), 'optimize_images_settings', 'seravo-optimize-images-settings'
            );
            add_settings_field(
              'seravo-images-max-height-field', __( 'Maximum Image Height (px)', 'seravo' ),
              array( __CLASS__, 'seravo_image_max_height_field' ), 'optimize_images_settings', 'seravo-optimize-images-settings'
             );

            self::check_default_settings();
        }

        public static function admin_enqueue_styles( $page ) {
            wp_register_script( 'optimize-images', plugin_dir_url(__DIR__) . '/js/optimize-images.js', array(), null );

            if ( $page === 'tools_page_optimize_images_page' ) {
                wp_enqueue_script( 'optimize-images' );
            }
        }

        public static function check_default_settings() {
            // Set the default settings for the user if the settings don't exist in database
            if ( get_option( 'seravo-image-max-resolution-width' ) === false || get_option( 'seravo-image-max-resolution-height' ) === false ) {
                update_option( 'seravo-image-max-resolution-width', self::$max_width_default );
                update_option( 'seravo-image-max-resolution-height', self::$max_height_default );
            }
            if ( get_option( 'seravo-enable-optimize-images' ) === false ) {
                update_option( 'seravo-enable-optimize-images', '' );
            }
        }

        public static function register_optimize_images_page() {
            add_submenu_page(
              'tools.php', __( 'Optimize Images', 'seravo' ), __( 'Optimize Images', 'seravo' ),
              'manage_options', 'optimize_images_page', array( __CLASS__, 'load_optimize_images_page' )
            );
        }

        public static function load_optimize_images_page() {
            settings_errors();
            echo '<div class="wrap">
                <form method="post" action="options.php" class="seravo-general-form">';
            settings_fields( 'seravo-optimize-images-settings-group' );
            do_settings_sections( 'optimize_images_settings' );
            submit_button( __( 'Save', 'seravo' ), 'primary', 'btnSubmit' );
            echo '</form></div>';
        }

        public static function seravo_image_max_width_field() {
            $image_max_width = get_option( 'seravo-image-max-resolution-width' );
            echo '<input type="text" class="' . self::get_input_field_attributes()[0] . '" name="seravo-image-max-resolution-width"' . self::get_input_field_attributes()[1] . '
                placeholder="' . __( 'Width', 'seravo' ) . '" value="' . $image_max_width . '">';
        }

        public static function seravo_image_max_height_field() {
            $image_max_height = get_option( 'seravo-image-max-resolution-height' );
            echo '<input type="text" class="' . self::get_input_field_attributes()[0] . '" name="seravo-image-max-resolution-height" ' . self::get_input_field_attributes()[1] . ' placeholder="'
                            . __( 'Height', 'seravo' ) . '" value="' . $image_max_height . '">';
        }

        public static function seravo_image_enabled_field() {
            echo '<input type="checkbox" name="seravo-enable-optimize-images" id="enable-optimize-images" ' . checked( 'on' , get_option( 'seravo-enable-optimize-images' ), false ) . '>';
        }

        public static function optimize_images_settings_description() {
            _e('Change the maximum image size for your site. Using a smaller image size
            significantly improves site performance and saves disk space.', 'seravo');
        }

        public static function sanitize_image_width( $width ) {
            if ( $width < self::$max_width_default && $width !== null && get_option( 'seravo-enable-optimize-images' ) === 'on' ) {
                add_settings_error( 'seravo-image-max-resolution-width', 'invalid-width',
                sprintf( __( 'The minimum width for image optimisation is %s px.', 'seravo' ), self::$max_width_default ) );
                return self::$max_width_default;
            }
            return $width;
        }

        public static function sanitize_image_height( $height ) {
            if ( $height < self::$max_height_default && $height !== null && get_option( 'seravo-enable-optimize-images' ) === 'on' ) {
                add_settings_error(
                  'seravo-image-max-resolution-height', 'invalid-height',
                  sprintf( __( 'The minimum height for image optimisation is %s px.', 'seravo' ), self::$max_height_default )
                );
                return self::$max_height_default;
            }
            return $height;
        }

        public static function get_input_field_attributes() {
            if ( get_option( 'seravo-enable-optimize-images' ) === 'on' ) {
                return array( 'max-resolution-field', '' );
            }
            return array( 'max-resolution-field-disabled', 'hidden' );
        }


    }
    Optimize_images::load();
}
