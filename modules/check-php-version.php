<?php
/**
 * Plugin name: Seravo Check PHP version
 * Description: Checks that the PHP version is supported. If the version is lower than recommended,
 * it displays a warning on the dashboard.
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {

  die('Access denied!');

}

if ( ! class_exists('CheckPHPVersion') ) {

  class CheckPHPVersion {

    public static function load() {

      add_action('admin_notices', array( __CLASS__, '_seravo_check_php_version' ));

    }

    public static function _seravo_check_php_version() {

      // Get the php version and check if it is supported, if not, show a warning

      $recommended_version = '7.2';

      if ( version_compare( PHP_VERSION, $recommended_version, '<' ) ) {

        self::_seravo_show_php_warning( $recommended_version );

      }

    }

    public static function _seravo_show_php_warning( $recommended_version ) {

	  ?>

		<div class="notice notice-error seravo-notice">
      <div class="seravo-banner">
        <div class="seravo-emblem">
          <a href="https://seravo.com">
            <svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd" viewBox="0 0 34 25" width="34" height="25" >
              <sodipodi:namedview pagecolor="#ffffff" bordercolor="#666666" borderopacity="1" objecttolerance="10" gridtolerance="10" guidetolerance="10" id="namedview132" />
              <defs id="defs113">
                <style id="style111">.cls-2{fill:#fff}</style>
              </defs>
              <path d="M0 9.3006C0 2.5719 10.44057 1.6086 10.44057 1.6086L33.97477.0 33.85747 19.5789c0 4.9585-11.7241 7.0158-22.89 4.0416v.016L.00653 20.6037z" id="path115" inkscape:connector-curvature="0" style="fill:#00a9d9;fill-rule:evenodd;stroke-width:.01675605" />
              <path class="cls-2" d="m17.56337 21.276c-1.5818.0-3.7674-.2301-5.2344-.633-.345-.086-.5464-.3166-.5464-.6621v-2.3318c0-.2878.201-.5467.5464-.5467h.1151c1.5529.2011 3.969.4021 4.8892.4021 1.3806.0 1.6969-.3741 1.6969-1.1226.0-.4318-.259-.7483-1.0642-1.2088l-3.7387-2.1578c-1.6106-.9201-2.5597-2.3878-2.5597-4.2584.0-2.9067 1.927-4.4607 5.8958-4.4607 2.2719.0 3.6528.2879 5.1191.6621.3454.086.5464.3165.5464.6617v2.3311c0 .3453-.201.5467-.4887.5467h-.086c-.834-.1151-3.3073-.3741-4.7742-.3741-1.1216.0-1.5248.1727-1.5248.8346.0.4316.3164.662.8915 1.0072l3.5663 2.0442c2.3871 1.3812 2.9049 2.8775 2.9049 4.4315 5e-4 2.7044-1.9553 4.8348-6.1542 4.8348z" id="path117" inkscape:connector-curvature="0" style="fill:#fff;stroke-width:.01675605" />
              <path class="cls-2" d="M26.65517 10.1155z" id="path129" style="fill:#fff;stroke-width:.01675605"/>
            </svg>
          </a>
        </div>
      </div>
      <div class="seravo-notice-content">
        <span>
      	  <?php

          // The line below is very long, but PHPCS standards requires translation
          // strings to be one one line
          printf(
            // translators: %1$s: current php version, %2$s: recommended php version
            __('The PHP version %1$s currently in use is lower than the recommended %2$s. Security updates might not be available for the version in use. Please consider <a target="_blank" href="https://help.seravo.com/en/knowledgebase/13/docs/107-set-your-site-to-use-newest-php-version">updating the PHP version</a>.', 'seravo'),
            PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, $recommended_version
          );

      	  ?>
        </span>
      </div>
		</div>
	  <?php

    }

  }

  CheckPHPVersion::load();

}
