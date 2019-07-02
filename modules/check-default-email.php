<?php
/**
 * Plugin name: Seravo Check Default Email
 * Description: Checks that the WordPress admin email is not the default no-reply@seravo
 * if so, show a notification suggesting to change it to something better.
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('CheckDefaultEmail') ) {
  class CheckDefaultEmail {

    public static function load() {
      add_action('admin_notices', array( __CLASS__, '_seravo_check_default_email' ));
    }

    public static function _seravo_check_default_email() {
      // Get the siteurl and home url and check if https is enabled, if not, show warning
      $email = get_option('admin_email');
      if ( $email === 'no-reply@seravo.fi' || $email === 'no-reply@seravo.com' ) {
        self::_seravo_show_email_warning();
      }
    }

    public static function _seravo_show_email_warning() {
      ?><div class="notice notice-error"><p>
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
  CheckDefaultEmail::load();
}
