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
      add_action('profile_update', array( __CLASS__, 'clear_seravo_pwned_check_timestamp' ));

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

      wp_register_style(
        'seravo_passwords',
        plugin_dir_url(__DIR__) . 'style/passwords.css',
        '',
        Helpers::seravo_plugin_version()
      );
      wp_register_script(
        'seravo_passwords',
        plugin_dir_url(__DIR__) . 'js/passwords.js',
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

    /**
     * Clear seravo_pwned_check form user meta
     *
     * The user meta seravo_pwned_check contains a timestamp to prevent the
     * check from running too frequently. When the user changes their password,
     * this field needs to be reset so that the check is guaranteed to run on
     * the next login.
     */
    public static function clear_seravo_pwned_check_timestamp( $user_id ) {
      if ( isset($_POST['pass1']) && ! empty($_POST['pass1']) ) {
        delete_user_meta($user_id, 'seravo_pwned_check');
      }
    }

    public static function calculate_password_hash( $user, $password ) {
      self::$password_hash = sha1($password);
      return $user;
    }

    public static function search_password_database( $redirect_to, $requested_redirect_to, $user ) {
      // Check if user has capability 'publish_pages'. By default user roles
      // editor, admin and superadmin are the only ones that have it.
      // Use user_can() for check as at this stage of the login the
      // current_user_can() would not return anything yet.
      // See: https://wordpress.org/support/article/roles-and-capabilities/#capability-vs-role-table
      if ( is_wp_error($user) || self::$password_hash === null || ! user_can($user->ID, 'publish_pages') ) {
        return $redirect_to;
      }

      // Make the check every 3 months
      $time_now = time();
      $pwned_meta = get_user_meta($user->ID, 'seravo_pwned_check', true);
      if ( empty($pwned_meta) || $time_now - (int) $pwned_meta > 90 * DAY_IN_SECONDS ) {
        // Check if the password has been pwned
        exec('wp-check-haveibeenpwned --json ' . self::$password_hash . ' 2>&1', $pwned_check);
        $result = \json_decode(isset($pwned_check[0]) ? $pwned_check[0] : '', true);

        if ( count($pwned_check) === 0 || isset($result['error']) || ! isset($result['found']) ) {
          // Something went wrong
          error_log("Failed to run 'wp-check-haveibeenpwned'!");
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
          <title><?php _e('Password change required', 'seravo'); ?></title>
          <?php
            wp_admin_css('install', true);
            wp_admin_css('ie', true);
          ?>
        </head>
        <body class="wp-core-ui">
          <p id="logo"><a href="#"><?php _e('WordPress'); ?></a></p>
          <h1><?php _e('Password change required', 'seravo'); ?></h1>
          <p><?php _e('The hash of your password matches a password in the <a href="https://haveibeenpwned.com" target="_blank">haveibeenpwned.com</a> database. This means your password was used on a website or service that suffered a breach and the password was leaked. Thus your WordPress account and the accounts on other services using the same password are highly vulnerable for misuse.', 'seravo'); ?></p>
          <p><?php _e('Please <strong>change your password immediately</strong> on the WordPress user profile page.', 'seravo'); ?></p>
          <p><?php _e('Remember to always follow good <strong>password hygiene</strong> and have a unique password for each website and service you use to prevent this in the future.', 'seravo'); ?></p>
          <p class="step">
            <a class="button button-large button-primary" href="<?php echo get_edit_profile_url(); ?>"><?php _e('Go to your profile page to change the password', 'seravo'); ?></a>
            <a class="button button-large" href="<?php echo $redirect_to; ?>"><?php _e('Continue normal login', 'seravo'); ?></a>
          </p>
        </body>
      </html>
      <?php
    }
  }

  Passwords::load();
}
