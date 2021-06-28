<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;

/**
 * Class SimpleCommand
 *
 * SimpleCommand is pre-made AjaxHandler for executing
 * a single command on button click and showing the output.
 */
class SimpleCommand extends SimpleForm {

  /**
   * @var string|null Command to be executed on AJAX request.
   */
  private $command;
  /**
   * @var string|null Special command to dry run.
   */
  private $dryrun_command;

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
   * @param string|null $dryrun        Dry-run command to be executed (default null).
   * @param bool        $allow_failure Whether exit code other than 0 should respond with an error (default false).
   */
  public function __construct( $section, $command = null, $dryrun = null, $allow_failure = false ) {
    parent::__construct($section);

    $this->command = $command;
    $this->allow_failure = $allow_failure;

    $this->set_ajax_func(
      function ( $section ) {
        return $this->ajax_command_exec($section);
      },
      0
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
    if ( $this->command === null ) {
      return AjaxResponse::unknown_error_response();
    }

    $dry_run = isset($_GET['dryrun']) && $_GET['dryrun'] === 'true';
    if ( $dry_run && $this->dryrun_command === null ) {
      return AjaxResponse::unknown_error_response();
    } else if ( $dry_run && $this->dryrun_command !== null ) {
      $exec_command = $this->dryrun_command;
    } else {
      $exec_command = $this->command;
    }

    $output = null;
    $retval = null;
    exec($exec_command, $output, $retval);

    if ( $retval !== 0 && ! $this->allow_failure ) {
      return AjaxResponse::command_error_response($this->command);
    }

    if ( empty($output) ) {
      $output = $this->empty_message === null ? __('Command returned no data', 'seravo') : $this->empty_message;
    } else {
      $output = implode("\n", $output);
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
   * Configure the commands to be executed.
   * @param string      $command       Command to be executed.
   * @param string|null $dryrun        Dry-run command to be executed (default null).
   * @param bool        $allow_failure Whether exit code other than 0 should respond with an error (default false).
   */
  public function set_command( $command, $dryrun = null, $allow_failure = false ) {
    $this->command = $command;
    $this->dryrun_command = $dryrun;
    $this->allow_failure = $allow_failure;
  }

  /**
   * Set whether exit code other than 0 should respond with an error.
   * @param bool $allow_failure Whether to allow failure.
   */
  public function set_allow_failure( $allow_failure ) {
    $this->allow_failure = $allow_failure;
  }

  /**
   * Set a message to be shown if command returns no data.
   * @param string $message Message to be shown for no command output.
   */
  public function set_empty_message( $message ) {
    $this->empty_message = $message;
  }

}
