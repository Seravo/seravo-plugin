<?php
namespace Seravo;

/**
 * Class CheckPHPVersion
 *
 * Encourage site admins to upgrade the PHP version.
 */
class CheckPHPVersion {

  /**
   * @return void
   */
  public static function load() {
    \add_action('admin_notices', array( __CLASS__, '_seravo_check_php_version' ));
    \add_filter('wp_update_php_url', array( __CLASS__, '_seravo_update_php_url' ));
  }

  /**
   * @return bool|void
   */
  public static function _seravo_check_php_version() {
    // Show only on main dashboard once directly after login so it
    // will not clutter too much.
    if ( ! isset($_SERVER['HTTP_REFERER']) || \strpos($_SERVER['HTTP_REFERER'], 'wp-login.php') === false ) {
      return false;
    }

    // Get the php version and check if it is supported, if not, show a warning

    $recommended_version = '7.4';

    if ( \version_compare(PHP_VERSION, $recommended_version, '<') ) {
      self::_seravo_show_php_recommendation($recommended_version);
    }
  }

  /**
   * @return string
   */
  public static function _seravo_update_php_url() {
     return \__('https://help.seravo.com/article/41-set-your-site-to-use-newest-php-version', 'seravo');
  }

  /**
   * @param string $recommended_version The currently recommended PHP-version.
   * @return void
   */
  public static function _seravo_show_php_recommendation( $recommended_version ) {
    $url = self::_seravo_update_php_url();
    ?>
    <div class="notice notice-info">
      <p>
        <?php

        // The line below is very long, but PHPCS standards requires translation
        // strings to be one one line
        \printf(
          // translators: %1$s: recommended php version, %2$s: url to the update page
          \__('PHP %1$s is available but not used on this site. Developers might want to <a href="tools.php?page=upkeep_page">upgrade the latest PHP version</a> for faster performance and new features. Read more about <a target="_blank" href="%2$s">PHP version upgrades</a>.', 'seravo'),
          $recommended_version,
          $url
        );
        ?>
      </p>
    </div>
    <?php

  }
}
