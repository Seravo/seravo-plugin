<?php

namespace Seravo\Postbox;

/**
 * Class SettingsForm
 *
 * SettingsForm is pre-made postbox for only showing
 * paragraphs and setting sections.
 */
class SettingsForm extends InfoBox {

  /**
   * Constructor for SettingsForm. Will be called on new instance.
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
    foreach ( $this->setting_sections as $section ) {
      $base->add_child($section->get_notifications());
    }

    foreach ( $this->paragraphs as $paragraph ) {
      $base->add_child(Template::paragraph($paragraph));
    }

    foreach ( $this->setting_sections as $section ) {
      $base->add_child($section->get_component());
    }
  }

}
