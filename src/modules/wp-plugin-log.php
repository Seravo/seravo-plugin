<?php
namespace Seravo;

/**
 * Class PluginLog
 *
 * Logs plugin and theme activations, deactivations, updates, installations
 * and deletions. Theme deletion is not logged as there is no hook for it.
 */
class PluginLog {

  /**
   * @return void
   */
  public static function load() {
    add_action('activate_plugin', array( __CLASS__, 'on_try_activate_plugin' ), 10, 2);
    add_action('activated_plugin', array( __CLASS__, 'on_activate_plugin' ), 10, 2);
    add_action('deactivate_plugin', array( __CLASS__, 'on_try_deactivate_plugin' ), 10, 2);
    add_action('deactivated_plugin', array( __CLASS__, 'on_deactivate_plugin' ), 10, 2);
    add_action('switch_theme', array( __CLASS__, 'on_switch_theme' ), 10, 1);
    add_action('upgrader_process_complete', array( __CLASS__, 'on_upgrader_process_complete' ), 10, 2);
    add_action('delete_plugin', array( __CLASS__, 'on_delete_plugin' ));
  }

  /**
   * @param \WP_Upgrader $upgrader A WP_Upgrader instance.
   * @param mixed[]      $arr_data Details about the upgrade.
   * @return void
   */
  public static function on_upgrader_process_complete( $upgrader = null, $arr_data = null ) {
    if ( empty($upgrader) || empty($arr_data) ) {
      return;
    }
    if ( $arr_data['type'] !== null && $arr_data['action'] !== null ) {
      $type = $arr_data['type'];
      $action = $arr_data['action'];
    } else {
      return;
    }

    if ( $upgrader instanceof \Theme_Upgrader ) {
      if ( $action === 'install' ) {
        self::write_log('installed theme ' . $upgrader->theme_info());
      } elseif ( $action === 'update' ) {
        self::write_log('updated theme ' . $upgrader->theme_info());
      }
    } elseif ( $upgrader instanceof \Plugin_Upgrader ) {
      if ( $action === 'install' ) {
        self::write_log('installed plugin ' . $upgrader->plugin_info());
      } elseif ( $action === 'update' ) {
        self::write_log('updated plugin ' . $upgrader->plugin_info());
      }
    }
  }

  /**
   * @param string $plugin Path to the plugin file relative to the plugins directory.
   * @return void
   */
  public static function on_delete_plugin( $plugin = '' ) {
    self::write_log('deleted plugin ' . $plugin);
  }

  /**
   * @param string $plugin             Path to the plugin file relative to the plugins directory.
   * @param bool   $network_activation Whether this was for all sites in the network or just the current site.
   * @return void
   */
  public static function on_try_activate_plugin( $plugin, $network_activation ) {
    self::write_log('is trying to activate plugin ' . $plugin);
  }

  /**
   * @param string $plugin             Path to the plugin file relative to the plugins directory.
   * @param bool   $network_activation Whether this was for all sites in the network or just the current site.
   * @return void
   */
  public static function on_activate_plugin( $plugin, $network_activation ) {
    self::write_log('activated plugin ' . $plugin);
  }

  /**
   * @param string $plugin             Path to the plugin file relative to the plugins directory.
   * @param bool   $network_activation Whether this was for all sites in the network or just the current site.
   * @return void
   */
  public static function on_try_deactivate_plugin( $plugin, $network_activation ) {
    self::write_log('is trying to deactivate plugin ' . $plugin);
  }

  /**
   * @param string $plugin             Path to the plugin file relative to the plugins directory.
   * @param bool   $network_activation Whether this was for all sites in the network or just the current site.
   * @return void
   */
  public static function on_deactivate_plugin( $plugin, $network_activation ) {
    self::write_log('deactivated plugin ' . $plugin);
  }

  /**
   * @param string $theme Name of the new theme.
   * @return void
   */
  public static function on_switch_theme( $theme ) {
    self::write_log('switched to theme ' . $theme);
  }

  /**
   * @param string $message Message to be written in to log.
   * @return void
   */
  public static function write_log( $message ) {
    $time_local = gmdate('j/M/Y:H:i:s O');

    $log_fp = fopen('/data/log/wp-settings.log', 'a');
    if ( $log_fp === false ) {
      // Couldn't open the file, can't do much
      return;
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    // eg ID is 0 when the change is made using WP CLI
    if ( $user_id === 0 ) {
      fwrite($log_fp, "{$time_local} WP-CLI {$message}\n");
    } else {
      $username = $current_user->user_login;
      fwrite($log_fp, "{$time_local} User {$username} {$message}\n");
    }
    fclose($log_fp);
  }
}
