<?php
/*
 * Plugin name: Database
 * Description: View database credentials and link to Adminer
 * Version: 1.0
 */

namespace Seravo;

use Seravo\Ajax;
use \Seravo\Postbox;
use Seravo\Postbox\Component;
use \Seravo\Postbox\Template;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Requirements;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once SERAVO_PLUGIN_SRC . 'lib/search-replace-ajax.php';
require_once SERAVO_PLUGIN_SRC . 'lib/database-ajax.php';

if ( ! class_exists('Database') ) {
  class Database {

    /**
     * Load database features
     */
    public static function load() {

      if ( ! is_multisite() ) {
        $GLOBALS['sr_networkvisibility'] = false;
        $GLOBALS['sr_alltables'] = true;
      } elseif ( current_user_can('manage_network') ) {
        $GLOBALS['sr_networkvisibility'] = true;
        $GLOBALS['sr_alltables'] = true;
      } else {
        $GLOBALS['sr_networkvisibility'] = false;
        $GLOBALS['sr_alltables'] = false;
      }

      $page = new Toolpage('tools_page_database_page');
      self::init_database_postboxes($page);

      $page->enable_ajax();
      $page->register_page();

      add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_database_scripts' ));

      // Add AJAX endpoints for wp search-replace, database info and database cleanup
      add_action('wp_ajax_seravo_search_replace', 'Seravo\seravo_ajax_search_replace');
      add_action('wp_ajax_seravo_wp_db_info', 'Seravo\seravo_ajax_get_wp_db_info');
    }

    public static function init_database_postboxes( Toolpage $page ) {
      /**
       * Database access info postbox
       */
      $database_access = new Postbox\InfoBox('database-access');
      $database_access->set_title(__('Database Access', 'seravo'));
      $database_access->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
      $database_access->add_paragraph(__('You can find the database credentials by connecting to your site with SSH and running the command <code>wp-list-env</code>. These credentials can be used to connect to the server with an SSH tunnel. You can also use the web-based Adminer available on this page.', 'seravo'));
      $database_access->add_paragraph(__('When you have established an SSH connection you can use WP-CLI that features powerful database tools for example exports and imports. <a href="https://developer.wordpress.org/cli/commands/db/" target="_BLANK">Read the documentation for wp db</a>.', 'seravo'));
      $page->register_postbox($database_access);

      /**
       * Database adminer info postbox
       */
      $adminer = new Postbox\Postbox('database-adminer');
      $adminer->set_title(__('Manage the Database with Adminer', 'seravo'));
      $adminer->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
      $adminer->set_build_func(array( __CLASS__, 'build_adminer_postbox' ));
      $page->register_postbox($adminer);

      /**
       * Search & Replace tool postbox
       * To be implemented...
       */
      $search_replace = new Postbox\Postbox('database-search-replace');
      $search_replace->set_title(__('Search-Replace Tool', 'seravo'));
      $search_replace->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
      $search_replace->set_build_func(array( __CLASS__, 'database_search_replace_postbox' ));
      $page->register_postbox($search_replace);

      /**
       * Database cleanup tool postbox
       */
      $cleanup = new Postbox\Postbox('database-cleanup');
      $cleanup->set_title(__('Database Cleanup Tool', 'seravo'));
      $cleanup->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
      $cleanup->set_build_func(array( __CLASS__, 'build_database_cleanup' ));
      self::init_cleanup_ajax_scripts($cleanup);
      $page->register_postbox($cleanup);

      /**
       * Database size postbox
       * To be implemented...
       */
      $size = new Postbox\Postbox('database-size');
      $size->set_title(__('Database Size', 'seravo'));
      $size->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
      $size->set_build_func(array( __CLASS__, 'database_size_postbox' ));
      $page->register_postbox($size);
    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function enqueue_database_scripts( $page ) {

      wp_register_style('seravo_database', SERAVO_PLUGIN_URL . 'style/database.css', '', Helpers::seravo_plugin_version());
      wp_register_script('apexcharts-js', 'https://cdn.jsdelivr.net/npm/apexcharts', '', Helpers::seravo_plugin_version(), true);

      if ( $page === 'tools_page_database_page' ) {
        wp_enqueue_style('seravo_database');
        wp_enqueue_script('apexcharts-js');
        wp_enqueue_script('color-hash', SERAVO_PLUGIN_URL . 'js/color-hash.js', array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('reports-chart', SERAVO_PLUGIN_URL . 'js/reports-chart.js', array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('seravo_database', SERAVO_PLUGIN_URL . 'js/database.js', array( 'jquery' ), Helpers::seravo_plugin_version(), false);

        $loc_translation_database = array(
          'ajaxurl'    => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('seravo_database'),
        );
        wp_localize_script('seravo_database', 'seravo_database_loc', $loc_translation_database);
      }

    }

    /**
     * Helper method for initializing cleanup & optimize postbox tools.
     * @param Postbox $cleanup Postbox on which the commands and ajax are initialized.
     */
    public static function init_cleanup_ajax_scripts( Postbox\Postbox $cleanup ) {
      // Initialize optimize section
      $optimize = new Ajax\SimpleCommand('optimize-db');

      // define the db credentials for wp-db-optimize
      $db_info = defined('DB_NAME') ? 'DB_NAME=' . DB_NAME . ' ' : '';
      $db_info .= defined('DB_HOST') ? 'DB_HOST=' . DB_HOST . ' ' : '';
      $db_info .= defined('DB_USER') ? 'DB_USER=' . DB_USER . ' ' : '';
      $db_info .= defined('DB_PASSWORD') ? 'DB_PASSWORD=' . DB_PASSWORD . ' ' : '';
      $db_optimize_command = $db_info . 'wp-db-optimize 2>&1';

      $optimize->set_command($db_optimize_command, null, true);
      $optimize->set_button_text(__('Optimize', 'seravo'));
      $optimize->set_spinner_text(__('Optimizing database...', 'seravo'));

      // Initialize cleanup section
      $cleanup_command = new Ajax\SimpleCommand('cleanup-db');
      $cleanup_command->set_command('wp-backup && wp-db-cleanup 2>&1', 'wp-db-cleanup --dry-run', false);
      $cleanup_command->set_button_text(__('Run wp-db-cleanup', 'seravo'), __('Do a dry run', 'seravo'));
      $cleanup_command->set_empty_message(__('Nothing to be cleaned up', 'seravo'));

      $cleanup->add_ajax_handler($optimize);
      $cleanup->add_ajax_handler($cleanup_command);
    }

    /**
     * Build the database cleanup and optimize postbox.
     * @param Component $base The postbox base component.
     * @param Postbox $postbox To fetch the AJAX components from.
     */
    public static function build_database_cleanup( Component $base, Postbox\Postbox $postbox ) {
      $base->add_child(Template::section_title(__('Optimization', 'seravo')));
      $base->add_child(Template::paragraph(__('You can use this tool to run <code>wp-db-optimize</code>. The command optimizes WordPress database and thus reduces database disk usage.', 'seravo')));
      $base->add_child($postbox->get_ajax_handler('optimize-db')->get_component());

      $base->add_child(Template::section_title(__('Cleanup', 'seravo')));
      $base->add_child(Template::paragraph(__('You can use this tool to run <code>wp-db-cleanup</code>. For safety reason a dry run is compulsory before the actual cleanup can be done.', 'seravo')));
      $base->add_child($postbox->get_ajax_handler('cleanup-db')->get_component());
    }

    /**
     * Build the Adminer info postbox.
     * @param Component $base The base component to add content.
     */
    public static function build_adminer_postbox( Component $base ) {
      $base->add_child(Template::paragraph(__('<a href="https://www.adminer.org" target="_BLANK">Adminer</a> is a visual database management tool, which is simpler and safer than its competitor phpMyAdmin.', 'seravo')));
      $base->add_child(Template::paragraph(__('At Seravo it can always be accessed at <code>example.com/.seravo/adminer</code>', 'seravo')));

      $button = Template::button_link_with_icon(Helpers::adminer_link(), __('Open Adminer', 'seravo'));
      $button->set_wrapper('<p class="adminer_button">', '</p>');
      $base->add_child($button);
    }

    public static function database_search_replace_postbox() {
      ?>
      <?php if ( exec('which wp') && apply_filters('seravo_search_replace', true) ) : ?>
        <p> <?php _e('You can use this tool to run <code>wp search-replace</code>. For safety reason a dry run is compulsory before the actual search-replace can be done.', 'seravo'); ?></p>
        <div class="sr-navbar">
          <span class="label_buttons"><label class="from_label" for="sr-from"><?php _e('From:', 'seravo'); ?></label> <input type="text" id="sr-from" value=""></span><br>
          <span class="label_buttons to_button"><label class="to_label" for="sr-to"><?php _e('To:', 'seravo'); ?></label> <input type="text" id="sr-to" value=""></span>
          <!-- To add new arbitrary option put it below. Use class optionbox
              Custom options will be overriden upon update -->
          <ul class="optionboxes">
              <li class="sr_option">
                <input type="checkbox" id="skip_backup" class="optionbox">
                <label for="skip_backup"><?php _e('Skip backups', 'seravo'); ?></label>
              </li>
            <?php if ( $GLOBALS['sr_alltables'] ) : ?>
              <li class="sr_option">
                <input type="checkbox" id="all_tables" class="optionbox">
                <label for="all_tables"><?php _e('All tables', 'seravo'); ?></label>
              </li>
            <?php endif; ?>
            <?php if ( $GLOBALS['sr_networkvisibility'] ) : ?>
              <li class="sr_option">
                <input type="checkbox" id="network" class="optionbox">
                <label for="network"><?php _e('Network', 'seravo'); ?></label>
              </li>
            <?php endif; ?>
          </ul>
          <div class="datab_buttons">
            <button id="sr-drybutton" class="button sr-button"> <?php _e('Do a dry run', 'seravo'); ?> </button>
            <button id="sr-button" class="button sr-button" disabled> <?php _e('Run wp search-replace', 'seravo'); ?> </button>
          </div>
        </div>
        <div id="search_replace_loading"><img class="hidden" src="/wp-admin/images/spinner.gif"></div>
        <div id="search_replace_command"></div>
        <table id="search_replace"></table>
        <?php
      endif;
    }

    public static function database_size_postbox() {
      ?>
      <?php if ( exec('which wp') !== '' ) : ?>
        <div class="section_chart_mobile">
          <p>
            <div class="seravo_wp_db_info_loading"><img src="/wp-admin/images/spinner.gif"></div>
            <div id="seravo_wp_db_info"></div>
            <hr>
            <b>
              <?php _e('Table sizes', 'seravo'); ?>
            </b>
            <div class="seravo_wp_db_info_loading"><img src="/wp-admin/images/spinner.gif"></div>
            <div class="chart_container">
              <div id="bars_single"></div>
            </div>
          </p>
        </div>

        <div class='seravo-database-detail-wrapper'>
          <div class='seravo_database_detail'>
            <?php _e('Details about database table sizes', 'seravo'); ?>
          </div>
          <div class='seravo-database-detail hidden'>
            <table class="database_detail_table" id="long_postmeta_values">
              <?php _e('Longest wp_postmeta values:', 'seravo'); ?>
            </table>
            <hr>
            <table class="database_detail_table" id="cumulative_postmeta_sizes">
              <?php _e('Cumulative size of meta_value per meta_key:', 'seravo'); ?>
            </table>
            <hr>
            <table class="database_detail_table" id="common_postmeta_values">
              <?php _e('Rows per meta_key:', 'seravo'); ?>
            </table>
            <hr>
            <table class="database_detail_table" id="autoload_option_count">
              <?php _e('Autoload options count (read to memory on each WP page load):', 'seravo'); ?>
            </table>
            <hr>
            <table class="database_detail_table" id="total_autoload_option_size">
              <?php _e('Autoload options total size of values:', 'seravo'); ?>
            </table>
            <hr>
            <table class="database_detail_table" id="long_autoload_option_values">
              <?php _e('Longest autoloaded wp_option values:', 'seravo'); ?>
            </table>
            <hr>
            <table class="database_detail_table" id="common_autoload_option_values">
              <?php _e('Most common autoloaded wp_option values:', 'seravo'); ?>
            </table>
          </div>
          <div class='seravo_database_detail_show_more_wrapper'>
            <a href='#' class='seravo_database_detail_show_more'><?php _e('Toggle Details', 'seravo'); ?>
              <div class='dashicons dashicons-arrow-down-alt2' id='seravo_arrow_database_detail_show_more'></div>
            </a>
          </div>
        </div>
        <?php
      endif; // end database info
    }
  }

  Database::load();
}
