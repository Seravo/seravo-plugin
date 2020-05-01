<?php
/*
 * Plugin name: Passwords
 * Description: Enforce strong passwords
 * Version: 1.1
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Passwords') ) {
  class Passwords {

    private static $password_hash;

    /**
     * Load passwords features
     */
    public static function load() {

      add_action('login_enqueue_scripts', array( __CLASS__, 'register_scripts' ));
      add_action('admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ));

      add_filter('wp_authenticate_user', array( __CLASS__, 'calculate_password_hash' ), 10, 3);
      // Use 15 as priority so wp-login.log (priority 10) is filled first
      add_filter('login_redirect', array( __CLASS__, 'search_password_database' ), 15, 3);

    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function register_scripts( $page ) {

      wp_register_style('seravo_passwords', plugin_dir_url(__DIR__) . '/style/passwords.css', '', Helpers::seravo_plugin_version());
      wp_register_script(
        'seravo_passwords',
        plugin_dir_url(__DIR__) . '/js/passwords.js',
        array( 'jquery' ),
        Helpers::seravo_plugin_version(), // version string
        true // in footer
      );

      if ( $page === 'profile.php' || $page === 'user-new.php' ) {
        wp_enqueue_style('seravo_passwords');
      } elseif ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
        wp_enqueue_script('seravo_passwords');
        // Password changing form
        if ( isset($_GET['action']) && $_GET['action'] === 'rp' ) {
          wp_enqueue_style('seravo_passwords');
        }
      }

    }

    public static function calculate_password_hash( $user, $password ) {
      self::$password_hash = sha1($password);
      return $user;
    }

    public static function search_password_database( $redirect_to, $requested_redirect_to, $user ) {
      if ( is_wp_error($user) || self::$password_hash === null || ! in_array('administrator', $user->roles, true) ) {
        return $redirect_to;
      }
      // Make the check every 3 months
      $time_now = current_time('timestamp', true);
      $pwned_meta = get_user_meta($user->ID, 'seravo_pwned_check', true);
      if ( empty($pwned_meta) || $time_now - (int) $pwned_meta > 90 * DAY_IN_SECONDS ) {
        // Check if the password has been pwned
        exec('wp-check-haveibeenpwned --json ' . self::$password_hash . ' 2>&1', $pwned_check);
        $result = \json_decode(isset($pwned_check[0]) ? $pwned_check[0] : '', true);

        if ( count($pwned_check) === 0 || isset($result['error']) || ! isset($result['found']) ) {
          // Something went wrong
          error_log("Seravo Plugin couldn't run 'wp-check-haveibeenpwned'!");
          return $redirect_to;
        } elseif ( $result['found'] === false ) {
          // Password not pwned
          update_user_meta($user->ID, 'seravo_pwned_check', $time_now);
          return $redirect_to;
        } else {
          // Password has been pwned!
          self::show_pwned_alert($redirect_to, self::$password_hash);
          exit();
        }
      }
      return $redirect_to;
    }

    public static function show_pwned_alert( $redirect_to, $hash ) {
    ?>
      <!DOCTYPE html>
      <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
        <head>
          <meta name="viewport" content="width=device-width" />
          <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
          <meta name="robots" content="noindex,nofollow" />
          <title><?php _e('Security &rsaquo; Pwned Password', 'seravo'); ?></title>
          <?php
            wp_admin_css('install', true);
            wp_admin_css('ie', true);
          ?>
        </head>
        <body class="wp-core-ui">
          <p id="logo"><a href="#"><?php _e('WordPress'); ?></a></p>
          <h1><?php _e('Password change required', 'seravo'); ?></h1>
          <p><?php _e('Automatic password security verification has found that your password has been pwned. Please follow the instructions below!', 'seravo'); ?></p>
          <h3><?php _e('What does this mean?', 'seravo'); ?></h3>
          <?php /* translators: %s: Hash of the users passwor1d. */ ?>
          <p><?php printf(__('The hash of your password (%s) was found in the <a href="https://haveibeenpwned.com" target="_blank">haveibeenpwned.com</a> database. Getting your password pwned means your password has been a part of at least one data leak on some 3rd party service. That makes your WordPress account and the accounts on other services using the same password more vulnerable to possible hijackers.', 'seravo'), $hash); ?></p>
          <h3><?php _e('What actions to take?', 'seravo'); ?></h3>
          <p><?php _e('You should <b>change your password immediadly</b>. You can do that with the button below or by following <a href="https://help.seravo.com/article/29-managing-user-passwords-in-wordpress">these instructions</a>. If you have any issues related to chaging the password, please be in contact with Seravo support at <i>help@seravo.com</i>.', 'seravo'); ?></p>
          <h3><?php _e('How to prevent this in the future?', 'seravo'); ?></h3>
          <p><?php _e('You should always follow good <a href="https://seravo.fi/2014/password-hygiene-every-mans-responsibility">password hygiene</a>. Especially by using different passwords on different services. This website already prevents changing your password to a weak one but you can scan for dangerously weak passwords from all users by running <code>wp-check-passwords</code>.', 'seravo'); ?></p>
          <p class="step">
            <a class="button button-large button-primary" href="<?php echo wp_login_url(); ?>?action=lostpassword"><?php _e('Change Password', 'seravo'); ?></a>
            <a class="button button-large" href="<?php echo $redirect_to; ?>"><?php _e('Continue', 'seravo'); ?></a>
          </p>
        </body>
      </html>
      <?php
    }
  }

  Passwords::load();
}
