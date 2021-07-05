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
   * Constructor for LazyLoader. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $ajax_handler = new Ajax\LazyLoader($id);
    $this->add_ajax_handler($ajax_handler);
  }

  /**
   * Set AJAX function for the handler of the postbox.
   * @param function $ajax_func Function to execute on AJAX call.
   */
  public function set_ajax_func( $ajax_func ) {
    $handler = $this->ajax_handlers[$this->id];
    $handler->set_ajax_func($ajax_func);
  }

  /**
   * Set <hr> element usage for the AJAX handler.
   * @param bool $use_hr True for using <hr>.
   */
  public function use_hr( $use_hr ) {
    $this->ajax_handlers[$this->id]->use_hr($use_hr);
  }

}
