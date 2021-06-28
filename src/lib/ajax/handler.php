<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;

/**
 * Class AjaxHandler
 *
 * AJAXHandler adds AJAX functionality for postboxes.
 */
class AjaxHandler {

  /**
   * @var string      String for transient key to be prefixed with.
   */
  const CACHE_KEY_PREFIX = 'seravo_ajax_';
  /**
   * @var string      String for transient key to be suffixed with.
   */
  const CACHE_KEY_SUFFIX = '_data';


  /**
   * @var string|null Unique id/slug of the postbox handler is part of.
   */
  private $id;
  /**
   * @var string      Unique section inside the postbox.
   */
  private $section;
  /**
   * @var string|null WordPress nonce for the page.
   */
  private $ajax_nonce;


  /**
   * @var array|null Function to be called on AJAX call.
   */
  private $ajax_func;
  /**
   * @var array|null Function to be called on AJAX component render.
   */
  private $build_func;
  /**
   * @var int|null   Seconds to cache data returned by $ajax_func.
   */
  private $data_cache_time;

  /**
   * Constructor for AjaxHandler. Will be called on new instance.
   * @param string $section Unique section inside the postbox.
   */
  public function __construct( $section ) {
    $this->section = $section;
  }

  /**
   * Initialize the Ajax handler. This will be call
   * by the postbox once the page is ready.
   * @param string $id    Unique id/slug of the postbox.
   * @param string $nonce Name of WordPress nonce for the page.
   */
  public function init( $id, $nonce ) {
    $this->id = $id;
    $this->ajax_nonce = $nonce;

    add_action(
      'wp_ajax_seravo_ajax_' . $this->id,
      function() {
        return $this->_ajax_handler();
      }
    );
  }

  /**
   * Set the AJAX function for the handler. The function will be
   * called on AJAX calls here. AJAX function should return an AjaxResponse.
   * @param array $ajax_func  Function to be called on AJAX call.
   * @param int   $cache_time Seconds to cache data for (default is 0).
   */
  public function set_ajax_func( $ajax_func, $cache_time = 0 ) {
    $this->ajax_func = $ajax_func;
    $this->data_cache_time = $cache_time;
  }

  /**
   * Set the optional build function for the handler.
   * The function will be called on get_component() calls.
   * @param array $build_func Function to be called on AJAX component render.
   */
  public function set_build_func( $build_func ) {
    $this->build_func = $build_func;
  }

  /**
   * Set the time data returned by AJAX function
   * is cached in transient for.
   * @param int $cache_time Seconds to cache data for (default is 0).
   */
  public function set_cache_time( $cache_time ) {
    $this->data_cache_time = $cache_time;
  }

  /**
   * Get component this AJAX handler needs to function. Calls
   * $build_func to build the component if one exists.
   * @return \Seravo\Postbox\Component Component for this AJAX handler.
   */
  public function get_component() {
    $component = new Component();

    if ( $this->build_func !== null ) {
      \call_user_func($this->build_func, $component, $this->section);
    }

    return $component;
  }

  /**
   * This function will be called by WordPress
   * if AJAX call is made here. Either calls $ajax_func
   * or responds with error on invalid requests.
   *
   * Caching and exceptions are taken care of here.
   */
  public function _ajax_handler() {
    check_ajax_referer($this->ajax_nonce, 'nonce');

    if ( ! isset($_REQUEST['section']) ) {
      // There must always be a section
      AjaxResponse::invalid_request_response()->send();
    }

    if ( $_REQUEST['section'] !== $this->section ) {
      // This request doesn't concern us
      return;
    }

    $cache_key = self::CACHE_KEY_PREFIX . $this->section . self::CACHE_KEY_SUFFIX;

    $response = null;

    try {
      // Check if we should be using transients
      if ( $this->data_cache_time > 0 ) {
        $response = \get_transient($cache_key);
        if ( false === $response ) {
          // The data was not cached, call data_func
          $response = \call_user_func($this->ajax_func, $this->section);
          if ( null !== $response && isset($response->data['success']) && $response->data['success'] === true ) {
            // Cache new result unless it's invalid
            \set_transient($cache_key, $response, $this->data_cache_time);
          }
        }
      } else {
        // Not using cache
        $response = \call_user_func($this->ajax_func, $this->section);
      }
    } catch ( \Exception $exception ) {
      error_log('### Seravo Plugin experienced an error!');
      error_log('### Please report this on GitHub (https://github.com/Seravo/seravo-plugin) with following:');
      error_log($exception);

      $response = AjaxResponse::exception_response();
    }

    if ( $response !== null ) {
      // We got a valid response
      $response->send();
    }

    AjaxResponse::unknown_error_response()->send();
  }

  /**
   * Get the section for the AJAX handler.
   * @return string The AJAX section.
   */
  public function get_section() {
    return $this->section;
  }

}
