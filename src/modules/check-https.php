<?php
namespace Seravo;

/**
 * Class CheckHttps
 *
 * Checks that the WordPress siteurl begins with https:// so WordPress can
 * be served over HTTPS. Displays an error message on the dashboard page if https is
 * not enabled.
 */
class CheckHttps {

  /**
   * @return void
   */
  public static function load() {
     add_action('admin_notices', array( __CLASS__, '_seravo_check_https' ));
  }

  /**
   * @return void
   */
  public static function _seravo_check_https() {
     // Get the siteurl and home url and check if https is enabled, if not, show warning
    $siteurl = get_option('siteurl');
    $home = get_option('home');
    if ( strpos($siteurl, 'https') !== 0 || strpos($home, 'https') !== 0 ) {
      self::_seravo_show_https_warning();
    }
  }

  /**
   * @return void
   */
  public static function _seravo_show_https_warning() {
     $siteurl = get_option('siteurl'); ?>
    <div class="notice notice-error">
      <p>
        <?php
        printf(
          // translators: user's website url
          __('The HTTPS protocol is not currently active in the <a href="%s/wp-admin/options-general.php">site settings</a>. Please <a href="https://help.seravo.com/article/24-how-do-i-enable-the-https-on-our-website" target="_BLANK">use HTTPS</a>.', 'seravo'),
          esc_url($siteurl)
        );
        ?>
      </p>
    </div>
    <?php
  }
}
