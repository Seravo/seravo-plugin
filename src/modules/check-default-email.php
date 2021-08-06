<?php
namespace Seravo;

/**
 * Class CheckDefaultEmail
 *
 * Checks if the WordPress admin email address has an evidently
 * bad local part, i.e. noreply@example.com or vagrant@example.com. If so,
 * show a notification suggesting to change it to something better.
 */
class CheckDefaultEmail {

  /**
   * @var string[]
   */
  private static $bad_email_locals = array( 'no-reply', 'noreply', 'vagrant' );

  /**
   * @return void
   */
  public static function load() {
     add_action('admin_notices', array( __CLASS__, '_seravo_check_default_email' ));
  }

  /**
   * @return void
   */
  public static function _seravo_check_default_email() {
     // Get admin email option and take the local part before the @ sign
    $email = get_option('admin_email');
    $email_local = strtok($email, '@');

    // Check if the email should should be changed. If so, show warning
    if ( in_array($email_local, self::$bad_email_locals, true) ) {
      self::_seravo_show_email_warning();
    }
  }

  /**
   * @return void
   */
  public static function _seravo_show_email_warning() {
    ?><div class="notice notice-error">
      <p>
        <?php
        $siteurl = get_option('siteurl');
        // translators: $s user's website url
        $link = sprintf(wp_kses(__('Warning: A generic admin email was detected in the <a href="%s/wp-admin/options-general.php">site settings</a>. Please update it.', 'seravo'), array( 'a' => array( 'href' => array() ) )), esc_url($siteurl));
        echo $link;
        ?>
      </p>
    </div>
    <?php
  }
}
