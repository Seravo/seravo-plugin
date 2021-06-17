<?php
/**
 * File for pre-made Postbox for automatically executing a single
 * command and showing the output. Uses Ajax_Auto_Command
 * as the only AJAX handler.
 *
 * Component structure
 * - info paragraph
 * - command output
 */

namespace Seravo\Postbox\Postboxes;

use \Seravo\Postbox\Ajax;
use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;
use \Seravo\Postbox\Postbox;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Postbox_Auto_Command') ) {
  class Postbox_Auto_Command extends Postbox {

    /**
     * @var string $info Info text to display.
     */
    private $info = '';

    /**
     * Constructor for Postbox_Auto_Command. Will be called on new instance.
     * @param string $id      Unique id/slug of the postbox.
     * @param string $context Default admin dashboard context where the postbox should be displayed in.
     */
    public function __construct( $id, $context = 'normal' ) {
      parent::__construct($id, $context);

      $this->set_build_func(
        function ( Component $base ) {
          return $this->build_auto_command($base);
        }
      );

      $ajax_handler = new Ajax\Ajax_Auto_Command($id);
      $this->add_ajax_handler($ajax_handler);
    }

    /**
     * Postbox will be built here.
     * @param \Seravo\Postbox\Component @base Base component to build postbox on.
     */
    public function build_auto_command( Component $base ) {
      $base->add_child(Template::paragraph($this->info));
      $base->add_child($this->ajax_handlers[$this->id]->get_component());
    }

    /**
     * Set info text to display.
     * @param string $text Info text.
     */
    public function set_info_text( $text ) {
      $this->info = $text;
    }

    /**
     * Configure the command to be executed.
     * @param string $command Command to be executed.
     * @param int $cache_time Seconds to cache response for (default is 300).
     * @param bool $allow_failure Whether exit code other than 0 should respond with an error.
     */
    public function set_auto_command( $command, $cache_time = 300, $allow_failure = false ) {
      $ajax_handler = $this->ajax_handlers[$this->id];
      $ajax_handler->set_command($command);
      $ajax_handler->allow_failure($allow_failure);
      $ajax_handler->set_cache_time($cache_time);
    }

  }
}