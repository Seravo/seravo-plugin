<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class SimpleCommand
 *
 * SimpleCommand is pre-made Postbox for executing
 * a single command on button click and showing the output.
 * Uses Ajax\SimpleCommand as the only AJAX handler.
 */
class SimpleCommand extends InfoBox {

  /**
   * @var \Seravo\Ajax\SimpleCommand The single ajax handler for this postbox.
   */
  private $handler;

  /**
   * Constructor for AutoCommand. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $this->handler = new Ajax\SimpleCommand($id);
    $this->add_ajax_handler($this->handler);
  }

  /**
   * Configure the commands to be executed.
   * @param string      $command       Command to be executed.
   * @param string|null $dryrun        Dry-run command to be executed (default null).
   * @param bool        $allow_failure Whether exit code other than 0 should respond with an error (default false).
   * @return void
   */
  public function set_command( $command, $dryrun = null, $allow_failure = false ) {
    $this->handler->set_command($command, $dryrun, $allow_failure);
  }

  /**
   * Set whether exit code other than 0 should respond with an error.
   * @param bool $allow_failure Whether to allow failure.
   * @return void
   */
  public function set_allow_failure( $allow_failure ) {
    $this->handler->set_allow_failure($allow_failure);
  }

  /**
   * Set a message to be shown if command returns no data.
   * @param string $message Message to be shown for no command output.
   * @return void
   */
  public function set_empty_message( $message ) {
    $this->handler->set_empty_message($message);
  }

  /**
   * Set text for the buttons.
   * @param string $text        Text on the button.
   * @param string $dryrun_text Text on the dry-run button (needs to be if there's dry-run command).
   * @return void
   */
  public function set_button_text( $text, $dryrun_text = null ) {
    $this->handler->set_button_text($text, $dryrun_text);
  }

  /**
   * Set text to be shown next to the spinner.
   * @param string $text Spinner text.
   * @return void
   */
  public function set_spinner_text( $text ) {
    $this->handler->set_spinner_text($text);
  }

}
