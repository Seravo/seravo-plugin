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
		<div class="notice notice-error">
	  <?php

        printf(
			// translators: %1$s: current php version, %2$s: recommended php version
			__('The PHP-version %1$s currently in use is lower than the recommended %2$s. The security support for versions below the recommendation is ending.
			<a target="blank" href="https://help.seravo.com/en/knowledgebase/13-server-configuration/docs/107-set-your-site-to-use-newest-php-version">More information</a>', 'seravo'),
			PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, $recommended_version
		);

	  ?>
		</div>
	  <?php

    }

  }

  CheckPHPVersion::load();

}
