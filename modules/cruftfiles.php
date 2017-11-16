<?php
/*
 * Plugin name: Cruft files
 * Description: View and edit domains and DNS
 * Version: 1.0
 */

namespace Seravo;

if ( ! class_exists('Cruftfiles') ) {
  class Cruftfiles {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_cruftfiles_page' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_cruftfiles_scripts' ) );
      add_action('wp_ajax_seravo_cruftfiles', function() {
          require_once(dirname( __FILE__ ) . '/../lib/cruftfiles-ajax.php');
          wp_die();
      });
      add_action( 'wp_ajax_seravo_delete_file', array( __CLASS__, 'ajax_delete_file' ) );
    }

    public static function register_cruftfiles_page() {
      add_submenu_page( 'tools.php', 'Cruft files', 'Cruft files', 'manage_options', 'cruftfiles_page', array( __CLASS__, 'load_cruftfiles_page' ) );
    }

    public static function load_cruftfiles_page() {
      require_once(dirname( __FILE__ ) . '/../lib/cruftfiles-page.php');
    }

    public static function ajax_delete_file() {
      if ( isset($_POST['deletefile']) && ! empty($_POST['deletefile']) ) {
        $file = $_POST['deletefile'];
        $result = array();
        $unlink_result = unlink($file);
        $result['success'] = (bool) $unlink_result;
        echo json_encode($result);
      }
      wp_die();
    }

    public static function enqueue_cruftfiles_scripts( $hook ) {
      wp_register_style( 'seravo_cruftfiles', plugin_dir_url( __DIR__ ) . '/style/cruftfiles.css' );
      wp_register_script( 'seravo_cruftfiles', plugin_dir_url( __DIR__ ) . '/js/cruftfiles.js' );

      if ( $hook === 'tools_page_cruftfiles_page' ) {
        wp_enqueue_style( 'seravo_cruftfiles' );
        wp_enqueue_script( 'seravo_cruftfiles' );

        // Localize the javascript file.
        $loc_translation = array(
            'no_data' => __( 'No data returned for section.', 'seravo' ),
             'confirm' => __( 'Are you sure you want to proceed? Deleted files can not be recovered.', 'seravo' ),
             'fail' => __( 'Failed to load. Please try again.', 'seravo' ),
        );
        wp_localize_script( 'seravo_cruftfiles', 'seravo_cruftfiles_loc', $loc_translation );
      }
    }
  }

  cruftfiles::load();
}
