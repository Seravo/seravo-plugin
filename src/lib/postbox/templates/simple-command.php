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
   * Constructor for AutoCommand. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $ajax_handler = new Ajax\SimpleCommand($id);
    $this->add_ajax_handler($ajax_handler);
  }

  /**
   * Configure the commands to be executed.
   * @param string      $command       Command to be executed.
   * @param string|null $dryrun        Dry-run command to be executed (default null).
   * @param bool        $allow_failure Whether exit code other than 0 should respond with an error (default false).
   */
  public function set_command( $command, $dryrun = null, $allow_failure = false ) {
    $handler = $this->ajax_handlers[$this->id];
    $handler->set_command($command, $dryrun, $allow_failure);
  }

  /**
   * Set whether exit code other than 0 should respond with an error.
   * @param bool $allow_failure Whether to allow failure.
   */
  public function set_allow_failure( $allow_failure ) {
    $handler = $this->ajax_handlers[$this->id];
    $handler->set_allow_failure($allow_failure);
  }

  /**
   * Set a message to be shown if command returns no data.
   * @param string $message Message to be shown for no command output.
   */
  public function set_empty_message( $message ) {
    $handler = $this->ajax_handlers[$this->id];
    $handler->set_empty_message($message);
  }

  /**
   * Set text for the buttons.
   * @param string $text        Text on the button.
   * @param string $dryrun_text Text on the dry-run button (needs to be if there's dry-run command).
   */
  public function set_button_text( $text, $dryrun_text = null ) {
    $handler = $this->ajax_handlers[$this->id];
    $handler->set_button_text($text, $dryrun_text);
  }

}
