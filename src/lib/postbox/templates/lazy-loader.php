<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class LazyLoader
 *
 * LazyLoader is a pre-made postbox for executing custom
 * AJAX function with spinner and showing the output.
 */
class LazyLoader extends InfoBox {

  /**
   * @var \Seravo\Ajax\LazyLoader The single ajax handler for this postbox.
   */
  private $handler;

  /**
   * Constructor for LazyLoader. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $this->handler = new Ajax\LazyLoader($id);
    $this->add_ajax_handler($this->handler);
  }

  /**
   * Set AJAX function for the handler of the postbox.
   * @param callable $ajax_func Function to execute on AJAX call.
   * @return void
   */
  public function set_ajax_func( $ajax_func ) {
    $this->handler->set_ajax_func($ajax_func);
  }

  /**
   * Set <hr> element usage for the AJAX handler.
   * @param bool $use_hr True for using <hr>.
   * @return void
   */
  public function use_hr( $use_hr ) {
    $this->handler->use_hr($use_hr);
  }

}
