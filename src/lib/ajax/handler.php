<?php

namespace Seravo\Ajax;

use Seravo\API\Container;
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
   * @var callable|null Function to be called on AJAX call.
   */
  private $ajax_func;
  /**
   * @var callable|null Function to be called on AJAX component render.
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
   * @return void
   */
  public function init( $id, $nonce ) {
    $this->id = $id;
    $this->ajax_nonce = $nonce;

    \add_action(
      'wp_ajax_seravo_ajax_' . $this->id,
      function() {
        $this->_ajax_handler();
      }
    );
  }

  /**
   * Set the AJAX function for the handler. The function will be
   * called on AJAX calls here. AJAX function should return an AjaxResponse.
   * @param callable $ajax_func  Function to be called on AJAX call.
   * @param int      $cache_time Seconds to cache data for (default is 0).
   * @return void
   */
  public function set_ajax_func( $ajax_func, $cache_time = 0 ) {
    $this->ajax_func = $ajax_func;
    $this->data_cache_time = $cache_time;
  }

  /**
   * Set the optional build function for the handler.
   * The function will be called on get_component() calls.
   * @param callable $build_func Function to be called on AJAX component render.
   * @return void
   */
  public function set_build_func( $build_func ) {
    $this->build_func = $build_func;
  }

  /**
   * Set the time data returned by AJAX function
   * is cached in transient for.
   * @param int $cache_time Seconds to cache data for (default is 0).
   * @return void
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
   * @return void
   */
  public function _ajax_handler() {
    if ( $this->ajax_nonce === null ) {
      AjaxResponse::unknown_error_response()->send();
      return;
    }

    \check_ajax_referer($this->ajax_nonce, 'nonce');

    if ( ! isset($_REQUEST['section']) ) {
      // There must always be a section
      AjaxResponse::invalid_request_response()->send();
      return;
    }

    if ( $_REQUEST['section'] !== $this->section ) {
      // This request doesn't concern us
      return;
    }

    if ( ! \is_callable($this->ajax_func) ) {
      // No ajax function
      AjaxResponse::unknown_error_response()->send();
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
      \error_log('### Seravo Plugin experienced an error!');
      \error_log('### Please report this on GitHub (https://github.com/Seravo/seravo-plugin) with following:');
      \error_log($exception);

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

  /**
   * Get the data cache time.
   * @return int|null Seconds to cache data returned by $ajax_func.
   */
  public function get_cache_time() {
    return $this->data_cache_time;
  }

  /**
   * Check if polling is no longer needed or continue doing it.
   * @return \Seravo\Ajax\AjaxResponse|bool AjaxResponse if still polling, true if
   *                                        polling is done, false if not polling yet.
   */
  public static function check_polling() {
    if ( ! isset($_REQUEST['poller_id']) || ! $_REQUEST['poller_id'] === '' ) {
      return false;
    }

    if ( ! isset($_REQUEST['poller_type']) || ! $_REQUEST['poller_type'] === '' ) {
      return false;
    }

    $id = \base64_decode($_REQUEST['poller_id'], true);
    $type = $_REQUEST['poller_type'];

    if ( $id === false ) {
      // Poller ID wasn't valid base64 string
      return false;
    }

    if ( $type === 'pid' ) {
      return self::check_pid_polling($id);
    } else if ( $type === 'task' ) {
      return self::check_task_polling($id);
    }

    return false;
  }

  private static function check_pid_polling($pid) {
    if ( \Seravo\Shell::is_pid_running($pid) ) {
      return AjaxResponse::require_polling_response($pid, 'pid');
    }
    return true;
  }

  private static function check_task_polling($id) {
    $response = Container::task_status($id);

    if ( \is_wp_error($response) ) {
      return AjaxResponse::api_error_response();
    }

    if ( ! isset($response['status']) ) {
      return AjaxResponse::api_error_response();
    }

    // Has the task completed?
    if ( $response['status'] === 'completed' ) {
      return true;
    }

    // Is it not running but hasn't completed (error)?
    if ( $response['status'] === 'started' ) {
      return AjaxResponse::require_polling_response($id, 'task');
    }

    $message = '';
    if ( isset($response['msg']) ) {
      $message = " with output:\n" . implode("\n", $response['msg']);
    }

    error_log("Seravo Plugin task (ID: $id) failed $message");
    return AjaxResponse::api_error_response();
  }

}
