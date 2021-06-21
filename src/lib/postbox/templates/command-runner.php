<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class CommandRunner
 *
 * CommandRunner is pre-made Postbox for excuting commands.
 * This postbox doesn't have handler set but needs AJAXHandler
 * extending AutoCommand.
 *
 * Component structure
 * - info paragraph
 * - AJAX component
 */
class CommandRunner extends Postbox {

  /**
   * @var string[] Paragraphs to display.
   */
  private $paragraphs = array();

  /**
   * Constructor for AutoCommand. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $this->set_build_func(
      function ( Component $base ) {
        return $this->build($base);
      }
    );
  }

  /**
   * Postbox will be built here.
   * @param \Seravo\Postbox\Component $base Base component to build postbox on.
   */
  public function build( Component $base ) {
    foreach ( $this->paragraphs as $paragraph ) {
      $base->add_child(Template::paragraph($paragraph));
    }
    $base->add_child($this->ajax_handlers[$this->id]->get_component());
  }

  /**
   * Add new paragraph to display.
   * @param string $text Info text.
   */
  public function add_paragraph( $text ) {
    $this->paragraphs[] = $text;
  }

  /**
   * Set message to be shown when there's no command output.
   * @param string $message Empty output message.
   */
  public function set_empty_message( $message ) {
    $this->ajax_handlers[$this->id]->set_empty_message($message);
  }

  /**
   * Configure the command to be executed.
   * @param string $command Command to be executed.
   * @param int $cache_time Seconds to cache response for (default is 300).
   * @param bool $allow_failure Whether exit code other than 0 should respond with an error.
   */
  public function set_command( $command, $cache_time = 300, $allow_failure = false ) {
    $ajax_handler = $this->ajax_handlers[$this->id];
    $ajax_handler->set_command($command);
    $ajax_handler->allow_failure($allow_failure);
    $ajax_handler->set_cache_time($cache_time);
  }

}
