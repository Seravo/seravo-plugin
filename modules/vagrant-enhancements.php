<?php
/*
 * Plugin name: WP-palvelu Vagrant Fixes
 * Description: Contains custom fixes for vagrant environment
 * Version: 1.0
 */

namespace WPPalvelu\Vagrant;

if (!class_exists('Enhancements')) {
  class Enhancements {

    /**
     * Loads hooks and filters
     */
    public static function load() {

      /**
       * Redirect non found (404) uploads to production
       * This helps developers so they won't need to transfer missing uploads to vagrant
       */
      add_action( 'template_redirect', array(__CLASS__, 'redirect_404_uploads_to_production'));
    }

    /*
     * If requested uploaded content doesn't exist, redirect it to production alternative
     */
    public static function redirect_404_uploads_to_production() {
      global $wp_query;

      if ($wp_query->is_404) {
        $uploads_url = "";

        // If this is subdirectory installation use subdirectory part
        $parsed_url = parse_url(site_url());
        if ($parsed_url['path']) {
          $uploads_url .= $parsed_url['path'];
        }
        
        // Get uploads directory url
        $uploads_url .= wp_upload_dir()['baseurl'];

        // Check if request string starts with uploads path
        if(strrpos($_SERVER['REQUEST_URI'], $uploads_url, -strlen($haystack)) !== FALSE) {
          do_the_redirect_to_production($_SERVER['REQUEST_URI']);
        }
      }
    }

    /*
     * Checks if production is defined in pre defined config.yml
     */
    private static function do_the_redirect_to_production($resource_path) {

      // Get production domain from config.yml
      if ( false === ( $production_url = get_transient( 'wpp_production_url' ) ) ) {

        $config_file = dirname(dirname(ABSPATH)).'/config.yml';
        if (file_exists($config_file) && class_exists('YAML')) {
          $config = YAML::parse(file_get_contents(dirname(dirname(ABSPATH)).'/config.yml'));
          if (isset($config['production']['domain'])) {
            $production_host = $config['production']['domain'];

            // Get only the host part if somehow it contained something else
            $parsed_url = parse_url($production_host);
            if(isset($parsed_url['host'])) {
              $production_host = $parsed_url['host'];
            }

            // Redirect needs to be absolute path
            if (is_ssl()) {
              $production_url = "https://".$production_host;
            } else {
              $production_url = "http://".$production_host;
            }

            set_transient( 'wpp_production_url', $production_url, 1 * HOUR_IN_SECONDS );
          }
        }
      }

      // Redirect to production assets if config.yml defines production url
      if ($production_url) {
        $production_resource = $production_url.$resource_path;
        error_log("Redirected non-existent upload to production: {$production_resource}");
        wp_redirect( $production_resource, 302 );
      }
    }
  }

  Enhancements::load();
}
