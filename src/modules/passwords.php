<?php

namespace Seravo\Module;

use \Seravo\Helpers;
use \Seravo\Compatibility;

/**
 * Class Passwords
 *
 * Enforce a strong passwords. Check user passwords with
 * wp-check-haveibeenpwned every few months.
 */
final class Passwords {
  use Module;

  /**
   * @var string|null
   */
  private static $password_hash;

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    \add_action('login_enqueue_scripts', array( __CLASS__, 'register_scripts' ));
    \add_action('admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ));
    \add_action('profile_update', array( __CLASS__, 'clear_seravo_pwned_check_timestamp' ));

    \add_filter('wp_authenticate_user', array( __CLASS__, 'calculate_password_hash' ), 10, 3);
    // Use 15 as priority so wp-login.log (priority 10) is filled first
    \add_filter('login_redirect', array( __CLASS__, 'search_password_database' ), 15, 3);
  }

  /**
   * Load required scripts and styles for this module.
   * @param string $page The page user is on.
   * @return void
   */
  public static function register_scripts( $page ) {
    \wp_register_style('seravo-passwords-css', SERAVO_PLUGIN_URL . 'style/passwords.css', array(), Helpers::seravo_plugin_version());
    \wp_register_script('seravo-passwords-js', SERAVO_PLUGIN_URL . 'js/passwords.js', array( 'jquery' ), Helpers::seravo_plugin_version(), true);

    if ( $page === 'profile.php' || $page === 'user-new.php' ) {
      \wp_enqueue_style('seravo-passwords-css');
    } elseif ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
      \wp_enqueue_script('seravo-passwords-js');
      // Password changing form
      if ( isset($_GET['action']) && $_GET['action'] === 'rp' ) {
        \wp_enqueue_style('seravo-passwords-css');
      }
    }
  }

  /**
   * Clear seravo_pwned_check form user meta
   *
   * The user meta seravo_pwned_check contains a timestamp to prevent the
   * check from running too frequently. When the user changes their password,
   * this field needs to be reset so that the check is guaranteed to run on
   * the next login.
   *
   * @param int $user_id ID of the user with changed meta.
   * @return void
   */
  public static function clear_seravo_pwned_check_timestamp( $user_id ) {
    if ( isset($_POST['pass1']) && $_POST['pass1'] !== '' ) {
      \delete_user_meta($user_id, 'seravo_pwned_check');
    }
  }

  /**
   * Calculate sha1 hash of the user password.
   * @param \WP_User|\WP_Error $user     WP_User or WP_Error object if a previous callback failed authentication.
   * @param string             $password Password used to login.
   * @return \WP_Error|\WP_User The object given as $user.
   */
  public static function calculate_password_hash( $user, $password ) {
    self::$password_hash = \sha1($password);
    return $user;
  }

  /**
   * Check haveibeenpwned database for the user password hash.
   * Only ran every 3 months on login.
   * @param string             $redirect_to           The redirect destination URL.
   * @param string             $requested_redirect_to The requested redirect destination URL passed as a parameter.
   * @param \WP_User|\WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
   * @return string The new $redirect_to.
   */
  public static function search_password_database( $redirect_to, $requested_redirect_to, $user ) {
    // Check if user has capability 'publish_pages'. By default user roles
    // editor, admin and superadmin are the only ones that have it.
    // Use user_can() for check as at this stage of the login the
    // current_user_can() would not return anything yet.
    // See: https://wordpress.org/support/article/roles-and-capabilities/#capability-vs-role-table
    if ( \is_wp_error($user) || self::$password_hash === null || ! \user_can($user->ID, 'publish_pages') ) {
      return $redirect_to;
    }

    // Make the check every 3 months
    $time_now = \time();
    $pwned_meta = \get_user_meta($user->ID, 'seravo_pwned_check', true);
    if ( $pwned_meta === '' || $time_now - ((int) $pwned_meta) > 90 * DAY_IN_SECONDS ) {
      // Check if the password has been pwned
      $exec = Compatibility::exec('wp-check-haveibeenpwned --json ' . self::$password_hash . ' 2>&1', $pwned_check);
      $result = \json_decode(isset($pwned_check[0]) ? $pwned_check[0] : '', true);
      if ( $exec === false || isset($result['error']) || ! isset($result['found']) ) {
        // Something went wrong
        \error_log("Failed to run 'wp-check-haveibeenpwned'!");
        return $redirect_to;
      }

      if ( $result['found'] === false ) {
        // Password not pwned
        \update_user_meta($user->ID, 'seravo_pwned_check', $time_now);
        return $redirect_to;
      }

      // Password has been pwned!
      self::show_pwned_alert($redirect_to, self::$password_hash);
      exit();
    }

    return $redirect_to;
  }

  /**
   * Show a alert page for pwned password.
   * @param string $redirect_to The URL user was really about to be redirected.
   * @param string $hash        The user password hash.
   * @return void
   */
  public static function show_pwned_alert( $redirect_to, $hash ) {
    ?>
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" <?php \language_attributes(); ?>>

    <head>
      <meta name="viewport" content="width=device-width" />
      <meta http-equiv="Content-Type" content="<?php \bloginfo('html_type'); ?>; charset=<?php echo \get_option('blog_charset'); ?>" />
      <meta name="robots" content="noindex,nofollow" />
      <title><?php _e('Password change required', 'seravo'); ?></title>
      <?php
      \wp_admin_css('install', true);
      \wp_admin_css('ie', true);
      ?>
    </head>

    <body class="wp-core-ui">
      <p id="logo"><a href="#"><?php _e('WordPress'); ?></a></p>
      <h1><?php _e('Password change required', 'seravo'); ?></h1>
      <p><?php _e('The hash of your password matches a password in the <a href="https://haveibeenpwned.com" target="_blank">haveibeenpwned.com</a> database. This means your password was used on a website or service that suffered a breach and the password was leaked. Thus your WordPress account and the accounts on other services using the same password are highly vulnerable for misuse.', 'seravo'); ?></p>
      <p><?php _e('Please <strong>change your password immediately</strong> on the WordPress user profile page.', 'seravo'); ?></p>
      <p><?php _e('Remember to always follow good <strong>password hygiene</strong> and have a unique password for each website and service you use to prevent this in the future.', 'seravo'); ?></p>
      <p class="step">
        <a class="button button-large button-primary" href="<?php echo \get_edit_profile_url(); ?>"><?php _e('Go to your profile page to change the password', 'seravo'); ?></a>
        <a class="button button-large" href="<?php echo $redirect_to; ?>"><?php _e('Continue normal login', 'seravo'); ?></a>
      </p>
    </body>

    </html>
    <?php
  }

}
