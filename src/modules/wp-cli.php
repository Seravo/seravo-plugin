<?php

namespace Seravo;

use \Seravo\Page\Upkeep;  // TODO: Not good, get rid of

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

/**
 * Implements Seravo.com specific actions
 */
class Seravo_WP_CLI extends \WP_CLI_Command {

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
   * @param string[] $args       Arguments for the command.
   * @param string[] $assoc_args Associated arguments for the command.
   * @return void
   */
  public function updates( $args, $assoc_args ) {
    $site_info = Upkeep::seravo_admin_get_site_info();
    if ( is_wp_error($site_info) ) {
      \WP_CLI::error('Seravo API failed to return information about updates.');
      return;
    }

    if ( $site_info['seravo_updates'] === true ) {
      \WP_CLI::success('Seravo Updates: enabled');
    } elseif ( $site_info['seravo_updates'] === false ) {
      \WP_CLI::success('Seravo Updates: disabled');
    } else {
      \WP_CLI::error('Seravo API failed to return information about updates.');
    }
  }

}

\WP_CLI::add_command('seravo updates', array( Seravo_WP_CLI::class, 'updates' ));
