<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class FancyForm
 *
 * FancyForm is pre-made Postbox for building
 * forms with button for executing a function and dry-run
 * and showing the output in a fancy wrapper.
 */
class FancyForm extends InfoBox {

  /**
   * @var \Seravo\Ajax\FancyForm The single ajax handler for this postbox.
   */
  private $handler;

  /**
   * Constructor for FancyForm. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $this->handler = new Ajax\FancyForm($id);
    $this->add_ajax_handler($this->handler);
  }

  /**
   * Set the callback function to be called for building the form.
   * @param callable $build_form_func Function for building the form.
   * @return void
   */
  public function set_build_form_func( $build_form_func ) {
    $this->handler->set_build_form_func($build_form_func);
  }

  /**
   * Set the callback function for the postbox. The function will be
   * called on button clicks. The callback function should return an AjaxResponse.
   * @param callable $button_func Function to be called on button click.
   * @return void
   */
  public function set_ajax_func( $button_func ) {
    $this->handler->set_ajax_func($button_func);
  }

  /**
   * Set text for the buttons.
   * @param string $text        Text on the button.
   * @param string $dryrun_text Text on the dry-run button (optional).
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

  /**
   * Set text to be shown in wrapper by default.
   * @param string $text Title text.
   * @return void
   */
  public function set_title_text( $text ) {
    $this->handler->set_title_text($text);
  }

}
