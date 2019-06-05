<?php
/*
 * Plugin name: Mails
 * Description: ---
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Mails') ) {

  class Mails {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_mails_page' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_styles' ) );

      seravo_add_postbox(
        'mail-forwards',
        __('Mails', 'seravo') . ' (beta)',
        array( __CLASS__, 'mails_postbox' ),
        'tools_page_mails_page',
        'normal'
      );

    }

    public static function register_mails_page() {
      add_submenu_page(
        'tools.php',
        __('Mails', 'seravo'),
        __('Mails', 'seravo'),
        'manage_options',
        'mails_page',
        'Seravo\seravo_postboxes_page'
      );
    }

    /**
     * Enqueues styles and scripts for the admin tools page
     *
     * @param mixed $hook
     * @access public
     * @return void
     */
    public static function admin_enqueue_styles( $hook ) {
      wp_register_style( 'mails_page', plugin_dir_url( __DIR__ ) . '/style/mails.css', '', Helpers::seravo_plugin_version() );
      wp_register_script( 'mails_page', plugin_dir_url( __DIR__ ) . '/js/mails.js', '', Helpers::seravo_plugin_version() );

      if ( $hook === 'tools_page_mails_page' ) {
        wp_enqueue_style( 'mails_page' );
        wp_enqueue_script( 'mails_page' );
      }
    }

    public static function mails_postbox() {
      require_once dirname( __FILE__ ) . '/../lib/mails-page.php';

      ?>
      <div class="mails-postbox">
        <form action="#" method="get" style="width: 100%; margin-bottom: 10px;">
          <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>"/>
          <?php list_domains(); ?>
        </form>
        <form>
          <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>"/>
          <?php
          if ( ! empty ( $_GET['domain'] ) ) {
            display_forwards_table();
          }
          ?>
        </form>
      </div>
      <?php
    }

  }

  /* Only show emails page in production */
  if ( Helpers::is_production() ) {
    Mails::load();
  }
}
