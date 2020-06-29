<?php
/**
 * Plugin name: WP Plugin Log
 * Description: Logs plugin and theme activations, deactivations, updates, installations
 * and deletions. Theme deletion is not logged as there is no hook for it.
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('UserLog') ) {
  class UserLog {

    public static function load() {
      add_action('edit_user_created_user', array( __CLASS__, 'on_edit_user_created_user' ), 10, 1);
      add_action('register_new_user', array( __CLASS__, 'on_register_new_user' ), 10, 1);
      add_action('delete_user', array( __CLASS__, 'on_delete_user' ), 10, 2);
      add_action('deleted_user', array( __CLASS__, 'on_deleted_user' ), 10, 2);
      add_action('after_password_reset', array( __CLASS__, 'on_after_password_reset' ), 10, 2);
      add_action('profile_update', array( __CLASS__, 'on_profile_update' ), 10, 2);
      add_action('set_user_role', array( __CLASS__, 'on_set_user_role' ), 10, 3);
    }

    // Existing user creates a new user
    public static function on_edit_user_created_user( $user_id ) {
      if ( empty($user_id) || ! is_numeric($user_id) ) {
        return;
      }

      $user = get_userdata($user_id);

      self::write_log_user('created user ' . $user->user_login . ' (ID:' . $user_id . ')');
    }

    // A new user registration
    public static function on_register_new_user( $user_id ) {
      if ( empty($user_id || ! is_numeric($user_id)) ) {
        return;
      }

      self::write_log('User (ID:' . $user_id . ') registered');
    }

    // User attempts to delete another user
    public static function on_delete_user( $user_id, $redirect_user_id = null ) {
      if ( empty($user_id || ! is_numeric($user_id)) ) {
        return;
      }

      $user = get_userdata($user_id);
      // Check if the user content is reassinged to another user
      if ( $redirect_user_id === null ) {
        self::write_log_user('attempts to delete user ' . $user->user_login . ' (ID:' . $user_id . ')');
      } else {
        $redirect_user = get_userdata($redirect_user_id);
        self::write_log_user('attempts to delete user ' . $user->user_login . ' (ID:' . $user_id . ') and reassign content to user ' . $redirect_user->user_login . ' (ID:' . $redirect_user_id . ')');
      }
    }

    // User deletes another user
    public static function on_deleted_user( $user_id, $redirect_user_id = null ) {
      if ( empty($user_id || ! is_numeric($user_id)) ) {
        return;
      }
      self::write_log_user('deleted user (ID:' . $user_id . ')');
    }

    // User succesfully resets password
    public static function on_after_password_reset( $user ) {
      if ( empty($user) ) {
        return;
      }
      self::write_log('User ' . $user->user_login . ' (ID:' . $user->ID . ') resetted password');
    }

    // User changes user settings
    public static function on_profile_update( $user_id, $old_user_data ) {
      if ( empty($user_id) || ! is_numeric($user_id) || ! $old_user_data ) {
        return;
      }

      $new_user_data = get_userdata($user_id);

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

    // User's role is changed
    public static function on_set_user_role( $user_id, $role, $old_roles ) {
      if ( empty($user_id) || ! is_numeric($user_id) ) {
        return;
      }

      // If ID is 0, there is no current user. eg role set on registration or wp-test
      if ( wp_get_current_user()->ID === 0 ) {
        self::write_log('User (ID:' . $user_id . ') role changed to ' . $role);
      } else {
        self::write_log_user('changed role of user (ID:' . $user_id . ') to ' . $role);
      }
    }

    // Add current user info to the log entry
    public static function write_log_user( $message ) {
      $current_user = wp_get_current_user();
      $user_id = $current_user->ID;
      // eg ID is 0 when the change is made using WP CLI
      if ( $user_id === 0 ) {
        $username = 'ID_0';
      } else {
        $username = $current_user->user_login;
      }
      $message = 'User ' . $username . ' (ID:' . $user_id . ') ' . $message;
      self::write_log($message);
    }

    public static function write_log( $message ) {
      $time_local = date('j/M/Y:H:i:s O');

      $log_fp = fopen($log_directory . '/data/log/wp-user.log', 'a');
      fwrite($log_fp, "$time_local $message\n");
      fclose($log_fp);
    }
  }

  UserLog::load();
}
