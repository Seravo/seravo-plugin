<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;

/**
 * Class AutoCommand
 *
 * AutoCommand is pre-made AjaxHandler for automatically executing
 * a single command and showing the output.
 *
 * Adds component for the spinner and output.
 */
class AutoCommand extends CommandRunner {

  /**
   * Constructor for AjaxHandler. Will be called on new instance.
   * @param string $section Unique section inside the postbox.
   * @param string|null $command Command to be executed.
   * @param int $cache_time Seconds to cache response for (default is 300).
   * @param bool $allow_failure Whether exit code other than 0 should respond with an error.
   */
  public function __construct( $section, $command = null, $cache_time = 300, $allow_failure = false ) {
    parent::__construct($section, $command, $cache_time, $allow_failure);

    $this->set_build_func(
      function ( Component $base, $section ) {
        $this->build_component($base, $section);
      }
    );
  }

  /**
   * Component needed for the AJAX handler.
   * Can be gotten with get_component().
   * @param \Seravo\Postbox\Component $base Base component.
   * @param string $section Unique section inside the postbox.
   */
  public function build_component( Component $base, $section ) {
    $component = new Component();
    $component->set_wrapper("<div class=\"seravo-ajax-auto-command\" data-section=\"{$section}\">", '</div>');
    $component->add_child(Template::spinner($section . '-spinner', ''));
    $component->add_child(Template::simple_command_output($section . '-output', 'hidden'));

    $base->add_child($component);
  }

}
