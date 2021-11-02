<?php

namespace Seravo\Module;

use \Seravo\API;
use \Seravo\Helpers;
use \Seravo\GeoIP;

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
    \WP_CLI::add_command('seravo geologin list', array( __CLASS__, 'geologin_list' ));
    \WP_CLI::add_command('seravo geologin allow', array( __CLASS__, 'geologin_allow' ));
    \WP_CLI::add_command('seravo geologin disallow', array( __CLASS__, 'geologin_disallow' ));
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

  /**
   * List the countries from which login is allowed.
   *
   * Check whether log in is restricted to specific countries only
   * and list the countries.
   *
   * Note that on multisites, the first list is the current blog only.
   * You can see the other blogs with WP CLI's `--url` parameter.
   *
   * To manage the lists, see the other `wp seravo geologin` subcommands.
   *
   * ## OPTIONS
   *
   * No options.
   *
   * ## EXAMPLES
   *
   *     wp seravo geologin list
   *
   * @param string[] $args       Arguments for the command.
   * @param string[] $assoc_args Associated arguments for the command.
   * @return void
   */
  public function geologin_list( $args, $assoc_args ) {
    $blog = Helpers::get_blog_name();

    // Get the countries allowed to login from
    $allow_countries = get_option('seravo-allow-login-countries', array());
    $allow_countries_network = array();
    if ( is_multisite() ) {
      $allow_countries_network = get_site_option('seravo-allow-login-countries', array());
    }

    if ( ! GeoIP::is_geologin_enabled() ) {
      \WP_CLI::log('Restricted login: ' . \WP_CLI::colorize('%RDisabled%n') . "\n");
      \WP_CLI::log('Add some countries to the list of countries from which login is allowed');
      \WP_CLI::log("to begin restricting login. See `wp seravo geologin allow --help` for help.\n");
      return;
    }

    // Geologin is enabled
    \WP_CLI::log('Restricted login: ' . \WP_CLI::colorize('%GEnabled%n') . "\n");

    if ( is_multisite() ) {
      \WP_CLI::log("Allow login on blog '${blog}' from:\n");
      foreach ( get_option('seravo-allow-login-countries', array()) as $country_code ) {
        $country = GeoIP::country_code_to_name($country_code);
        \WP_CLI::log("    - ${country} (${country_code})");
      }
      \WP_CLI::log("\nAllow login network-wide (all blogs) from:\n");
      foreach ( get_site_option('seravo-allow-login-countries', array()) as $country_code ) {
        $country = GeoIP::country_code_to_name($country_code);
        \WP_CLI::log("    - ${country} (${country_code})");
      }
    } else {
      \WP_CLI::log("Allow login from:\n");
      foreach ( get_option('seravo-allow-login-countries', array()) as $country_code ) {
        $country = GeoIP::country_code_to_name($country_code);
        \WP_CLI::log("    - ${country} (${country_code})");
      }
    }

    \WP_CLI::log('');
  }

  /**
   * Add country to the list of countries from which login is allowed.
   *
   * On a multisite, all blogs have a list of their own. You can allow a country
   * network-wide with the --network option.
   *
   * See the list and geologin status with `wp seravo geologin list`.
   *
   * ## OPTIONS
   *
   * <name>
   * : The two-letter code of the country from which to allow login.
   *
   * [--network]
   * : Add the country to the network-wide list of countries from
   * which login is allowed. Applies to multisites only.
   *
   * ## EXAMPLES
   *
   *   # Allow login from Finland
   *   $ wp seravo geologin allow fi
   *   Success: Login from 'Finland' is now allowed on blog 'Joosuakoskinen'.
   *
   *   # Allow login from Sweden network-wide (all blogs)
   *   $ wp seravo geologin allow se --network
   *   Success: Login from 'Sweden' is now allowed on all blogs.
   *
   * @param string[] $args       Arguments for the command.
   * @param string[] $assoc_args Associated arguments for the command.
   * @return void
   */
  public function geologin_allow( $args, $assoc_args ) {
    $country_code = $args[0];
    $country_name = GeoIP::country_code_to_name($args[0]);
    $blog = Helpers::get_blog_name();

    if ( $country_name === false ) {
      \WP_CLI::error("${country_code}' is not a valid two-letter country code.");
    }

    // Check if the country should be added network-wide
    $network = isset($assoc_args['network']) && $assoc_args['network'] == true && is_multisite();

    if ( GeoIP::allow_geologin($country_code, $network) ) {
      // Added to the list
      if ( $network ) {
        \WP_CLI::success("Login from '${country_name}' is now allowed on all blogs.");
      } else if ( is_multisite() ) {
        \WP_CLI::success("Login from '${country_name}' is now allowed on blog '${blog}'.");
      } else {
        \WP_CLI::success("Login from '${country_name}' is now allowed.");
      }
    } else {
      // Was already on the list
      if ( $network ) {
        \WP_CLI::success("Login from '${country_name}' was already allowed on all blogs.");
      } else if ( is_multisite() ) {
        \WP_CLI::success("Login from '${country_name}' was already allowed on blog '${blog}'.");
      } else {
        \WP_CLI::success("Login from '${country_name}' was already allowed.");
      }
    }
  }

  /**
   * Remove a country from the list of countries from which login is allowed.
   *
   * This specifically removes a country from the list of allowed countries.
   * A country can't be disallowed unless it's allowed first.
   *
   * On a multisite, all blogs have a list of their own. You can remove a country
   * from the network-wide list with the --network option.
   *
   * See the list and geologin status with `wp seravo geologin list`.
   *
   * ## OPTIONS
   *
   * <name>
   * : The two-letter code of the country from which to disallow login.
   *
   * [--network]
   * : Remove the country from the network-wide list of countries from
   * which login is allowed. Applies to multisites only.
   *
   * ## EXAMPLES
   *
   *   # Remove Finland from the list of countries from which login is allowed
   *   $ wp seravo geologin disallow fi
   *   Success: 'Finland' is no longer on the list of allowed countries for blog 'Joosuakoskinen'.
   *
   * @param string[] $args       Arguments for the command.
   * @param string[] $assoc_args Associated arguments for the command.
   * @return void
   */
  public function geologin_disallow( $args, $assoc_args ) {
    $country_code = $args[0];
    $country_name = GeoIP::country_code_to_name($args[0]);
    $blog = Helpers::get_blog_name();

    if ( $country_name === false ) {
      \WP_CLI::error("'${country_code}' is not a valid two-letter country code.");
    }

    // Check if the country should be added network-wide
    $network = isset($assoc_args['network']) && $assoc_args['network'] == true && is_multisite();

    // Try to remove the country from the list
    $removed = GeoIP::disallow_geologin($country_code, $network);

    if ( ! $removed ) {
      // The country wasn't removed from the list
      if ( $network ) {
        \WP_CLI::warning("Login from '${country_name}' can't be disallowed network-wide.");
        \WP_CLI::log("         It isn't on the network-wide list of countries from which login is allowed.");
      } else if ( is_multisite() ) {
        \WP_CLI::warning("Login from '${country_name}' can't be disallowed on blog '${blog}'.");
        \WP_CLI::log("         It isn't on the blog's list of countries from which login is allowed.");
      } else {
        \WP_CLI::warning("Login from '${country_name}' can't be disallowed.");
        \WP_CLI::log("         It isn't on the list of countries from which login is allowed.");
      }
    }

    if ( $removed ) {
      // The country was removed from the list
      if ( $network ) {
        \WP_CLI::success("'${country_name}' is no longer on the network-wide list of allowed countries.");
      } else {
        if ( is_multisite() && GeoIP::is_login_allowed($country_code) ) {
          \WP_CLI::warning("'${country_name}' is no longer on the list of allowed countries for blog '${blog}'");
          \WP_CLI::log("         but it's still on the network-wide list so login from there is still allowed.");
        } else {
          if ( is_multisite() ) {
            \WP_CLI::success("'${country_name}' is no longer on the list of allowed countries for blog '${blog}'.");
          } else {
            \WP_CLI::success("'${country_name}' is no longer on the list of allowed countries.");
          }
        }
      }
    }

    if ( ! GeoIP::is_geologin_enabled() ) {
      if ( is_multisite() ) {
        \WP_CLI::warning("The list of allowed countries on blog '${blog}' is empty.");
      } else {
        \WP_CLI::warning('The list of allowed countries is empty.');
      }
      \WP_CLI::log('         Geologin is now disabled and login is allowed from anywhere.');
    }
  }

}

