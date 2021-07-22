<?php

namespace Seravo\Postbox;

use \Seravo\Helpers;

/**
 * Class Requirements
 *
 * Requirements is used to manage requirements for
 * postboxes and other Seravo Plugin features.
 */
final class Requirements {

  /**
   * @var string Key for 'init_from_array' initilization of is_admin.
   */
  const IS_ADMIN = 'is_admin';
  /**
   * @var string Key for 'init_from_array' initilization of is_wp_cli.
   */
  const IS_WP_CLI = 'is_wp_cli';
  /**
   * @var string Key for 'init_from_array' initilization of is_multisite.
   */
  const IS_MULTISITE = 'is_multisite';
  /**
   * @var string Key for 'init_from_array' initilization of is_not_multisite.
   */
  const IS_NOT_MULTISITE = 'is_not_multisite';
  /**
   * @var string Key for 'init_from_array' initilization of can_be_production.
   */
  const CAN_BE_PRODUCTION = 'can_be_production';
  /**
   * @var string Key for 'init_from_array' initilization of can_be_staging.
   */
  const CAN_BE_STAGING = 'can_be_staging';
  /**
   * @var string Key for 'init_from_array' initilization of can_be_development.
   */
  const CAN_BE_DEVELOPMENT = 'can_be_development';
  /**
   * @var string Key for 'init_from_array' initilization of can_be_*.
   */
  const CAN_BE_ANY_ENV = 'can_be_any_env';
  /**
   * @var string Key for 'init_from_array' initilization of capabilities.
   */
  const CAPABILITIES = 'capabilities';


  /**
   * @var bool Whether user must be admin and able to manager network.
   */
  public $is_admin = \true;
  /**
   * @var bool Whether plugin must be loaded by WP CLI.
   */
  public $is_wp_cli = \false;
  /**
   * @var bool Whether site must be multisite install.
   */
  public $is_multisite = \false;
  /**
   * @var bool Whether site must not be multisite install.
   */
  public $is_not_multisite = \false;
  /**
   * @var bool Whether the site can be in production environment.
   */
  public $can_be_production = \false;
  /**
   * @var bool Whether the site can be in staging environment.
   */
  public $can_be_staging = \false;
  /**
   * @var bool Whether the site can be in local development environment.
   */
  public $can_be_development = \false;

  /**
   * Capabilities array contains capabilities as strings and
   * arrays with capability string at index[0] and extra args at index[1].
   * @var mixed[] Additional WordPress capabilities required.
   * @see https://wordpress.org/support/article/roles-and-capabilities
   */
  public $capabilities = array();

  /**
   * Initialize requirements from array. Array should
   * be in "[Requirements::*] => value" format.
   * @param array<string, mixed> $requirements Requirements to be initialized.
   * @return void
   */
  public function init_from_array( $requirements ) {
    if ( isset($requirements[self::IS_ADMIN]) ) {
      $this->is_admin = $requirements[self::IS_ADMIN];
    }
    if ( isset($requirements[self::IS_WP_CLI]) ) {
      $this->is_wp_cli = $requirements[self::IS_WP_CLI];
    }
    if ( isset($requirements[self::IS_MULTISITE]) ) {
      $this->is_multisite = $requirements[self::IS_MULTISITE];
    }
    if ( isset($requirements[self::IS_NOT_MULTISITE]) ) {
      $this->is_not_multisite = $requirements[self::IS_NOT_MULTISITE];
    }
    if ( isset($requirements[self::CAN_BE_PRODUCTION]) ) {
      $this->can_be_production = $requirements[self::CAN_BE_PRODUCTION];
    }
    if ( isset($requirements[self::CAN_BE_STAGING]) ) {
      $this->can_be_staging = $requirements[self::CAN_BE_STAGING];
    }
    if ( isset($requirements[self::CAN_BE_DEVELOPMENT]) ) {
      $this->can_be_development = $requirements[self::CAN_BE_DEVELOPMENT];
    }
    if ( isset($requirements[self::CAN_BE_ANY_ENV]) ) {
      $this->can_be_production = $requirements[self::CAN_BE_ANY_ENV];
      $this->can_be_staging = $requirements[self::CAN_BE_ANY_ENV];
      $this->can_be_development = $requirements[self::CAN_BE_ANY_ENV];
    }
    if ( isset($requirements[self::CAPABILITIES]) ) {
      $this->capabilities = $requirements[self::CAPABILITIES];
    }
  }

  /**
   * Check if the environment and user
   * permissions match the required ones.
   * @return bool Whether requirements match or not.
   */
  public function is_allowed() {
    if ( $this->is_admin && ! current_user_can('administrator') ) {
      return false;
    }
    if ( $this->is_admin && is_multisite() && ! current_user_can('manage_network') ) {
      return false;
    }
    if ( $this->is_wp_cli && ! (defined('WP_CLI') && WP_CLI) ) {
      return false;
    }
    if ( $this->is_multisite && ! is_multisite() ) {
      return false;
    }
    if ( $this->is_not_multisite && is_multisite() ) {
      return false;
    }
    if ( ! $this->can_be_production && Helpers::is_production() ) {
      return false;
    }
    if ( ! $this->can_be_staging && Helpers::is_staging() ) {
      return false;
    }
    if ( ! $this->can_be_development && Helpers::is_development() ) {
      return false;
    }

    foreach ( $this->capabilities as $capability ) {
      $args = null;

      if ( is_array($capability) ) {
        $args = isset($capability[1]) ? $capability[1] : null;
        $capability = $capability[0];
      }

      if ( ! \current_user_can($capability, $args) ) {
        return false;
      }
    }

    return \true;
  }

}
