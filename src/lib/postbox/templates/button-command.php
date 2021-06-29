<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class ButtonCommand
 *
 * ButtonCommand is pre-made Postbox for
 * executing a single command on button click showing
 * the output. Uses ButtonCommand as the only AJAX handler.
 *
 * Component structure
 * - info paragraph
 * - AJAX component
 */
class ButtonCommand extends CommandRunner {

  /**
   * Constructor for ButtonCommand. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $ajax_handler = new Ajax\ButtonCommand($id);
    $this->add_ajax_handler($ajax_handler);
  }

  /**
   * Set text for the command execute button.
   * @param string $text Text on the button.
   */
  public function set_button_text( $text ) {
    $this->ajax_handlers[$this->id]->set_button_text($text);
  }

}
