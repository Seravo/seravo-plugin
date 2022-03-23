<?php

namespace Seravo\Module;

use \Seravo\API;
use \Seravo\Helpers;

/**
 * Class SeravoCLI
 *
 * A class for Seravo.com specific WP-CLI actions.
 */
final class SeravoCLI extends \WP_CLI_Command {
  use Module;

  /**
   * Check whether the module should be loaded or not.
   * @return bool Whether to load.
   */
  protected function should_load() {
    return Helpers::is_production();
  }

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    \WP_CLI::add_command('seravo updates', array( __CLASS__, 'updates' ));
  }

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
    $site_info = API::get_site_data();
    if ( \is_wp_error($site_info) ) {
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
