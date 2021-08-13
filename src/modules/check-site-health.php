<?php
namespace Seravo;

use \Seravo\Logs;
use \Seravo\Postbox\Template;

/**
 * Class SiteHealth
 *
 * Class for checking some general settings and potential issues
 * regarding Seravo WordPress installation.
 */
class SiteHealth {

  /**
   * @var string[]|mixed[]|null
   */
  private static $potential_issues;
  /**
   * @var string[]|mixed[]|null
   */
  private static $no_issues;
  /**
   * @var string[]
   */
  private static $bad_plugins = array(
    'https-domain-alias',
    'wp-palvelu-plugin',
  );

  /**
   * Helper method for the class to execute given command & wrapping the return value
   * automatically as result array.
   * @param string $command The command to be executed.
   * @return string[] Output from the command.
   */
  private static function exec_command( $command ) {
    $output = array();
    \exec($command, $output);

    return $output;
  }

  /**
   * Check siteurl & home HTTPS usage status
   * Logic is from check-https-module
   * @return void
   */
  private static function check_https() {
    $siteurl = \get_option('siteurl');
    $home = \get_option('home');
    $https_tooltip = \__('Read more about HTTPS on our <a href="https://seravo.com/blog/https-is-not-optional/" target="_blank">blog</a>', 'seravo');

    if ( \strpos($siteurl, 'https') !== 0 || \strpos($home, 'https') !== 0 ) {
      self::$potential_issues[\__('HTTPS is disabled', 'seravo')] = $https_tooltip;
    } else {
      self::$no_issues[\__('HTTPS is enabled', 'seravo')] = $https_tooltip;
    }
  }

  /**
   * Check that some recaptcha plugin is installed and active on WordPress.
   * @return void
   */
  private static function check_recaptcha() {
    $output = self::exec_command('wp plugin list');
    $captcha_found = false;
    $captcha_tooltip = \__('Recaptcha is recommended as it can help protect your site from spam and abuse.', 'seravo');

    foreach ( $output as $plugin ) {
      // check that captcha is found and it's not inactive
      if ( \strpos($plugin, 'captcha') !== false && \strpos($plugin, 'inactive') === false ) {
        self::$no_issues[\__('Recaptcha is enabled', 'seravo')] = $captcha_tooltip;
        $captcha_found = true;
        break;
      }
    }

    if ( ! $captcha_found ) {
      self::$potential_issues[\__('Recaptcha is disabled', 'seravo')] = $captcha_tooltip;
    }
  }

  /**
   * Count the inactive themes.
   * @return void
   */
  private static function check_inactive_themes() {
    $output = self::exec_command('wp theme list');
    $inactive_themes = 0;
    $theme_tooltip = \__('It is recommended to remove inactive themes.', 'seravo');

    foreach ( $output as $line ) {
      if ( \strpos($line, 'inactive') !== false ) {
        ++$inactive_themes;
      }
    }

    if ( $inactive_themes > 0 ) {
      /* translators:
        * %1$s number of inactive themes
        */
      $themes_msg = \wp_sprintf(\_n('Found %1$s inactive theme', 'Found %1$s inactive themes', $inactive_themes, 'seravo'), \number_format_i18n($inactive_themes));
      self::$potential_issues[$themes_msg] = $theme_tooltip;
    } else {
      self::$no_issues[\__('No inactive themes', 'seravo')] = $theme_tooltip;
    }
  }

  /**
   * Check potential bad and deprecated plugins specified by bad_plugins array.
   * @return void
   */
  private static function check_plugins() {
    // check inactive plugins and all plugin related issues
    $output = self::exec_command('wp plugin list');
    $inactive_plugins = 0;
    $bad_plugins_found = 0;
    $plugin_tooltip = \__('It is recommended to remove inactive plugins and features.', 'seravo');
    $deprecated_tooltip = \__('Deprecated plugins and features are obsolete and should no longer be used.', 'seravo');

    foreach ( $output as $line ) {

      if ( \strpos($line, 'inactive') !== false ) {
        ++$inactive_plugins;
      }

      foreach ( self::$bad_plugins as $plugin ) {

        if ( \strpos($line, $plugin) !== false ) {
          $error_msg = '<b>' . $plugin . '</b> ' . \__('is deprecated');
          self::$potential_issues[$error_msg] = $deprecated_tooltip;
          ++$bad_plugins_found;
        }
      }
    }

    if ( $bad_plugins_found === 0 ) {
      self::$no_issues[\__('No deprecated features or plugins', 'seravo')] = $deprecated_tooltip;
    }

    if ( $inactive_plugins > 0 ) {
      /* translators:
       * %1$s number of inactive plugins
       */
      $plugins_msg = \wp_sprintf(\_n('Found %1$s inactive plugin', 'Found %1$s inactive plugins', $inactive_plugins, 'seravo'), \number_format_i18n($inactive_plugins));
      self::$potential_issues[$plugins_msg] = $plugin_tooltip;
    } else {
      self::$no_issues[\__('No inactive plugins', 'seravo')] = $plugin_tooltip;
    }
  }

  /**
   * Fetch the PHP error count by using the error count of login notifications module.
   * @return void
   */
  private static function check_php_errors() {
    $php_info = '<a href="' . \get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log" target="_blank">php-error.log</a>';
    $error_tooltip = \__('PHP related errors are usually a sign of something being broken on the code.', 'seravo');

    $php_error_count = Logs::get_week_error_count();
    if ( $php_error_count === false ) {
      if ( \file_exists('/data/log/php-error.log') ) {
        self::$potential_issues[\__('Too many PHP errors to count', 'seravo')] = $error_tooltip;
      } else {
        self::$no_issues[\__('No php errors on log', 'seravo')] = $error_tooltip;
      }
    } elseif ( $php_error_count === 0 ) {
      self::$no_issues[\__('No php errors on log', 'seravo')] = $error_tooltip;
    } else {
      /* translators:
       * %1$s number of errors in the log
       * %2$s url to php-error.log */
      $php_errors_msg = \wp_sprintf(\_n('%1$s error on %2$s', 'At least %1$s errors on %2$s', $php_error_count, 'seravo'), \number_format_i18n($php_error_count), $php_info);
      self::$potential_issues[$php_errors_msg] = $error_tooltip;
    }
  }

  /**
   * Execute command wp-test and wrap up whether it runs successfully or not.
   * @return void
   */
  private static function check_wp_test() {
    \exec('wp-test', $output, $return_variable);
    $wp_test_tooltip = \__('<code>wp-test</code> checks if the site works normally. It also checks whether automatic updates can continue.', 'seravo');

    if ( $return_variable === 0 ) {
      self::$no_issues[\__('Command <code>wp-test</code> runs successfully', 'seravo')] = $wp_test_tooltip;
    } else {
      self::$potential_issues[\__('Command <code>wp-test</code> fails', 'seravo')] = $wp_test_tooltip;
    }
  }

  /**
   * Return passed and failed tests formatted in HTML.
   * @return mixed[] Output, title and status color.
   */
  private static function result_to_html() {
    $output = '';

    if ( self::$potential_issues === null || self::$potential_issues === array() ) {
      $title = \__('No issues were found', 'seravo');
      $status_color = Ajax\FancyForm::STATUS_GREEN;
    } else {
      $title = \__('Potential issues were found', 'seravo');
      $status_color = Ajax\FancyForm::STATUS_YELLOW;
      $output .= Template::section_title(\__('Potential issues', 'seravo'), 'failure')->to_html();
      $counter = 0;

      foreach ( self::$potential_issues as $element => $tooltip ) {
        $tooltip_component = Template::tooltip($tooltip)->to_html();

        if ( $counter === \count(self::$potential_issues) - 1 ) {
          // Apply border-bottom to the last element
          $output .= '<div class="issue-box" style="border-bottom: 1px solid #ccd0d4">' . $tooltip_component . $element . '</div>';
        } else {
          $output .= Template::text($tooltip_component . $element, 'issue-box')->to_html();
        }
        ++$counter;
      }
      $output .= '<br>';
    }

    $output .= Template::section_title(\__('Passed tests', 'seravo'), 'success')->to_html();
    $counter = 0;

    if ( self::$no_issues !== null ) {
      foreach ( self::$no_issues as $element => $tooltip ) {
        $tooltip_component = Template::tooltip($tooltip)->to_html();

        if ( $counter === \count(self::$no_issues) - 1 ) {
          // Apply border-bottom to the last element
          $output .= '<div class="success-box" style="border-bottom: 1px solid #ccd0d4">' . $tooltip_component . $element . '</div>';
        } else {
          $output .= Template::text($tooltip_component . $element, 'success-box')->to_html();
        }
        ++$counter;
      }
    }

    return array( $output, $title, $status_color );
  }

  /**
   * Run the test set and return results.
   * @param bool $formatted Return formatted output.
   * @return array<int, array<string, int>> Site status results.
   */
  public static function check_site_status( $formatted = false ) {
    self::$potential_issues = array();
    self::$no_issues = array();

    self::check_https();
    self::check_recaptcha();

    self::check_inactive_themes();
    self::check_plugins();

    self::check_php_errors();
    self::check_wp_test();

    if ( $formatted ) {
      return self::result_to_html();
    }

    return array( self::$potential_issues, self::$no_issues );
  }
}
