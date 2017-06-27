<?php
/**
 * Implements example command.
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
    function updates( $args, $assoc_args ) {

      require_once(dirname( __FILE__ ) . '/../modules/updates.php');
      $site_info = Seravo\Updates::seravo_admin_get_site_info();

      if ( $site_info['seravo_updates'] === true ) {
        WP_CLI::success( 'Seravo updates: enabled' );
      } elseif ( $site_info['seravo_updates'] === true ) {
        WP_CLI::success('Seravo updates: disbled' );
      } else {
        WP_CLI::error( 'Seravo API failed to return information about updates.' );
      }

    }
}

WP_CLI::add_command( 'seravo', 'Seravo_WP_CLI' );
