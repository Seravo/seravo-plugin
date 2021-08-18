<?php

namespace Seravo\Module;

use \Seravo\Logs;
use \Seravo\Postbox\Template;

/**
 * Class AdminChecks
 *
 * Check that site has contact email, HTTPS enabled, where the last
 * login was from and recommended PHP version or show an warning notice.
 */
final class AdminChecks {
  use Module;

  /**
   * Check whether the module should be loaded or not.
   * @return bool Whether to load.
   */
  protected function should_load() {
    return \current_user_can('administrator');
  }

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    \add_action('admin_notices', array( __CLASS__, 'admin_checks' ));
  }

  /**
   * Make the checks and show warning notices if needed.
   * @return void
   */
  public static function admin_checks() {
    self::check_https();
    self::check_default_email();

    // Check PHP version and last login only once after login
    if ( isset($_SERVER['HTTP_REFERER']) && \strpos($_SERVER['HTTP_REFERER'], 'wp-login.php') !== false ) {
      self::check_php_version();

      // Apply filter for showing last login
      if ( \apply_filters('seravo_dashboard_last_login', true) === true ) {
        self::check_last_login();
      }
    }
  }

  /**
   * Check that the WordPress siteurl begins with https:// so WordPress can be served
   * over HTTPS. Displays an error message on the dashboard page if https is not enabled.
   * @return void
   */
  public static function check_https() {
    if ( \strpos(\get_option('siteurl'), 'https') === 0 && \strpos(\get_option('home'), 'https') === 0 ) {
      return;
    }

    $message = \sprintf(
      // translators: user's website url
      __('The HTTPS protocol is not currently active in the <a href="%s/wp-admin/options-general.php">site settings</a>. Please <a href="https://help.seravo.com/article/24-how-do-i-enable-the-https-on-our-website" target="_BLANK">use HTTPS</a>.', 'seravo'),
      \esc_url(\get_option('siteurl'))
    );
    Template::nag_notice(Template::paragraph($message), 'notice-error')->print_html();
  }

  /**
   * Check that the admin doesn't have default no-reply or development environment local.
   * Display an error message on the dashboard page if a bad local is found.
   * @return void
   */
  public static function check_default_email() {
    $email_local = \strtok(\get_option('admin_email'), '@');
    $bad_email_locals = array( 'no-reply', 'noreply', 'vagrant' );

    // Check if the email should should be changed. If so, show warning
    if ( ! \in_array($email_local, $bad_email_locals, true) ) {
      return;
    }

    $message = \sprintf(
      // translators: user's website url
      __('Warning: A generic admin email was detected in the <a href="%s/wp-admin/options-general.php" target="_BLANK">site settings</a>. Please update it.', 'seravo'),
      \esc_url(\get_option('siteurl'))
    );
    Template::nag_notice(Template::paragraph($message), 'notice-error')->print_html();
  }

  /**
   * Check if the PHP version is below the recommended one. If not, show
   * a warning notice to encourage site admins to upgrade the PHP version.
   * @return void
   */
  public static function check_php_version() {
    $recommended_version = '7.4';
    if ( \version_compare(PHP_VERSION, $recommended_version, '>=') ) {
      return;
    }

    $message = \sprintf(
      // translators: %1$s: recommended php version, %2$s: user's website url
      __('PHP %1$s is available but not used on this site. Developers might want to <a href="%2$s/wp-admin/tools.php?page=upkeep_page">upgrade the latest PHP version</a> for faster performance and new features. Read more about <a target="_blank" href="https://help.seravo.com/article/41-set-your-site-to-use-newest-php-version">PHP version upgrades</a>.', 'seravo'),
      $recommended_version,
      \esc_url(\get_option('siteurl'))
    );
    Template::nag_notice(Template::paragraph($message), 'notice-error')->print_html();
  }

  /**
   * Check if previous login is found and show a notification with
   * previous login info. This should only be called once after login.
   * @return void
   */
  public static function check_last_login() {
    $last_login = Logs::retrieve_last_login();
    if ( $last_login === false ) {
      return;
    }

    $message = \wp_sprintf(
      /* translators:
       * %1$s username of the current user
       * %2$s date of last login
       * %3$s time of last login
       * %4$s IP address or reverse domain of the last login
       */
      __('Welcome, %1$s! Your previous login was on %2$s at %3$s from %4$s.', 'seravo'),
      $last_login['user'],
      $last_login['date'],
      $last_login['time'],
      $last_login['domain']
    );
    Template::nag_notice(Template::paragraph($message), 'notice notice-info is-dismissible')->print_html();
  }

}
