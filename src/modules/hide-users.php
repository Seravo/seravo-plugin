<?php

namespace Seravo\Module;

/**
 * Class HideUsers
 *
 * Hides prespecified and given users from a WordPress page.
 */
final class HideUsers {
  use Module;

  /**
   * @var string[] Array of users that will be hidden from WP user front-end, wp-cli output and WP Admin panel.
   */
  private static $hidden_user_array = array( 'seravotest', 'seravo' );

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    \add_action('pre_user_query', array( __CLASS__, 'hide_user_from_page' ), 10, 3);
  }

  /**
   * Fires after the WP_User_Query has been parsed, and before the query is executed.
   * @param \WP_User_Query $user_query The user query object.
   * @return void
   */
  public static function hide_user_from_page( $user_query ) {
    if ( \defined('WP_CLI') ) {
      return;
    }

    $users_to_hide = self::$hidden_user_array;
    $current_user_name = \wp_get_current_user()->user_login;

    // Exclude the current user from the users that will be hidden
    $current_user_key = \array_search($current_user_name, $users_to_hide, true);
    if ( $current_user_key !== false ) {
      unset($users_to_hide[$current_user_key]);
    }

    global $wpdb;
    $array_sql_str = \implode("', '", $users_to_hide);

    // Edit SQL query clause just before making a query and skip the given users.
    // Replaces the default "WHERE 1=1" clause with one of the following format:
    // "WHERE 1=1 AND wp_user_table.user_login NOT IN ('user1', 'user2', ... )".
    $user_query->query_where = \str_replace(
      'WHERE 1=1',
      "WHERE 1=1 AND {$wpdb->users}.user_login NOT IN ('{$array_sql_str}')",
      $user_query->query_where
    );
  }

}
