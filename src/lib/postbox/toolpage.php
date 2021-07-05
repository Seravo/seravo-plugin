<?php

namespace Seravo\Postbox;

/**
 * Class Toolpage
 *
 * Toolpage is a simple way to handle a postbox screen as
 * it takes care of the common features like nonces.
 */
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
   * Enables chart features for this page. This must be called
   * on the page if there's even a single postbox using charts.
   */
  public function enable_charts() {
    add_action(
      'admin_enqueue_scripts',
      function( $page ) {
      if ( $page !== $this->screen ) {
          return;
      }

        wp_enqueue_script('apexcharts-js', SERAVO_PLUGIN_URL . 'js/lib/apexcharts.js', '', \Seravo\Helpers::seravo_plugin_version(), true);
        wp_enqueue_script('seravo-charts', SERAVO_PLUGIN_URL . 'js/charts.js', array( 'jquery' ), \Seravo\Helpers::seravo_plugin_version(), false);

        $charts_l10n = array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'show_more' => __('Show more', 'seravo'),
          'show_less' => __('Show less', 'seravo'),
          'used' => __('Used', 'seravo'),
          'available' => __('Available', 'seravo'),
        );
        wp_localize_script('seravo_ajax', 'seravo_charts_l10n', $charts_l10n);
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
