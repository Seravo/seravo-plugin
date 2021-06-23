<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;

/**
 * Class CommandRunner
 *
 * CommandRunner is pre-made AjaxHandler for executing
 * commands.
 *
 * This handler doesn't have a pre-made component.
 */
class CommandRunner extends AjaxHandler {

  /**
   * @var string|null Command to be executed on AJAX request.
   */
  private $command;

  /**
   * @var bool Whether dryrun is enabled.
   */
  private $dryrun = false;

  /**
   * @var bool Whether exit code other than 0 should respond with an error.
   */
  private $allow_failure = false;

  /**
   * @var string|null Message to be shown for no command output.
   */
  private $empty_message;

  /**
   * Constructor for CommandRunner. Will be called on new instance.
   * @param string $section Unique section inside the postbox.
   * @param string|null $command Command to be executed.
   * @param int $cache_time Seconds to cache response for (default is 300).
   * @param bool $allow_failure Whether exit code other than 0 should respond with an error.
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
  }

  /**
   * This is called on valid AJAX request.
   * Command is executed here.
   * @param string $section Unique section inside the postbox.
   * @return \Seravo\Ajax\AjaxResponse Response for the client.
   */
  public function ajax_command_exec( $section ) {
    if ( $this->command === null ) {
      return AjaxResponse::unknown_error_response();
    }

    if ( $this->is_dryrun_enabled() && isset($_GET['dryrun']) && $_GET['dryrun'] === 'true' ) {
      $this->command = $command . ' --dry-run';
    }

    $output = null;
    $retval = null;
    exec($this->command, $output, $retval);

    if ( $retval !== 0 && ! $this->allow_failure ) {
      return AjaxResponse::command_error_response($this->command);
    }

    if ( empty($output) ) {
      $output = $this->empty_message === null ? __('Command returned no data', 'seravo') : $this->empty_message;
    } else {
      $output = implode("\n", $output);
    }

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
   * @param string $command Command for exec.
   * @param int $cache_time Seconds to cache response for (default is 300).
   * @param bool $allow_failure Whether exit code other than 0 should respond with an error.
   */
  public function set_command( $command, $cache_time = 300, $allow_failure = false ) {
    $this->command = $command;
    $this->allow_failure = $allow_failure;
    $this->set_cache_time($cache_time);
  }

  /**
   * Set whether exit code other than 0 should respond with an error.
   * @param bool $allow_failure Whether to allow failure.
   */
  public function allow_failure( $allow_failure ) {
    $this->allow_failure = $allow_failure;
  }

  /**
   * Set a message to be shown if command returns no data.
   * @param string $message Message to be shown for no command output.
   */
  public function set_empty_message( $message ) {
    $this->empty_message = $message;
  }

  /**
   * Enables dry-run option for the handler.
   * @param bool $enabled Whether dryrun is enabled.
   */
  public function enable_dryrun( $enabled ) {
    $this->dryrun = $enabled;
  }

  /**
   * Check if dry-running is enabled for the handler.
   * @return bool Whether dry-run is enabled.
   */
  public function is_dryrun_enabled() {
    return $this->dryrun;
  }

}
