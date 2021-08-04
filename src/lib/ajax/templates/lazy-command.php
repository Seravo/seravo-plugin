<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;

/**
 * Class LazyCommand
 *
 * LazyCommand is pre-made AjaxHandler for automatically executing
 * a single command and showing the output.
 */
class LazyCommand extends LazyLoader {

  /**
   * @var string|null Command to be executed on AJAX request.
   */
  private $command;
  /**
   * @var bool        Whether exit code other than 0 should respond with an error.
   */
  private $allow_failure = false;

  /**
   * @var string|null Message to be shown for no command output.
   */
  private $empty_message;

  /**
   * Constructor for LazyCommand. Will be called on new instance.
   * @param string      $section       Unique section inside the postbox.
   * @param string|null $command       Command to be executed.
   * @param int         $cache_time    Seconds to cache response for (default 300).
   * @param bool        $allow_failure Whether exit code other than 0 should respond with an error (default false).
   */
  public function __construct( $section, $command = null, $cache_time = 300, $allow_failure = false ) {
    parent::__construct($section);

    $this->command = $command;
    $this->allow_failure = $allow_failure;

    $this->set_ajax_func(
      function ( $section ) {
        return $this->ajax_command_exec($section);
      },
      $cache_time
    );

    $this->set_build_func(
      function ( Component $base, $section ) {
        $this->build_component($base, $section);
      }
    );
  }

  /**
   * Function called on valid AJAX request. The command is executed here.
   * @param string $section Unique section inside the postbox.
   * @return \Seravo\Ajax\AjaxResponse Response for the client.
   */
  public function ajax_command_exec( $section ) {
    $exec_command = $this->command;

    if ( $exec_command === null ) {
      return AjaxResponse::unknown_error_response();
    }

    $output = null;
    $retval = null;
    \exec($exec_command, $output, $retval);

    if ( $retval !== 0 && ! $this->allow_failure ) {
      return AjaxResponse::command_error_response($exec_command, $retval);
    }

    if ( $output === array() ) {
      $output = $this->empty_message === null ? \__('Command returned no data', 'seravo') : $this->empty_message;
    } else {
      $output = \implode("\n", $output);
    }

    $output = '<pre>' . $output . '</pre>';

    $response = new AjaxResponse();
    $response->is_success(true);
    $response->set_data(
      array(
        'output' => $output,
      )
    );
    return $response;
  }

  /**
   * Set the command to be executed.
   * @param string $command       Command for exec.
   * @param int    $cache_time    Seconds to cache response for (default 300).
   * @param bool   $allow_failure Whether exit code other than 0 should respond with an error (default false).
   * @return void
   */
  public function set_command( $command, $cache_time = 300, $allow_failure = false ) {
    $this->command = $command;
    $this->allow_failure = $allow_failure;

    $this->set_cache_time($cache_time);
  }

  /**
   * Set whether exit code other than 0 should respond with an error.
   * @param bool $allow_failure Whether to allow failure.
   * @return void
   */
  public function set_allow_failure( $allow_failure ) {
    $this->allow_failure = $allow_failure;
  }

  /**
   * Set a message to be shown if command returns no data.
   * @param string $message Message to be shown for no command output.
   * @return void
   */
  public function set_empty_message( $message ) {
    $this->empty_message = $message;
  }

}
