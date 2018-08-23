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
require_once dirname( __FILE__ ) . '/../lib/cruftthemes-ajax.php';

if ( ! class_exists('Cruftfiles') ) {
  class Cruftfiles {

    public static function load() {
      add_action( 'admin_menu', array( __CLASS__, 'register_cruftfiles_page' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_cruftfiles_scripts' ) );

      // AJAX functionality for listing and deleting files
      add_action( 'wp_ajax_seravo_cruftfiles', 'seravo_ajax_list_cruft_files' );
      add_action( 'wp_ajax_seravo_delete_file', 'seravo_ajax_delete_cruft_files' );

      // AJAX functionality for listing and removing plugins
      add_action( 'wp_ajax_seravo_list_cruft_plugins', 'seravo_ajax_list_cruft_plugins' );
      add_action( 'wp_ajax_seravo_remove_plugins', 'seravo_ajax_remove_plugins' );

      // AJAX functionality for listing and removing themess
      add_action( 'wp_ajax_seravo_list_cruft_themes', 'seravo_ajax_list_cruft_themes' );
      add_action( 'wp_ajax_seravo_remove_themes', 'seravo_ajax_remove_themes' );

      // Add HTTP request stats postbox
      seravo_add_postbox(
        'cruft-files',
        __('Cruft Files (beta)', 'seravo'),
        array( __CLASS__, 'cruftfiles_postbox' ),
        'tools_page_cruftfiles_page',
        'normal'
      );

      // Add cache status postbox
      seravo_add_postbox(
        'cruft-plugins',
        __('Unnecessary plugins', 'seravo'),
        array( __CLASS__, 'cruftplugins_postbox' ),
        'tools_page_cruftfiles_page',
        'side'
      );

      // Add cache status postbox
      seravo_add_postbox(
        'cruft-themes',
        __('Unnecessary themes', 'seravo'),
        array( __CLASS__, 'cruftthemes_postbox' ),
        'tools_page_cruftfiles_page',
        'side'
      );
    }

    public static function register_cruftfiles_page() {
      add_submenu_page(
        'tools.php', __( 'Cruft Files', 'seravo' ),
        __( 'Cruft Files', 'seravo' ), 'manage_options', 'cruftfiles_page',
        'Seravo\seravo_postboxes_page'
      );
    }

    public static function cruftfiles_postbox() {
      ?>
      <p>
        <?php _e( 'Find and delete unnecessary files in the filesystem', 'seravo' ); ?>
      </p>
      <p>
        <div id="cruftfiles_status">
          <table>
            <tbody id="cruftfiles_entries">
            </tbody>
          </table>
          <div id="cruftfiles_status_loading">
            <?php _e( 'Finding files...', 'seravo' ); ?>
            <img src="/wp-admin/images/spinner.gif">
          </div>
        </div>
      </p>
      <?php
    }

    public static function cruftplugins_postbox() {
      ?>
      <p>
        <?php _e( 'Find and remove plugins that are unnecessary or inactive. For more information, read our <a href="https://help.seravo.com/en/knowledgebase/19-teemat-ja-lisaosat/docs/51-wordpress-lisaosat-wp-palvelu-fi-ssa">Helpy-page</a>.', 'seravo' ); ?>
      </p>
      <p>
        <div id="cruftplugins_status">
          <div id="cruftplugins_status_loading">
            <?php _e( 'Finding plugins...', 'seravo' ); ?>
            <img src="/wp-admin/images/spinner.gif">
          </div>
        </div>
      </p>
      <?php
    }

    public static function cruftthemes_postbox() {
      ?>
      <p>
        <?php _e( 'Find and remove themes that are unnecessary or inactive.', 'seravo' ); ?>
      </p>
      <p>
        <div id="cruftthemes_status">
          <div id="cruftthemes_status_loading">
            <?php _e( 'Finding themes...', 'seravo' ); ?>
            <img src="/wp-admin/images/spinner.gif">
          </div>
        </div>
      </p>
      <?php
    }

    /**
     * $_POST['deletefile'] is either a string denoting only one file
     * or it can contain an array containing strings denoting files.
     */
    public static function ajax_delete_file() {
      check_ajax_referer( 'seravo_cruftfiles', 'nonce' );
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
      wp_register_style( 'seravo_cruftfiles', plugin_dir_url( __DIR__ ) . '/style/cruftfiles.css', '', Helpers::seravo_plugin_version());
      wp_register_script( 'seravo_cruftfiles', plugin_dir_url( __DIR__ ) . '/js/cruftfiles.js', '', Helpers::seravo_plugin_version());
      wp_register_script( 'seravo_cruftplugins', plugin_dir_url( __DIR__ ) . '/js/cruftplugins.js', '', Helpers::seravo_plugin_version());
      wp_register_script( 'seravo_cruftthemes', plugin_dir_url( __DIR__ ) . '/js/cruftthemes.js', '', Helpers::seravo_plugin_version());

      if ( $hook === 'tools_page_cruftfiles_page' ) {
        wp_enqueue_style( 'seravo_cruftfiles' );
        wp_enqueue_script( 'seravo_cruftfiles' );
        wp_enqueue_script( 'seravo_cruftplugins' );
        wp_enqueue_script( 'seravo_cruftthemes' );

        // Localize the javascript file.
        $loc_translation_files = array(
          'no_data'       => __( 'No data returned for section.', 'seravo' ),
          'confirm'       => __( 'Are you sure you want to proceed? Deleted files can not be recovered.', 'seravo' ),
          'fail'          => __( 'Failed to load. Please try again.', 'seravo' ),
          'no_cruftfiles' => __( 'Congratulations! You have no any cruft around.', 'seravo' ),
          'delete'        => __( 'Delete', 'seravo' ),
          'bytes'         => __( 'b', 'seravo' ),
          'mod_date'      => __( 'Last modified', 'seravo' ),
          'select_all'    => __( 'Select all files', 'seravo' ),
          'filesize'      => __( 'Filesize', 'seravo' ),
          'ajaxurl'       => admin_url('admin-ajax.php'),
          'ajax_nonce'    => wp_create_nonce('seravo_cruftfiles'),
        );
        $loc_translation_plugins = array(
          'inactive'                => __( 'Inactive plugins:', 'seravo' ),
          'inactive_desc'           => __( 'These plugins are currently not in use. They can be removed to save disk storage.', 'seravo' ),
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
          'confirm'                 => __( 'Are you sure you want to remove this plugin?', 'seravo' ),
          'failure'                 => __( 'Failed to remove plugin', 'seravo' ),
          'ajaxurl'                 => admin_url('admin-ajax.php'),
          'ajax_nonce'              => wp_create_nonce('seravo_cruftplugins'),
        );
        $loc_translation_themes = array(
          'isparentto'     => __( 'is parent to: ', 'seravo' ),
          'confirm'        => __( 'Are you sure you want to remove this theme?', 'seravo' ),
          'failure'        => __( 'Failed to remove theme', 'seravo' ),
          'no_cruftthemes' => __( 'There are currently no unnecessary themes on the website.', 'seravo' ),
          'cruftthemes'    => __( 'The following themes are inactive and can be removed.', 'seravo' ),
          'ajaxurl'        => admin_url('admin-ajax.php'),
          'ajax_nonce'     => wp_create_nonce('seravo_cruftthemes'),

        );
        wp_localize_script( 'seravo_cruftfiles', 'seravo_cruftfiles_loc', $loc_translation_files );
        wp_localize_script( 'seravo_cruftplugins', 'seravo_cruftplugins_loc', $loc_translation_plugins );
        wp_localize_script( 'seravo_cruftthemes', 'seravo_cruftthemes_loc', $loc_translation_themes );
      }
    }
  }

  cruftfiles::load();
}
