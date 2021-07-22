<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class LazyCommand
 *
 * LazyCommand is pre-made Postbox for automatically executing
 * a single command and showing the output. Uses Ajax\LazyCommand
 * as the only AJAX handler.
 */
class LazyCommand extends InfoBox {

  /**
   * @var \Seravo\Ajax\LazyCommand The single ajax handler for this postbox.
   */
  private $handler;

  /**
   * Constructor for AutoCommand. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $this->handler = new Ajax\LazyCommand($id);
    $this->add_ajax_handler($this->handler);
  }

  /**
   * Set the command to be executed.
   * @param string $command Command for exec.
   * @param int    $cache_time Seconds to cache response for (default 300).
   * @param bool   $allow_failure Whether exit code other than 0 should respond with an error (default false).
   * @return void
   */
  public function set_command( $command, $cache_time = 300, $allow_failure = false ) {
    $this->handler->set_command($command, $cache_time, $allow_failure);
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

}
