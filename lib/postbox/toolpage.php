<?php
/**
 * File for Seravo Toolpage. Seravo Toolpage
 * contains Seravo Postboxes.
 */

namespace Seravo\Postbox;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Toolpage') ) {
  class Toolpage {

    /**
     * @var string Admin screen id where the page should be displayed in.
     */
    private $screen;

    /**
     * @var \Seravo\Postbox\Postbox[] Postboxes registered on the page.
     */
    private $postboxes = array();

    /**
     * Constructor for Toolpage. Will be called on new instance.
     * @param string $screen Admin screen id where the page should be displayed in.
     */
    public function __construct( $screen = '' ) {
      $this->screen = $screen;
    }

    /**
     * Enables AJAX features for this page. This must be called
     * on the page if there's even a single postbox using AJAX.
     */
    public function enable_ajax() {
      // Generates WordPress nonce for this page
      // and prints it as JavaScipt variable inside <SCRIPT>.
      add_action(
        'before_seravo_postboxes_' . $this->screen,
        function() {
          $nonce = wp_create_nonce($this->screen);
          echo "<script>SERAVO_AJAX_NONCE = \"{$nonce}\";</script>";
        }
      );
    }

    /**
     * Register postbox to be shown on the page. The same postbox
     * instance shouldn't be used elsewhere without clone.
     * @param \Seravo\Postbox\Postbox $postbox Postbox to be registered.
     */
    public function register_postbox( Postbox $postbox ) {
      $postbox->on_page_assign($this->screen);
      $this->postboxes[] = $postbox;
    }

    /**
     * Register the page to be rendered. This should be called once
     * the page is ready and all the postboxes are added.
     * @param \Seravo\Postbox\Postbox $postbox
     */
    public function register_page() {
      foreach ( $this->postboxes as $postbox ) {
        if ( $postbox->_is_allowed() ) {
          seravo_add_postbox($this->screen, $postbox);
        }
      }
    }

  }
}
