<?php

namespace Seravo\Module;

/**
 * Class SeravoTestAuthByPass
 *
 * Allow automated login for user 'seravotest'
 * Description: If normal login is prevented (e.g. captcha, external LDAP API etc)
 * then this module can be used by the 'seravotest' user to log in to a site anyway.
 */
final class SeravoTestAuthBypass {
  use Module;

  /**
   * Check whether the module should be loaded or not.
   * @return bool Whether to load.
   */
  protected function should_load() {
    // Only load the module when it's needed
    return isset($_GET['seravotest-auth-bypass']);
  }

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    // Check for permission to enter only if flag is set
    \add_action('login_init', array( __CLASS__, 'attempt_login' ), 10, 2);
  }

  /**
   * Check if the authentication bypass key is valid and
   * set the authentication cookies.
   * @return void
   */
  public static function attempt_login() {
    // If special authentication bypass key is found, check if a matching
    // key is found, and if so, automatically login user and redirect to wp-admin.
    $key = \get_transient('seravotest-auth-bypass-key');

    if ( $key !== $_GET['seravotest-auth-bypass'] ) {
      self::error_log('Failed "seravotest" user authentication bypass attempt from IP "' . $_SERVER['REMOTE_ADDR'] . '"');
      return;
    }

    // Remove bypass key so it cannot be used again
    \delete_transient('seravotest-auth-bypass-key');

    $user = \get_user_by('login', 'seravotest');

    if ( $user !== false ) {
      \wp_clear_auth_cookie();
      \wp_set_current_user($user->ID);
      \wp_set_auth_cookie($user->ID);
      $redirect_to = \user_admin_url();
      \wp_safe_redirect($redirect_to);
    }
  }

}
