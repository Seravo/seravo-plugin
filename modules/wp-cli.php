<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

/**
 * Implements Seravo.com specific actions
 */
class Seravo_WP_CLI extends WP_CLI_Command {

  /**
   * Seravo wp-cli functions.
   *
   * ## OPTIONS
   *
   * No options.
   *
   * ## EXAMPLES
   *
   *     wp seravo updates
   *
   */
  public function updates( $args, $assoc_args ) {

    require_once dirname(__FILE__) . '/../modules/upkeep.php';
    $site_info = Seravo\Upkeep::seravo_admin_get_site_info();

    if ( $site_info['seravo_updates'] === true ) {
      WP_CLI::success('Seravo Updates: enabled');
    } elseif ( $site_info['seravo_updates'] === false ) {
      WP_CLI::success('Seravo Updates: disabled');
    } else {
      WP_CLI::error('Seravo API failed to return information about updates.');
    }

  }
}

WP_CLI::add_command('seravo', 'Seravo_WP_CLI');
