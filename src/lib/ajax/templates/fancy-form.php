<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;

/**
 * Class FancyForm
 *
 * FancyForm is pre-made AjaxHandler for building
 * forms with button for executing a function and dry-run
 * and showing the output in a fancy wrapper.
 */
class FancyForm extends AjaxHandler {

  /**
   * @var string Yellow status color. Used for loading / mixed.
   */
  const STATUS_YELLOW = 'rgb(232, 186, 27)';
  /**
   * @var string Green status color. Used for success.
   */
  const STATUS_GREEN = 'rgb(3, 129, 3)';
  /**
   * @var string Red status color. Used for failure.
   */
  const STATUS_RED = 'red';

  /**
   * @var callable|null Callback for building the form.
   */
  private $build_form_func;

  /**
   * @var string|null Text to be shown on the main button.
   */
  private $button_text;
  /**
   * @var string|null   Text to be shown on the dryrun button.
   */
  private $dryrun_button_text;

  /**
   * @var string|null   Text to be shown next to the spinner.
   */
  private $spinner_text;
  /**
   * @var string|null   Text to be shown in wrapper by default.
   */
  private $title_text;

  /**
   * Constructor for FancyForm. Will be called on new instance.
   * @param string $section Unique section inside the postbox.
   */
  public function __construct( $section ) {
    parent::__construct($section);

    $this->set_build_func(
      function ( Component $base, $section ) {
        $this->build_component($base, $section);
      }
    );
  }

  /**
   * Component needed for the AJAX handler.
   * Can be gotten with get_component().
   * @param \Seravo\Postbox\Component $base    Base component.
   * @param string                    $section Unique section inside the postbox.
   * @return void
   */
  public function build_component( Component $base, $section ) {
    $form = new Component();
    if ( $this->build_form_func !== null ) {
      \call_user_func($this->build_form_func, $form);
    }

    if ( $this->button_text === null ) {
      $this->button_text = __('Run', 'seravo');
    }

    if ( $this->dryrun_button_text !== null ) {
      $buttons = Template::side_by_side(
        Template::button($this->dryrun_button_text, $section . '-dryrun-button', 'button-primary'),
        Template::button($this->button_text, $section . '-button', 'button-primary', true)
      );
    } else {
      $buttons = Template::button($this->button_text, $section . '-button', 'button-primary');
    }

    $default_title = $this->title_text !== null ? $this->title_text : __('WIP', 'seravo');
    $spinner_text = $this->spinner_text !== null ? $this->spinner_text : __('Loading...', 'seravo');

    $title = new Component('', '<div class="seravo-result-wrapper-title">', '</div>');
    $title->add_child(Template::spinner_with_text($section . '-spinner', $spinner_text));
    $title->add_child(new Component($default_title, '<span id="' . $section . '-status">', '</span>'));

    $output_wrapper = new Component('', '<div class="seravo-result-wrapper">', '</div>');
    $output_wrapper->add_child($title);
    $output_wrapper->add_child(new Component('', '<div id="' . $section . '-output" class="seravo-result-wrapper-output hidden">', '</div>'));
    $output_wrapper->add_child(Template::show_more_link());

    $component = new Component('', "<div class=\"seravo-ajax-fancy-form\" data-section=\"{$section}\">", '</div>');
    $component->add_child($form);
    $component->add_child($buttons);
    $component->add_child($output_wrapper);

    $base->add_child($component);
  }

  /**
   * Set the callback function to be called for building the form.
   * @param callable $build_form_func Function for building the form.
   * @return void
   */
  public function set_build_form_func( $build_form_func ) {
    $this->build_form_func = $build_form_func;
  }

  /**
   * Set text for the buttons.
   * @param string      $text        Text on the button.
   * @param string|null $dryrun_text Text on the dry-run button (optional).
   * @return void
   */
  public function set_button_text( $text, $dryrun_text = null ) {
    $this->button_text = $text;
    $this->dryrun_button_text = $dryrun_text;
  }

  /**
   * Set text to be shown next to the spinner.
   * @param string|null $text Spinner text.
   * @return void
   */
  public function set_spinner_text( $text ) {
    $this->spinner_text = $text;
  }

  /**
   * Set text to be shown in wrapper by default.
   * @param string|null $text Title text.
   * @return void
   */
  public function set_title_text( $text ) {
    $this->title_text = $text;
  }

  /**
   * Get common FancyForm AJAX response and return it.
   * @param mixed        $output Output to return with AJAX response.
   * @param mixed|string $title  Title text to display on the wrapper.
   * @param mixed|string $color  Color to display on the wrapper.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function get_response( $output, $title, $color ) {
    $response = new AjaxResponse();
    $response->is_success(true);
    $response->set_data(
      array(
        'output' => $output,
        'title' => $title,
        'color' => $color,
      )
    );
    return $response;
  }

}
