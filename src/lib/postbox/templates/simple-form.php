<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class SimpleForm
 *
 * SimpleForm is pre-made Postbox for building forms
 * with button for executing a function and dry-run.
 */
class SimpleForm extends InfoBox {

  /**
   * Constructor for SimpleForm. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $ajax_handler = new Ajax\SimpleForm($id);
    $this->add_ajax_handler($ajax_handler);
  }

  /**
   * Set the callback function for the postbox. The function will be
   * called on button clicks. The callback function should return an AjaxResponse.
   * @param array $button_func Function to be called on button click.
   */
  public function set_button_func( $button_func ) {
    $handler = $this->ajax_handlers[$this->id];
    $handler->set_ajax_func($button_func);
  }

  /**
   * Set text for the buttons.
   * @param string $text        Text on the button.
   * @param string $dryrun_text Text on the dry-run button (optional).
   */
  public function set_button_text( $text, $dryrun_text = null ) {
    $handler = $this->ajax_handlers[$this->id];
    $handler->set_button_text($text, $dryrun_text);
  }

}
