<?php

namespace Seravo\Postbox;

/**
 * Class InfoBox
 *
 * InfoBox is pre-made Postbox for showing paragraphs.
 */
class InfoBox extends Postbox {

  /**
   * @var string[] Paragraphs to display.
   */
  private $paragraphs = array();

  /**
   * Constructor for InfoBox. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $this->set_build_func(
      function ( Component $base ) {
        $this->build($base);
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
    foreach( $this->ajax_handlers as $ajax_handler ) {
      $base->add_child($ajax_handler->get_component());
    }
  }

  /**
   * Add new paragraph to display.
   * @param string $text Info text.
   */
  public function add_paragraph( $text ) {
    $this->paragraphs[] = $text;
  }

}
