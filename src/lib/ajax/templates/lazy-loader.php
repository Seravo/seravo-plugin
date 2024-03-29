<?php

namespace Seravo\Ajax;

use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;

/**
 * Class LazyLoader
 *
 * LazyLoader is pre-made AjaxHandler for automatically executing
 * a function on page load showing the output.
 */
class LazyLoader extends AjaxHandler {

  /**
   * @var bool Whether to use <hr> element or not.
   */
  private $use_hr = true;

  /**
   * Constructor for LazyLoader. Will be called on new instance.
   * @param string $section    Unique section inside the postbox.
   * @param int    $cache_time Seconds to cache response for (default 300).
   */
  public function __construct( $section, $cache_time = 300 ) {
    parent::__construct($section);

    $this->set_cache_time($cache_time);

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
   * @param string                    $section Unique section inside the postbox.
   * @return void
   */
  public function build_component( Component $base, $section ) {
    $component = new Component();
    $component->set_wrapper("<div class=\"seravo-ajax-lazy-load\" data-section=\"{$section}\">" . ($this->use_hr ? '<hr>' : ''), '</div>');
    $component->add_child(Template::spinner($section . '-spinner', ''));
    $component->add_child(new Component('', '<span id="' . $section . '-output" class="hidden">', '</span>'));

    $base->add_child($component);
  }

  /**
   * Set <hr> element usage for the AJAX handler.
   * @param bool $use_hr True for using <hr>.
   * @return void
   */
  public function use_hr( $use_hr ) {
    $this->use_hr = $use_hr;
  }

}
