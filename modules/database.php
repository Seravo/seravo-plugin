<?php
/*
 * Plugin name: Database
 * Description: View database credentials and link to Adminer
 * Version: 1.0
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

require_once dirname(__FILE__) . '/../lib/search-replace-ajax.php';
require_once dirname(__FILE__) . '/../lib/database-ajax.php';

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

      add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_database_scripts' ));

      // Add AJAX endpoints for wp search-replace and database info
      add_action('wp_ajax_seravo_search_replace', 'seravo_ajax_search_replace');
      add_action('wp_ajax_seravo_wp_db_info', 'seravo_ajax_get_wp_db_info');

      seravo_add_postbox(
        'database-access',
        __('Database Access', 'seravo'),
        array( __CLASS__, 'database_access_postbox' ),
        'tools_page_database_page',
        'normal'
      );

      seravo_add_postbox(
        'database-adminer',
        __('Manage the Database with Adminer', 'seravo'),
        array( __CLASS__, 'database_adminer_postbox' ),
        'tools_page_database_page',
        'normal'
      );

      seravo_add_postbox(
        'database-search-replace',
        __('Search-Replace Tool', 'seravo'),
        array( __CLASS__, 'database_search_replace_postbox' ),
        'tools_page_database_page',
        'side'
      );

      seravo_add_postbox(
        'database-size',
        __('Database Size', 'seravo'),
        array( __CLASS__, 'database_size_postbox' ),
        'tools_page_database_page',
        'side'
      );
    }

    /**
     * Register scripts
     *
     * @param string $page hook name
     */
    public static function enqueue_database_scripts( $page ) {

      wp_register_style('seravo_database', plugin_dir_url(__DIR__) . '/style/database.css', '', Helpers::seravo_plugin_version());
      wp_register_script('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js', '', Helpers::seravo_plugin_version(), true);

      if ( $page === 'tools_page_database_page' ) {
        wp_enqueue_style('seravo_database');
        wp_enqueue_script('chart-js');
        wp_enqueue_script('color-hash', plugins_url('../js/color-hash.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('reports-chart', plugins_url('../js/reports-chart.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_script('seravo_database', plugins_url('../js/database.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);

        $loc_translation_database = array(
          'ajaxurl'    => admin_url('admin-ajax.php'),
          'ajax_nonce' => wp_create_nonce('seravo_database'),
        );
        wp_localize_script('seravo_database', 'seravo_database_loc', $loc_translation_database);
      }

    }

    public static function database_access_postbox() {
      ?>
      <p>
        <?php
        // translators: $s example of the command for getting user's database credentials
        printf(__('You can find the database credentials by connecting to your site with SSH and running the command %s. These credentials can be used to connect to the server with an SSH tunnel. You can also use the web-based Adminer available on this page.', 'seravo'), '<code>wp-list-env</code>');
        ?>
      </p>
      <p>
        <?php
        // translators: $s url containing additional information on WordPress database tools
        printf(__('When you have established an SSH connection you can use WP-CLI that features powerful database tools for example exports and imports. <a href="%s">Read the documentation for wp db</a>.', 'seravo'), 'https://developer.wordpress.org/cli/commands/db/');
        ?>
      </p>
      <?php
    }

    public static function database_adminer_postbox() {
      ?>
      <p>
        <?php
        /* translators:
        * %1$s url to www.adminer.org
        */
        printf(__('Adminer is a simple database management tool, like phpMyAdmin. <a href="%1$s">Learn more about Adminer.</a>', 'seravo'), 'https://www.adminer.org');
        ?>
      </p>
      <p>
        <?php
        /* translators:
        * %1$s example url for accessing Adminer in production environment
        * %2$s example url for accessing Adminer in local development
        */
        printf(__('Adminer can be located in the production environment at %1$s and in the local development environment at %2$s.', 'seravo'), '<code>sitename.com/.seravo/adminer</code>', '<code>adminer.sitename.local</code>');
        ?>
      </p>
      <?php
      $adminer_url = '';

      // TODO: test for multisite
      $siteurl = get_site_url();

      if ( 'production' === getenv('WP_ENV') ) {

        // Add trailing slash if missing
        if ( substr($siteurl, -1) !== '/' ) {
          $siteurl .= '/';
        }

        $adminer_url = $siteurl . '.seravo/adminer';

      } else {

        // Add trailing slash if missing
        if ( substr($siteurl, -1) !== '/' ) {
          $siteurl .= '/';
        }

        // Inject subdomain
        $adminer_url = str_replace('//', '//adminer.', $siteurl);

      }

      ?>

      <p class="adminer_button">
        <a href="<?php echo esc_url($adminer_url); ?>" class="button" target="_blank">
          <?php _e('Open Adminer', 'seravo'); ?>
          <span aria-hidden="true" class="dashicons dashicons-external"></span>
        </a>
      </p>
      <?php
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
      <?php if ( exec('which wp') ) : ?>
        <div class="section_chart_mobile">
          <p>
            <div id="seravo_wp_db_info_loading"><img src="/wp-admin/images/spinner.gif"></div>
            <pre><div id="seravo_wp_db_info"></div></pre>
            <div class="pie_container">
              <canvas id="pie_chart" style="width: 10%; height: 4vh;"></canvas>
            </div>
          </p>
        </div>
        <?php
      endif; // end database info
    }
  }

  Database::load();
}
