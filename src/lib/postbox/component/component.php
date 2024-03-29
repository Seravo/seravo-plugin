<?php
/**
 * File for Seravo postbox components.
 */

namespace Seravo\Postbox;

/**
 * Class Component
 *
 * Component in it's simplest form is just a
 * html element. The element may be wrapped with
 * other elements and contain child components.
 */
class Component {

  /**
   * @var string The HTML content of the component.
   */
  private $content = '';
  /**
   * @var string The opening tag of the component.
   */
  private $wrapper_open = '';
  /**
   * @var string The closing tag of the component.
   */
  private $wrapper_close = '';

  /**
   * @var \Seravo\Postbox\Component[] Children to be rendered after $content.
   */
  private $children = array();

  /**
   * Constructor for AjaxHandler. Will be called on new instance.
   * @param string $content       The HTML content of the component.
   * @param string $wrapper_open  The opening tag of the component.
   * @param string $wrapper_close The closing tag of the component.
   */
  public function __construct( $content = '', $wrapper_open = '', $wrapper_close = '' ) {
    $this->content = $content;
    $this->wrapper_open = $wrapper_open;
    $this->wrapper_close = $wrapper_close;
  }

  /**
   * Add a child which will be rendered after the content.
   * @param \Seravo\Postbox\Component|null $child Child component to be added.
   * @return void
   */
  public function add_child( $child ) {
    if ( $child === null ) {
      return;
    }

    $this->children[] = $child;
  }

  /**
   * Add children which are rendered after the content.
   * @param \Seravo\Postbox\Component[]|null|null[] $children List of children to be added.
   * @return void
   */
  public function add_children( $children ) {
    if ( $children === null || $children === array() ) {
      return;
    }

    foreach ( $children as $child ) {
      // Add them with add_child so same checks
      // are used for each child.
      $this->add_child($child);
    }
  }

  /**
   * Get HTML for rendering component.
   * @return string Component HTML.
   */
  public function to_html() {
    $html = $this->wrapper_open . $this->content;
    foreach ( $this->children as $child ) {
      $html .= $child->to_html();
    }
    return $html . $this->wrapper_close;
  }

  /**
   * Prints the HTML for the component.
   * @return void
   */
  public function print_html() {
    echo $this->to_html();
  }

  /**
   * Wraps the component with HTML tags.
   * @param string $open The opening tag for the component.
   * @param string $close The closing tag for the component.
   * @return $this
   */
  public function set_wrapper( $open, $close ) {
    $this->wrapper_open = $open;
    $this->wrapper_close = $close;
    return $this;
  }

  /**
   * Set raw content for the component. Content
   * is printed between $wrapper tags.
   * @param string $content Content for the component.
   * @return $this
   */
  public function set_content( $content ) {
    $this->content = $content;
    return $this;
  }

  /**
   * Get component from raw HTML.
   * @param string $content Raw HTML.
   * @return \Seravo\Postbox\Component Component with HTML content.
   */
  public static function from_raw( $content ) {
    $component = new Component();
    $component->set_content($content);
    return $component;
  }

}
