<?php

namespace Seravo\Module;

/**
 * Class Module
 *
 * Module is a base class for Seravo-Plugin modules
 * and the common module functionality.
 */
trait Module {

  /**
   * @var Object Instance of the module using this trait.
   */
  protected $instance;

  /**
   * Will be called before initialization
   * to see if the module should be loaded.
   * @return bool
   */
  protected function should_load() {
    return true;
  }

  /**
   * Will be called for module initialization.
   * @return void
   */
  abstract protected function init();

  /**
   * Function for printing module specific errors to php-error.log.
   * @param string $message Message to log.
   * @return void
   */
  protected static function error_log( $message ) {
    \error_log("[Seravo-Plugin - module '" . self::get_name() . "'] " . $message);
  }

  /**
   * Get name of the module. The name is the class name
   * with namespace prefix removed and in lowercase.
   * @return string Name of the module.
   */
  public static function get_name() {
    $class = \explode('\\', static::class);
    return \strtolower(\array_pop($class));
  }

  /**
   * Load the module. This should only be called ones.
   * The module will be initialized here after requirement checks.
   * @return void
   */
  public static function load() {
    $instance = new static();

    $name = self::get_name();

    if ( ! $instance->should_load() ) {
      if ( \defined('SERAVO_PLUGIN_DEBUG') && SERAVO_PLUGIN_DEBUG ) {
        \error_log("Module \"{$name}\" not loaded: should_load() returned 'false'.");
      }
      return;
    }

    if ( ! (bool) \apply_filters('seravo_load_module_' . $name, true) ) {
      if ( \defined('SERAVO_PLUGIN_DEBUG') && SERAVO_PLUGIN_DEBUG ) {
        \error_log("Module \"{$name}\" not loaded: filter disabled the module.");
      }
      return;
    }

    $instance->init();
  }

}
