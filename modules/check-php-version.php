<?php
/**
 * Plugin name: Seravo Check PHP version
 * Description: Encourage site admins to upgrade the PHP version.
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

      // Show only on main dashboard once directly after login so it
      // will not clutter too much.
      if ( ! isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'wp-login.php') === false ) {
        return false;
      }

      // Get the php version and check if it is supported, if not, show a warning

      $recommended_version = '7.4';

      if ( version_compare(PHP_VERSION, $recommended_version, '<') ) {

        self::_seravo_show_php_recommendation($recommended_version);

      }

    }

    public static function _seravo_show_php_recommendation( $recommended_version ) {

      ?>
      <div class="notice notice-info">
      <p>
      <?php

      // The line below is very long, but PHPCS standards requires translation
      // strings to be one one line
      printf(
        // translators: %1$s: current php version, %2$s: recommended php version
        __('PHP %s is available but not used on this site. Developers might want to <a href="tools.php?page=upkeep_page">upgrade the latest PHP version</a> for faster performance and new features. Read more about <a target="_blank" href="https://help.seravo.com/article/41-set-your-site-to-use-newest-php-version">PHP version upgrades</a>.', 'seravo'),
        $recommended_version
      );
      ?>
      </p>
      </div>
      <?php

    }

  }

  CheckPHPVersion::load();

}
