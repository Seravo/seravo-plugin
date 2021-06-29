<?php

namespace Seravo\Postbox;

use \Seravo\Ajax;

/**
 * Class AutoCommand
 *
 * AutoCommand is pre-made Postbox for automatically executing
 * a single command and showing the output. Uses AutoCommand
 * as the only AJAX handler.
 *
 * Component structure
 * - info paragraph
 * - command output
 */
class AutoCommand extends CommandRunner {

  /**
   * Constructor for AutoCommand. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    parent::__construct($id, $context);

    $ajax_handler = new Ajax\AutoCommand($id);
    $this->add_ajax_handler($ajax_handler);
  }

}
