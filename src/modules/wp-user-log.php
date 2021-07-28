<?php
namespace Seravo;

/**
 * Class UserLog
 *
 * Logs plugin and theme activations, deactivations, updates, installations
 * and deletions. Theme deletion is not logged as there is no hook for it.
 */
class UserLog {

  /**
   * @return void
   */
  public static function load() {
    add_action('edit_user_created_user', array( __CLASS__, 'on_edit_user_created_user' ), 10, 1);
    add_action('register_new_user', array( __CLASS__, 'on_register_new_user' ), 10, 1);
    add_action('delete_user', array( __CLASS__, 'on_delete_user' ), 10, 2);
    add_action('deleted_user', array( __CLASS__, 'on_deleted_user' ), 10, 2);
    add_action('after_password_reset', array( __CLASS__, 'on_after_password_reset' ), 10, 2);
    add_action('profile_update', array( __CLASS__, 'on_profile_update' ), 10, 2);
    add_action('set_user_role', array( __CLASS__, 'on_set_user_role' ), 10, 3);
  }

  /**
   * Existing user creates a new user.
   * @param int $user_id ID of the user.
   * @return void
   */
  public static function on_edit_user_created_user( $user_id ) {
    if ( empty($user_id) || ! is_numeric($user_id) ) {
      return;
    }

    $user = get_userdata($user_id);
    if ( $user === false ) {
      return;
    }

    self::write_log_user('created user ' . $user->user_login . ' (ID:' . $user_id . ')');
  }

  /**
   * A new user registration.
   * @param int $user_id ID of the user.
   * @return void
   */
  public static function on_register_new_user( $user_id ) {
    if ( empty($user_id || ! is_numeric($user_id)) ) {
      return;
    }

    self::write_log('User (ID:' . $user_id . ') registered');
  }

  /**
   * User attempts to delete another user.
   * @param int      $user_id          ID of the user.
   * @param int|null $redirect_user_id ID of the user to reassign posts and links to.
   * @return void
   */
  public static function on_delete_user( $user_id, $redirect_user_id = null ) {
    if ( empty($user_id || ! is_numeric($user_id)) ) {
      return;
    }

    $user = get_userdata($user_id);
    if ( $user === false ) {
      return;
    }

    // Check if the user content is reassinged to another user
    if ( $redirect_user_id === null ) {
      self::write_log_user('attempts to delete user ' . $user->user_login . ' (ID:' . $user_id . ')');
    } else {
      $redirect_user = get_userdata($redirect_user_id);
      if ( $redirect_user === false ) {
        return;
      }
      self::write_log_user('attempts to delete user ' . $user->user_login . ' (ID:' . $user_id . ') and reassign content to user ' . $redirect_user->user_login . ' (ID:' . $redirect_user_id . ')');
    }
  }

  /**
   * User deletes another user.
   * @param int      $user_id          ID of the user.
   * @param int|null $redirect_user_id ID of the user to reassign posts and links to.
   * @return void
   */
  public static function on_deleted_user( $user_id, $redirect_user_id = null ) {
    if ( empty($user_id || ! is_numeric($user_id)) ) {
      return;
    }
    self::write_log_user('deleted user (ID:' . $user_id . ')');
  }

  /**
   * User succesfully resets password.
   * @param \WP_User $user The user's WP_User object.
   * @return void
   */
  public static function on_after_password_reset( $user ) {
    if ( empty($user) ) {
      return;
    }
    self::write_log('User ' . $user->user_login . ' (ID:' . $user->ID . ') resetted password');
  }

  /**
   * User changes user settings.
   * @param int      $user_id       ID of the user.
   * @param \WP_User $old_user_data Object containing user's data prior to update.
   * @return void
   */
  public static function on_profile_update( $user_id, $old_user_data ) {
    if ( empty($user_id) ) {
      return;
    }

    $new_user_data = get_userdata($user_id);
    if ( $new_user_data === false ) {
      return;
    }

    $username = $new_user_data->user_login;
    $username .= (' (ID:' . $user_id . ')');

    // User changes user password
    if ( isset($_POST['pass1']) && '' !== $_POST['pass1'] ) {
      self::write_log_user('changed password for user ' . $username);
    }

    // User changes user email
    if ( $new_user_data->user_email !== $old_user_data->user_email ) {
      self::write_log_user('changed user ' . $username . ' email from ' . $old_user_data->user_email . ' to ' . $new_user_data->user_email);
    }
  }

  /**
   * User's role is changed.
   * @param int      $user_id   ID of the user.
   * @param string   $role      The new role.
   * @param string[] $old_roles An array of the user's previous roles.
   * @return void
   */
  public static function on_set_user_role( $user_id, $role, $old_roles ) {
    if ( empty($user_id) || ! is_numeric($user_id) ) {
      return;
    }

    // If ID is 0, there is no current user. eg role set on registration or wp-test
    if ( wp_get_current_user()->ID === 0 ) {
      $new_user_data = get_userdata($user_id);
      if ( $new_user_data === false ) {
        return;
      }

      $username = $new_user_data->user_login;

      // Hide seravotest user from the logs
      if ( $username != 'seravotest' ) {
        self::write_log('User ' . $username . ' (ID:' . $user_id . ') role changed to ' . $role);
      }
    } else {
      self::write_log_user('changed role of user (ID:' . $user_id . ') to ' . $role);
    }
  }

  /**
   * Add current user info to the log entry.
   * @param string $message Message to be written in to log.
   * @return void
   */
  public static function write_log_user( $message ) {
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    // eg ID is 0 when the change is made using WP CLI
    $username = $user_id === 0 ? 'ID_0' : $current_user->user_login;
    $message = 'User ' . $username . ' (ID:' . $user_id . ') ' . $message;
    self::write_log($message);
  }

  /**
   * @param string $message Message to be written in to log.
   * @return void
   */
  public static function write_log( $message ) {
    $time_local = date('j/M/Y:H:i:s O');

    $log_fp = fopen('/data/log/wp-user.log', 'a');
    if ( $log_fp === false ) {
      // Couldn't open the file, can't do much
      return;
    }

    fwrite($log_fp, "{$time_local} {$message}\n");
    fclose($log_fp);
  }
}
