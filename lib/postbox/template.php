<?php
/**
 * File for Seravo postbox component templates.
 */

namespace Seravo\Postbox;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Template') ) {
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

  }
}
