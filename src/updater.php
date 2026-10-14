<?php
/**
 * Self-hosted update checker for the Seravo plugin.
 *
 * Reads the JSON manifest published to GitHub Pages by
 * .github/workflows/publish-update-manifest.yml and feeds it into
 * WordPress core's native self-hosted update mechanism, which is based
 * on the "Update URI" plugin header (added in WP 5.8).
 *
 * Core reference (exact filter contract, read from wp_update_plugins()):
 * https://developer.wordpress.org/reference/functions/wp_update_plugins/
 * Dev note introducing the header and hook:
 * https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/
 *
 * @package Seravo
 */

namespace Seravo;

class Updater {

	// Hostname of the "Update URI" header set in seravo-plugin.php.
	// WordPress builds the filter name from this value, so it must match
	// exactly (see load() below and the header you add to the main file).
	const UPDATE_HOSTNAME = 'seravo.github.io';

	// Where the release workflow publishes the update manifest.
	const MANIFEST_URL = 'https://seravo.github.io/seravo-plugin/update.json';

	const SLUG = 'seravo-plugin';

	// Set by load() to the actual plugin_basename(__FILE__) of the main
	// plugin file, which is what WordPress passes as $plugin_file below.
	// Not hardcoded, since that depends on the install folder name.
	private static $plugin_basename;

	/**
	 * Registers the two filters WordPress core calls for a plugin that
	 * declares a non-WordPress.org "Update URI".
	 *
	 * @param string $plugin_basename Result of plugin_basename(__FILE__) from the main plugin file.
	 */
	public static function load( $plugin_basename ) {
		self::$plugin_basename = $plugin_basename;
		add_filter( 'update_plugins_' . self::UPDATE_HOSTNAME, array( __CLASS__, 'check_for_update' ), 10, 4 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
	}

	/**
	 * Answers the `update_plugins_{$hostname}` filter fired from
	 * wp_update_plugins(). Core casts our return value to an object and
	 * requires at least a 'version' key for it to count as an update.
	 *
	 * @param array|false $update      Default false.
	 * @param array       $plugin_data Headers of the plugin being checked.
	 * @param string      $plugin_file Basename of the plugin being checked.
	 * @param string[]    $locales     Unused: we don't serve translation updates this way.
	 * @return array|false
	 */
	public static function check_for_update( $update, $plugin_data, $plugin_file, $locales ) {
		// This filter fires once per plugin that shares the hostname, so
		// ignore any plugin that isn't us.
		if ( self::$plugin_basename !== $plugin_file ) {
			return $update;
		}

		$manifest = self::get_manifest();
		if ( ! $manifest || empty( $manifest['version'] ) ) {
			return $update;
		}

		return array(
			'slug'         => $manifest['slug'] ?? self::SLUG,
			'version'      => $manifest['version'],
			'url'          => $manifest['url'] ?? 'https://github.com/Seravo/seravo-plugin',
			'package'      => $manifest['package'] ?? '',
			'tested'       => $manifest['tested'] ?? '',
			'requires_php' => $manifest['requires_php'] ?? '',
		);
	}

	/**
	 * Answers the `plugins_api` filter so the "View version x.y.z details"
	 * popup on the Plugins page works for a plugin that isn't hosted on
	 * WordPress.org. Optional: update checks above work without this, but
	 * clicking "View details" would otherwise show a generic error.
	 *
	 * @param false|object|array $result Default result from other handlers.
	 * @param string             $action Requested plugins_api action.
	 * @param object             $args   Request args; ->slug identifies the plugin.
	 * @return false|object
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}

		$manifest = self::get_manifest();
		if ( ! $manifest ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Seravo',
			'slug'          => self::SLUG,
			'version'       => $manifest['version'] ?? '',
			'author'        => '<a href="https://seravo.com/">Seravo</a>',
			'homepage'      => $manifest['url'] ?? 'https://github.com/Seravo/seravo-plugin',
			'requires'      => $manifest['requires'] ?? '',
			'tested'        => $manifest['tested'] ?? '',
			'requires_php'  => $manifest['requires_php'] ?? '',
			'download_link' => $manifest['package'] ?? '',
			'last_updated'  => $manifest['last_updated'] ?? '',
			'sections'      => array(
				'changelog' => $manifest['sections']['changelog'] ?? '',
			),
		);
	}

	/**
	 * Fetches and decodes the update manifest. No caching here: core
	 * already rate-limits calls into this filter via its own
	 * "update_plugins" site transient (every 12 hours, or sooner on
	 * admin screens where it matters), so a second cache layer would
	 * just duplicate that for no benefit.
	 *
	 * @return array|null Decoded JSON as an associative array, or null on any failure.
	 */
	private static function get_manifest() {
		$response = wp_remote_get( self::MANIFEST_URL, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$manifest = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $manifest ) ? $manifest : null;
	}

}
