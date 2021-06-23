<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;

/**
 * Class ButtonCommand
 *
 * ButtonCommand is pre-made AjaxHandler for executing
 * a single command on button click showing the output.
 *
 * Adds component for the button, spinner and output.
 */
class ButtonCommand extends CommandRunner {

  /**
   * @var string|null Text to be shown on the button.
   */
  private $button_text;

  /**
   * @var string|null Text to be shown on the dryrun button.
   */
  private $dryrun_button_text;

  /**
   * Constructor for ButtonCommand. Will be called on new instance.
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
    $button_content = $this->button_text !== null ? $this->button_text : __('Run', 'seravo');
    $dryrun_button_content = $this->dryrun_button_text !== null ? $this->dryrun_button_text : __('Dry-run', 'seravo');

    if ( $this->is_dryrun_enabled() ) {
      $spinner_button = Template::n_by_side(
        array(
          Template::button($dryrun_button_content, $section . '-dryrun-button', 'button-primary'),
          Template::button($button_content, $section . '-button', 'button-primary', true),
          Template::spinner($section . '-spinner'),
        )
      );
    } else {
      $button = Template::button($button_content, $section . '-button');
      $spinner = Template::spinner($section . '-spinner');
      $spinner_button = Template::side_by_side($button, $spinner);
    }

    $component = new Component('', "<div class=\"seravo-ajax-button-command\" data-section=\"{$section}\">", '</div>');
    $component->add_child($spinner_button);
    $component->add_child(Template::simple_command_output($section . '-output', 'hidden'));

    $base->add_child($component);
  }

  /**
   * Set the command to be executed.
   * @param string $command Command for exec.
   * @param int $cache_time Seconds to cache response for (default is 300).
   * @param bool $allow_failure Whether exit code other than 0 should respond with an error.
   * @param bool $dryrun Whether dry-running is enabled.
   */
  public function set_command( $command, $cache_time = 300, $allow_failure = false, $dryrun = false ) {
    if ( $dryrun ) {
      $cache_time = 0;
    }

    parent::set_command($command, $cache_time, $allow_failure);
    $this->enable_dryrun($dryrun);
  }

  /**
   * Set text for the command execute button.
   * @param string $text Text on the button.
   */
  public function set_button_text( $text, $dryrun_text = null ) {
    $this->button_text = $text;
    $this->dryrun_button_text = $dryrun_text;
  }

}
