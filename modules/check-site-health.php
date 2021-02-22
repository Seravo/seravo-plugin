<?php
/*
 * Description: Class for checking some general settings and potential issues
 * regarding WordPress installation
 */

namespace Seravo;

if ( ! defined('ABSPATH') ) {
    die('Access denied!');
}

if ( ! class_exists('Site_Health') ) {
  class Site_Health {
    private static $potential_issues;
    private static $no_issues;
    private static $bad_plugins = array(
      'https-domain-alias',
      'wp-palvelu-plugin',
    );

    private static function exec_command( $command ) {
        $output = array();
        exec($command, $output);

        return $output;
    }

    private static function check_https() {
        /* Logic from check-https module
         *Get the siteurl and home url and check if https is enabled, if not, show warning
        */
      $siteurl = get_option('siteurl');
      $home = get_option('home');
      if ( strpos($siteurl, 'https') !== 0 || strpos($home, 'https') !== 0 ) {
        array_push(self::$potential_issues, __('HTTPS is disabled', 'seravo'));
      } else {
        array_push(self::$no_issues, __('HTTPS is enabled', 'seravo'));
      }
    }

    private static function check_recaptcha() {
        $output = self::exec_command('wp plugin list');
        $captcha_found = false;

        foreach ( $output as $plugin ) {
        // check that captcha is found and it's not inactive
        if ( strpos($plugin, 'captcha') && strpos($plugin, 'inactive') === false ) {
            array_push(self::$no_issues, __('Recaptcha is enabled', 'seravo'));
            $captcha_found = true;
            break;
          }
        }

        if ( ! $captcha_found ) {
          array_push(self::$potential_issues, __('Recaptcha is disabled', 'seravo'));
        }
    }

    private static function check_inactive_themes() {
        $output = self::exec_command('wp theme list');
        $inactive_themes = 0;

        foreach ( $output as $line ) {
        if ( strpos($line, 'inactive') ) {
            $inactive_themes++;
          }
        }

        if ( $inactive_themes > 0 ) {
        /* translators:
        * %1$s number of inactive themes
        */
          $themes_msg = wp_sprintf(_n('Found %1$s inactive theme', 'Found %1$s inactive themes', $inactive_themes, 'seravo'), number_format_i18n($inactive_themes));
          array_push(self::$potential_issues, $themes_msg);
        } else {
          array_push(self::$no_issues, __('No inactive themes', 'seravo'));
        }
    }

    private static function check_plugins() {
        // check inactive plugins and all plugin related issues
        $output = self::exec_command('wp plugin list');
        $inactive_plugins = 0;
        $bad_plugins_found = 0;

        foreach ( $output as $line ) {

        if ( strpos($line, 'inactive') ) {
          $inactive_plugins++;
        }

        foreach ( self::$bad_plugins as $plugin ) {

          if ( str_contains($line, $plugin) ) {
            $error_msg = '<b>' . $plugin . '</b> ' . __('is deprecated');
            array_push(self::$potential_issues, $error_msg);
            $bad_plugins_found++;
            }
          }
        }

        if ( $bad_plugins_found === 0 ) {
          array_push(self::$no_issues, __('No deprecated features or plugins'));
        }

        if ( $inactive_plugins > 0 ) {
        /* translators:
        * %1$s number of inactive plugins
        */
          $plugins_msg = wp_sprintf(_n('Found %1$s inactive plugin', 'Found %1$s inactive plugins', $inactive_plugins, 'seravo'), number_format_i18n($inactive_plugins));
          array_push(self::$potential_issues, $plugins_msg);
        } else {
          array_push(self::$no_issues, __('No inactive plugins', 'seravo'));
        }
    }

    private static function check_php_errors() {
        $php_info = '<a href="' . get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log" target="_blank">php-error.log</a>';
        $php_errors = Login_Notifications::retrieve_error_count();

        if ( $php_errors > 0 ) {
        /* translators:
        * %1$s number of errors in the log
        * %2$s url to php-error.log */
          $php_errors_msg = wp_sprintf(_n('%1$s error on %2$s', '%1$s errors on %2$s', $php_errors, 'seravo'), number_format_i18n($php_errors), $php_info);
          array_push(self::$potential_issues, $php_errors_msg);
        } else {
          array_push(self::$no_issues, __('No php errors on log', 'seravo'));
        }
    }

    private static function check_wp_test() {
      exec('wp-test', $output, $return_variable);

      if ( $return_variable === 0 ) {
        array_push(self::$no_issues, __('Command <code>wp-test</code> runs successfully', 'seravo'));
      } else {
        array_push(self::$potential_issues, __('Command <code>wp-test</code> fails'));
      }
    }

    public static function check_site_status() {
        self::$potential_issues = array();
        self::$no_issues = array();

        self::check_https();
        self::check_recaptcha();

        self::check_inactive_themes();
        self::check_plugins();

        self::check_php_errors();
        self::check_wp_test();

        $result_array = array( self::$potential_issues, self::$no_issues );
        return $result_array;
    }
  }
}

