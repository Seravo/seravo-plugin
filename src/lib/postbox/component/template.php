<?php

namespace Seravo\Postbox;

/**
 * Class Template
 *
 * Template has static methods for building
 * components from 'templates'. Templates should be
 * used to keep the components unified.
 */
class Template {

  /**
   * Get paragraph component for showing error messages.
   * @param string $message Error message to display.
   * @return \Seravo\Postbox\Component Error component.
   */
  public static function error_paragraph( $message ) {
    $html = sprintf('<p><b>%s</b></p>', $message);
    return Component::from_raw($html);
  }

  /**
   * Display HTML paragraph with given content.
   * @param string $content The content to display.
   * @param string $class   Paragraph class to apply.
   * @return \Seravo\Postbox\Component Paragraph component.
   */
  public static function paragraph( $content, $class = 'paragraph-text' ) {
    return Component::from_raw('<p class="' . $class . '">' . $content . '</p>');
  }

  /**
   * Display basic Seravo Plugin widget text.
   * @param string $content The content to display.
   * @param string $class Optional class for the div element.
   * @return \Seravo\Postbox\Component Div component using postbox-text class.
   */
  public static function text( $content, $class = 'postbox-text' ) {
    return Component::from_raw('<div class="' . $class . '">' . $content . '</div>');
  }

  /**
   * Display section title on widget.
   * @param string $content The given title to display.
   * @param string $class Optional class for the title element.
   * @return \Seravo\Postbox\Component Title component.
   */
  public static function section_title( $content, $class = 'title' ) {
    return Component::from_raw('<h3 class="' . $class . '">' . $content . '</h3>');
  }

  /**
   * Display HTML list view.
   * @param string|array $element Element or elements to add onto list.
   * @return \Seravo\Postbox\Component List view component.
   */
  public static function list_view( $element ) {
    $component = new Component('', '<ul class="postbox-ul">', '</ul>');

    if ( is_array($element) ) {
      foreach ( $element as $list_object ) {
        $component->add_child(Component::from_raw('<li>' . $list_object . '</li>'));
      }
      return $component;
    }

    $component->add_child(Component::from_raw('<li>' . $element . '</li>'));

    return $component;
  }

  /**
    * Display link
    * @param string $content Text to wrap the link around.
    * @param string $href    Link URL.
    * @param string $id      ID for the link element.
    * @param string $class   Optional class for the link element.
    * @param string $target  Optional link element target, default _blank.
    * @return \Seravo\Postbox\Component Link component.
    */
   public static function link( $content, $href, $id, $class = 'button', $target = '_blank' ) {
    return Component::from_raw('<a id="' . $id . '" class="' . $class . ' href="' . $href . '" target="' . $target . '" >' . $content . '</a>');
   }

  /**
   * Generates link with icon.
   * @param string $href         Link URL.
   * @param string $link_content Content to wrap the link around to.
   * @return \Seravo\Postbox\Component Button with icon.
   */
  public static function button_link_with_icon( $href, $link_content ) {
    return Component::from_raw(
      '<a href="' . $href . '" class="button" target="_blank">' . $link_content . '
      <span aria-hidden="true" class="dashicons dashicons-external"></span> </a>'
    );
  }

  /**
   * Display Seravo Plugin tooltip.
   * @param string $tooltiptext The given content to display on tooltip.
   * @return \Seravo\Postbox\Component Tooltip component.
   */
  public static function tooltip( $tooltiptext ) {
    return Component::from_raw(
      '<span class="tooltip dashicons dashicons-info"> <span class="tooltiptext"> ' .
      $tooltiptext . '</span></span>'
    );
  }

  /**
   * Display basic clickable / interactive button to run for example AJAX side commands
   * $content Text to display in the button.
   * @param string $content  Button text.
   * @param string $id       Button id.
   * @param string $class    Specified button class to use.
   * @param bool   $disabled Whether the button is disabled.
   * @return \Seravo\Postbox\Component Button component.
   */
  public static function button( $content, $id, $class = 'button-primary', $disabled = false ) {
    $disabled = $disabled ? ' disabled' : '';
    return Component::from_raw('<button id="' . $id . '" class="' . $class . '"' . $disabled . '>' . $content . '</button>');
  }

  /**
   * Display spinner image. In default it's hidden.
   * @param string $id Id for this spinner.
   * @param string $class Class for the div spinner component, default 'seravo-spinner-wrapper seravo-spinner'.
   * @return \Seravo\Postbox\Component Spinner component.
   */
  public static function spinner( $id, $class = 'seravo-spinner' ) {
    return Component::from_raw('<div id="' . $id . '" class="seravo-spinner-wrapper ' . $class . '"><img src="/wp-admin/images/spinner.gif"></div>');
  }

  /**
   * Display spinner image with loading text next to it. In default it's hidden.
   * @param string $id Id for this spinner.
   * @param string $text Text next to spinner.
   * @param string $spinner_class Class for the div spinner component, default 'spinner'.
   * @return \Seravo\Postbox\Component Spinner component.
   */
  public static function spinner_with_text( $id, $text, $spinner_class = 'seravo-spinner' ) {
    $spinner = new Component('<div id="' . $id . '" class="' . $spinner_class . '"><img src="/wp-admin/images/spinner.gif"></div>');
    $text = new Component($text, '<span><b>', '</b></span>');

    $wrapper = new Component('', '<div class="seravo-spinner-wrapper">', '</div>');
    $wrapper->add_child(self::side_by_side($spinner, $text));
    return $wrapper;
  }

  /**
   * Display status widget based on the input.
   * @param bool $status True for success and false for failure.
   * @return \Seravo\Postbox\Component Paragraph component with text.
   */
  public static function success_failure( $status ) {
    if ( $status ) {
      $class = 'success';
      $msg = sprintf('<b>%s</b>', __('Success!', 'seravo'));
    } else {
      $class = 'failure';
      $msg = sprintf('<b>%s</b>', __('Failure!', 'seravo'));
    }

    return Component::from_raw('<p class="' . $class . '">' . $msg . '</p>');
  }

  /**
   * Get component for showing command output. This component
   * is not for pretty output, just for scrollable <pre></pre>.
   * @param string $id      ID for the output component.
   * @param string $class   Classes for the output component.
   * @param string $content Default placeholder content.
   * @return \Seravo\Postbox\Component Simple command output component.
   */
  public static function simple_command_output( $id, $class = '', $content = '' ) {
    return Component::from_raw('<pre id="' . $id . '" class="seravo-simple-command-output ' . $class . '">' . $content . '</pre>');
  }

  /**
   * Get wrapper component to show two components side by side.
   * @param \Seravo\Postbox\Component $left  Left component.
   * @param \Seravo\Postbox\Component $right Right component.
   * @return \Seravo\Postbox\Component Side-by-side component.
   */
  public static function side_by_side( Component $left, Component $right ) {
    $component = new Component();
    $wrapper = new Component('', '<div class="side-by-side-container">', '</div>');

    $left_div = new Component('', '<div>', '</div>');
    $left_div->add_child($left);
    $wrapper->add_child($left_div);

    $right_div = new Component('', '<div>', '</div>');
    $right_div->add_child($right);
    $wrapper->add_child($right_div);

    $component->add_child($wrapper);
    return $component;
  }

  /**
   * Get wrapper component to show multiple components side by side.
   * @param \Seravo\Postbox\Component[] $components Components from left to right.
   * @return \Seravo\Postbox\Component Side-by-side component.
   */
  public static function n_by_side( $components ) {
    $component = new Component();
    $wrapper = new Component('', '<div class="side-by-side-container">', '</div>');

    foreach ( $components as $child_component ) {
      $div = new Component('', '<div>', '</div>');
      $div->add_child($child_component);
      $wrapper->add_child($div);
    }

    $component->add_child($wrapper);
    return $component;
  }

}
