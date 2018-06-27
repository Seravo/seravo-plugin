<?php
/*
 * Plugin name: Cruft files
 * Description: View and remove cruft files found in filesystem
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname( __FILE__ ) . '/../lib/cruftfiles-ajax.php';
require_once dirname( __FILE__ ) . '/../lib/cruftplugins-ajax.php';

if ( ! class_exists('Cruftfiles') ) {
  class Cruftfiles {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_cruftfiles_page' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_cruftfiles_scripts' ) );

      // AJAX functionality for listing and deleting files
      add_action( 'wp_ajax_seravo_cruftfiles', 'seravo_ajax_list_cruft_files' );
      add_action( 'wp_ajax_seravo_delete_file', 'seravo_ajax_delete_cruft_files' );

      // AJAX functionality for listing ... plugins
      add_action( 'wp_ajax_seravo_list_cruft_plugins', 'seravo_ajax_list_cruft_plugins' );
      add_action( 'wp_ajax_seravo_remove_plugins', 'seravo_ajax_remove_plugins');
    }

    public static function register_cruftfiles_page() {
      add_submenu_page(
        'tools.php', __( 'Cruft Files', 'seravo' ),
        __( 'Cruft Files', 'seravo' ), 'manage_options', 'cruftfiles_page',
        array( __CLASS__, 'load_cruftfiles_page' )
      );
    }

    public static function load_cruftfiles_page() {
      require_once dirname( __FILE__ ) . '/../lib/cruftfiles-page.php';
    }

    /**
     * $_POST['deletefile'] is either a string denoting only one file
     * or it can contain an array containing strings denoting files.
     */
    public static function ajax_delete_file() {
      if ( isset($_POST['deletefile']) && ! empty($_POST['deletefile']) ) {
        $files = $_POST['deletefile'];
        if ( is_string($files) ) {
          $files = array( $files );
        }
        if ( ! empty($files) ) {
          $result = array();
          $results = array();
          foreach ( $files as $file ) {
            $legit_cruft_files = get_transient('cruft_files_found'); // Check first that given file or directory is legitimate
            if ( in_array( $file, $legit_cruft_files, true ) ) {
              if ( is_dir($file) ) {
                $unlink_result = self::rmdir_recursive($file, 0);
              } else {
                $unlink_result = unlink($file);
              }
              // else - Backwards compatible with old UI
              $result['success'] = (bool) $unlink_result;
              $result['filename'] = $file;
              array_push( $results, $result );
            }
          }
          echo json_encode($results);
        }
      }
      wp_die();
    }

    public static function rmdir_recursive( $dir, $recursive ) {
      foreach ( scandir($dir) as $file ) {
        if ( '.' === $file || '..' === $file ) {
          continue; // Skip current and upper level directories
        }
        if ( is_dir("$dir/$file") ) {
          rmdir_recursive("$dir/$file", 1);
        } else {
          unlink("$dir/$file");
        }
      }
      rmdir($dir);
      if ( $recursive == 0 ) {
        return true; // when not called recursively
      }
    }

    public static function enqueue_cruftfiles_scripts( $hook ) {
      wp_register_style( 'seravo_cruftfiles', plugin_dir_url( __DIR__ ) . '/style/cruftfiles.css' );
      wp_register_script( 'seravo_cruftfiles', plugin_dir_url( __DIR__ ) . '/js/cruftfiles.js' );
      wp_register_script( 'seravo_cruftplugins', plugin_dir_url( __DIR__ ) . '/js/cruftplugins.js' );

      if ( $hook === 'tools_page_cruftfiles_page' ) {
        wp_enqueue_style( 'seravo_cruftfiles' );
        wp_enqueue_script( 'seravo_cruftfiles' );
        wp_enqueue_script( 'seravo_cruftplugins' );

        // Localize the javascript file.
        $loc_translation_files = array(
          'no_data'       => __( 'No data returned for section.', 'seravo' ),
          'confirm'       => __( 'Are you sure you want to proceed? Deleted files can not be recovered.', 'seravo' ),
          'fail'          => __( 'Failed to load. Please try again.', 'seravo' ),
          'no_cruftfiles' => __( 'Congratulations! You have no any cruft around.', 'seravo' ),
        );
        $loc_translation_plugins = array(
          'inactive'                => __( 'Inactive plugins:', 'seravo' ),
          'inactive_desc'           => __( 'These plugins are currently not in use. They can be removed to save disk storage.'),
          'cache_plugins'           => __( 'Unnecessary cache plugins:', 'seravo' ),
          'cache_plugins_desc'      => __( 'Your website runs on a server which has serverside caching. Any plugins that provide caching can not improve upon the provided service.', 'seravo' ),
          'security_plugins'        => __( 'Unnecessary security plugins:', 'seravo' ),
          'security_plugins_desc'   => __( 'Your website runs on a server which has been configured to a high level of security. Any plugins providing security services only slow your website down.', 'seravo' ),
          'db_plugins'              => __( 'Unnecessary database manipulating plugins:', 'seravo' ),
          'db_plugins_desc'         => __( 'These plugins may cause issues with your database.', 'seravo' ),
          'backup_plugins'          => __( 'Unnecessary backup plugins:', 'seravo' ),
          'backup_plugins_desc'     => __( 'Backups of your website are taken automatically on the server daily. Any plugins creating backups are redundant and unnecessesarily fill up data storage.', 'seravo' ),
          'poor_security'           => __( 'Plugins that are not very secure:', 'seravo' ),
          'poor_security_desc'      => __( 'These plugins are known to have issues with security.', 'seravo' ),
          'no_cruftplugins'         => __( 'All plugins are currently active and approved.', 'seravo' ),
          'cruftplugins'            => __( 'The following plugins have been found and are suggested for removal', 'seravo' ),
          'confirm'                 => __( 'Are you sure you want to remove this plugin?', 'seravo'),
          'failure'                 => __( 'Failed to remove plugin', 'seravo'),
        );
        wp_localize_script( 'seravo_cruftfiles', 'seravo_cruftfiles_loc', $loc_translation_files );
        wp_localize_script( 'seravo_cruftplugins', 'seravo_cruftplugins_loc', $loc_translation_plugins );
      }
    }
  }

  cruftfiles::load();
}
