<?php

namespace Seravo;

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
   */
  public function updates( $args, $assoc_args ) {

    require_once dirname(__FILE__) . '/../modules/upkeep.php';
    $site_info = Upkeep::seravo_admin_get_site_info();

    if ( $site_info['seravo_updates'] === true ) {
      \WP_CLI::success('Seravo Updates: enabled');
    } elseif ( $site_info['seravo_updates'] === false ) {
      \WP_CLI::success('Seravo Updates: disabled');
    } else {
      \WP_CLI::error('Seravo API failed to return information about updates.');
    }

  }
}

/**
 * Extends wp-cli search-replace feature.
 *
 * Will be called after running regular search-replace,
 * even if --skip-plugins is specified.
 */
function search_replace_extension() {
  // Positionals <old> <new> [<table>...]>
  $positionals = \WP_CLI::get_runner()->__get('arguments');
  $options = \WP_CLI::get_runner()->__get('assoc_args');

  if ( count($positionals) != 3 ) {
    // User gave specific tables to run search-replace on,
    // nothing else should be replaced
    return;
  }

  $from = $positionals[1];
  $to = $positionals[2];

  $dry_run = \WP_CLI\Utils\get_flag_value($options, 'dry-run');
  $regex = \WP_CLI\Utils\get_flag_value($options, 'regex', false);

  if ( $dry_run === true || $regex === true ) {
    // No need to do anything on dry-run and
    // shouldn't mess with regex for now
    return;
  }

  if ( is_multisite() === true ) {
    // This is a multisite, check whether
    // we need to replace DOMAIN_CURRENT_SITE

    if ( ! defined('DOMAIN_CURRENT_SITE') ) {
      return;
    }

    $old = DOMAIN_CURRENT_SITE;
    $new = DOMAIN_CURRENT_SITE;

    if ( $from === $old ) {
      $new = $to;
    } else if ( $from === ('://' . $old) ) {
      $new = ltrim($to, '://');
    }

    if ( $old === $new ) {
      return;
    }

    // Replace DOMAIN_CURRENT_SITE in wp-config.php
    $wp_config = '/data/wordpress/htdocs/wp-config.php';
    $replace_regex = '/define.*DOMAIN_CURRENT_SITE.*;/';
    $replace_with = "define( 'DOMAIN_CURRENT_SITE', '$new' );";

    // Read wp-config.php, make the change, write it back
    $content = file_get_contents($wp_config);
    $new_content = preg_replace($replace_regex, $replace_with, $content);

    if ( $new_content === $content ) {
      return;
    }

    file_put_contents($wp_config, $new_content);

    if ( is_executable('/usr/local/bin/s-git-commit') && file_exists('/data/wordpress/.git') ) {
      // Commit wp-config.php changes
      exec('cd /data/wordpress/ && git add htdocs/wp-config.php && /usr/local/bin/s-git-commit -m "Update DOMAIN_CURRENT_SITE" && cd /data/wordpress/htdocs/wordpress/wp-admin');
    }
  }
}

\WP_CLI::add_command('seravo', 'Seravo\Seravo_WP_CLI');
\WP_CLI::add_hook('after_invoke:search-replace', 'Seravo\search_replace_extension');
