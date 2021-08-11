<?php

namespace Seravo\Page;

use \Seravo\Shell;
use \Seravo\Helpers;
use \Seravo\Compatibility;

use \Seravo\Ajax;
use \Seravo\Ajax\AjaxResponse;
use \Seravo\Ajax\SimpleCommand;

use \Seravo\Postbox;
use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Requirements;

/**
 * Class Database
 *
 * Database is a page for info
 * and management of the database.
 */
class Database extends Toolpage {

  /**
   * @var \Seravo\Page\Database|null Instance of this page.
   */
  private static $instance;

  /**
   * Function for creating an instance of the page. This should be
   * used instead of 'new' as there can only be one instance at a time.
   * @return \Seravo\Page\Database Instance of this page.
   */
  public static function load() {
    if ( self::$instance === null ) {
      self::$instance = new Database();
    }

    return self::$instance;
  }

  /**
   * Constructor for Database. Will be called on new instance.
   * Basic page details are given here.
   */
  public function __construct() {
    parent::__construct(
      \__('Database', 'seravo'),
      'tools_page_database_page',
      'database_page',
      'Seravo\Postbox\seravo_postboxes_page'
    );
  }

  /**
   * Will be called for page initialization. Includes scripts
   * and enables toolpage features needed for this page.
   */
  public function init_page() {
    self::init_postboxes($this);

    $this->enable_charts();
    $this->enable_ajax();
  }

  /**
   * Will be called for setting requirements. The requirements
   * must be as strict as possible but as loose as the
   * postbox with the loosest requirements on the page.
   * @param \Seravo\Postbox\Requirements $requirements Instance to set requirements to.
   */
  public function set_requirements( Requirements $requirements ) {
    $requirements->can_be_production = \true;
    $requirements->can_be_staging = \true;
    $requirements->can_be_development = \true;
  }

  /**
   * @return void
   */
  public static function init_postboxes( Toolpage $page ) {
    /**
     * Database access info postbox
     */
    $database_access = new Postbox\InfoBox('database-access');
    $database_access->set_title(\__('Database Access', 'seravo'));
    $database_access->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
    $database_access->add_paragraph(\__('You can find the database credentials by connecting to your site with SSH and running the command <code>wp-list-env</code>. These credentials can be used to connect to the server with an SSH tunnel. You can also use the web-based Adminer available on this page.', 'seravo'));
    $database_access->add_paragraph(\__('When you have established an SSH connection you can use WP-CLI that features powerful database tools for example exports and imports. <a href="https://developer.wordpress.org/cli/commands/db/" target="_BLANK">Read the documentation for wp db</a>.', 'seravo'));
    $page->register_postbox($database_access);

    /**
     * Database adminer info postbox
     */
    $adminer = new Postbox\Postbox('database-adminer');
    $adminer->set_title(\__('Manage the Database with Adminer', 'seravo'));
    $adminer->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
    $adminer->set_build_func(array( __CLASS__, 'build_adminer_postbox' ));
    $page->register_postbox($adminer);

    /**
     * Search & Replace tool postbox
     */
    $search_replace = new Postbox\SimpleForm('database-search-replace');
    $search_replace->set_title(\__('Search-Replace Tool', 'seravo'));
    $search_replace->set_button_text(\__('Run wp search-replace', 'seravo'), \__('Do a dry run', 'seravo'));
    $search_replace->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $search_replace->set_ajax_func(array( __CLASS__, 'execute_search_replace' ));
    $search_replace->set_build_form_func(array( __CLASS__, 'build_search_replace_postbox' ));
    $page->register_postbox($search_replace);

    /**
     * Database cleanup tool postbox
     */
    $cleanup = new Postbox\Postbox('database-cleanup');
    $cleanup->set_title(\__('Database Cleanup Tool', 'seravo'));
    $cleanup->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
    $cleanup->set_build_func(array( __CLASS__, 'build_database_cleanup' ));
    self::init_cleanup_ajax_scripts($cleanup);
    $page->register_postbox($cleanup);

    /**
     * Database size postbox
     */
    $db_size = new Postbox\Postbox('database-size');
    $db_size->set_title(\__('Database Size', 'seravo'));
    $db_size->set_requirements(array( Requirements::CAN_BE_PRODUCTION => true ));
    $db_size->set_build_func(array( __CLASS__, 'build_database_size' ));
    self::init_database_size_scripts($db_size);
    $page->register_postbox($db_size);
  }

  /**
   * Helper method for initializing cleanup & optimize postbox tools.
   * @param \Seravo\Postbox\Postbox $cleanup Postbox on which the commands and ajax are initialized.
   * @return void
   */
  public static function init_cleanup_ajax_scripts( Postbox\Postbox $cleanup ) {
    // Initialize optimize section
    $optimize = new Ajax\SimpleCommand('optimize-db');

    // define the db credentials for wp-db-optimize
    $db_info = \defined('DB_NAME') ? 'DB_NAME=' . DB_NAME . ' ' : '';
    $db_info .= \defined('DB_HOST') ? 'DB_HOST=' . DB_HOST . ' ' : '';
    $db_info .= \defined('DB_USER') ? 'DB_USER=' . DB_USER . ' ' : '';
    $db_info .= \defined('DB_PASSWORD') ? 'DB_PASSWORD=' . DB_PASSWORD . ' ' : '';
    $db_optimize_command = $db_info . 'wp-db-optimize 2>&1';

    $optimize->set_command($db_optimize_command, null, true);
    $optimize->set_button_text(\__('Optimize', 'seravo'));
    $optimize->set_spinner_text(\__('Optimizing database...', 'seravo'));

    // Initialize cleanup section
    $cleanup_command = new Ajax\SimpleCommand('cleanup-db');
    $cleanup_command->set_command('wp-backup && wp-db-cleanup 2>&1', 'wp-db-cleanup --dry-run', false);
    $cleanup_command->set_button_text(\__('Run wp-db-cleanup', 'seravo'), \__('Do a dry run', 'seravo'));
    $cleanup_command->set_empty_message(\__('Nothing to be cleaned up', 'seravo'));

    $cleanup->add_ajax_handler($optimize);
    $cleanup->add_ajax_handler($cleanup_command);
  }

  /**
   * Build the database cleanup and optimize postbox.
   * @param \Seravo\Postbox\Component $base The postbox base component.
   * @param \Seravo\Postbox\Postbox $postbox To fetch the AJAX components from.
   * @return void
   */
  public static function build_database_cleanup( Component $base, Postbox\Postbox $postbox ) {
    $base->add_child(Template::section_title(\__('Optimization', 'seravo')));
    $base->add_child(Template::paragraph(\__('You can use this tool to run <code>wp-db-optimize</code>. The command optimizes WordPress database and thus reduces database disk usage.', 'seravo')));
    $base->add_child($postbox->get_ajax_handler('optimize-db')->get_component());

    $base->add_child(Template::section_title(\__('Cleanup', 'seravo')));
    $base->add_child(Template::paragraph(\__('You can use this tool to run <code>wp-db-cleanup</code>. For safety reason a dry run is compulsory before the actual cleanup can be done.', 'seravo')));
    $base->add_child($postbox->get_ajax_handler('cleanup-db')->get_component());
  }

  /**
   * Helper method for initializing database size AJAX handlers.
   * @param Postbox\Postbox $postbox Postbox to init AJAX handlers.
   * @return void
   */
  public static function init_database_size_scripts( Postbox\Postbox $postbox ) {
    $cache_time = 300;
    // Database details & info
    $db_info = new Ajax\LazyLoader('db-info', $cache_time);
    $db_info->set_ajax_func(array( __CLASS__, 'fetch_db_info' ));
    $db_info->use_hr(false);
    $postbox->add_ajax_handler($db_info);

    // Table sizes and their details
    $table_sizes = new Ajax\LazyLoader('table-sizes', $cache_time);
    $table_sizes->set_ajax_func(array( __CLASS__, 'fetch_db_table_sizes' ));
    $table_sizes->use_hr(false);
    $postbox->add_ajax_handler($table_sizes);

    $table_details = new Ajax\LazyLoader('table-details', $cache_time);
    $table_details->set_ajax_func(array( __CLASS__, 'fetch_db_table_sizes_details' ));
    $postbox->add_ajax_handler($table_details);
  }

  /**
   * Build the Adminer info postbox.
   * @param \Seravo\Postbox\Component $base The base component to add content.
   * @return void
   */
  public static function build_adminer_postbox( Component $base ) {
    $base->add_child(Template::paragraph(\__('<a href="https://www.adminer.org" target="_BLANK">Adminer</a> is a visual database management tool, which is simpler and safer than its competitor phpMyAdmin.', 'seravo')));
    $base->add_child(Template::paragraph(\__('At Seravo it can always be accessed at <code>example.com/.seravo/adminer</code>', 'seravo')));

    $button = Template::button_link_with_icon(Helpers::adminer_link(), \__('Open Adminer', 'seravo'));
    $button->set_wrapper('<p class="adminer-button">', '</p>');
    $base->add_child($button);
  }

  /**
   * Build the search-replace postbox.
   * @param \Seravo\Postbox\Component $base The base component to add content.
   * @return void
   */
  public static function build_search_replace_postbox( Component $base ) {
    $base->add_child(Template::paragraph(\__('You can use this tool to run <code>wp search-replace</code>. For safety reason a dry run is compulsory before the actual search-replace can be done.', 'seravo')));

    $from_to = new Component('', '<table>', '</table>');
    $from_to->add_child(Template::textfield_with_label('<b>' . \__('FROM:', 'seravo') . '</b>', 'sr-from'));
    $from_to->add_child(Template::textfield_with_label('<b>' . \__('TO:', 'seravo') . '</b>', 'sr-to'));
    $base->add_child($from_to);

    $base->add_child(
      Template::n_by_side(
        array(
          Template::checkbox_with_label(\__('Skip backups', 'seravo'), 'skip-backup'),
          (! \is_multisite() || \current_user_can('manage_network')) ? Template::checkbox_with_label(\__('All tables', 'seravo'), 'all-tables') : null,
          (\is_multisite() && \current_user_can('manage_network')) ? Template::checkbox_with_label(\__('Network', 'seravo'), 'network') : null,
        )
      )
    );
  }

  /**
   * AJAX function for search-replace postbox. Executes
   * the search-replace itself.
   * @return \Seravo\Ajax\AjaxResponse Response with the result.
   */
  public static function execute_search_replace() {
    // Check that both to and from are set
    if ( ! isset($_REQUEST['sr-from']) || $_REQUEST['sr-from'] === '' ||
          ! isset($_REQUEST['sr-to']) || $_REQUEST['sr-to'] === '' ) {
      return Ajax\AjaxResponse::form_input_error(\__('Error: Both <code>from</code> and <code>to</code> needs to be set', 'seravo'));
    }

    $from = $_REQUEST['sr-from'];
    $to = $_REQUEST['sr-to'];

    // Make sure the are not the same
    if ( $from === $to ) {
      // translators: Search replace 'from' value and 'to' value
      $message = \__('Error: Value %1$s is identical to %2$s', 'seravo');
      return Ajax\AjaxResponse::form_input_error(\sprintf($message, '<code>' . $to . '</code>', '<code>' . $from . '</code>'));
    }

    // Get arguments
    $dryrun = isset($_REQUEST['dryrun']) && $_REQUEST['dryrun'] === 'true';
    $backup = isset($_REQUEST['skip-backup']) && $_REQUEST['skip-backup'] === 'true' ? false : ! $dryrun;
    $all_tables = isset($_REQUEST['all-tables']) && $_REQUEST['all-tables'] === 'true' && (! \is_multisite() || \current_user_can('manage_network'));
    $network = isset($_REQUEST['network']) && $_REQUEST['network'] === 'true' && (\is_multisite() && \current_user_can('manage_network'));

    $args = array(
      $dryrun ? '--dry-run' : null,
      $all_tables ? '--all-tables' : null,
      $network ? '--network' : '--url=' . \get_site_url(),
      $from,
      $to,
    );

    $output = array();

    // Take backup
    if ( $backup ) {
      $output[] = "<b>$ wp-backup 2>&1\n</b>";
      \exec('wp-backup 2>&1', $output, $return_code);

      if ( $return_code !== 0 ) {
        return Ajax\AjaxResponse::command_error_response('wp-backup 2>&1', $return_code);
      }
    }

    // Execute search-replace
    $output = array();
    $command = Shell::sanitize_command('wp search-replace', $args);
    \exec($command . ' --format=table 2>&1', $sr_output, $return_code);

    // Convert output as table
    $output[] = '<div class="result-table-wrapper"><table class="result-table">';
    foreach ( $sr_output as $i => $line ) {
      if ( $i === 0 ) {
        $output[] = '<td><b>Table</b></td><td><b>Column</b></td><td><b>Count</b></td>';
        continue;
      }

      $columns = \preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
      if ( $columns === false || \count($columns) < 3 ) {
        continue;
      }

      $row = "<tr><td class=\"seravo-ellipsis\" title=\"{$columns[0]}\">{$columns[0]}</td>";
      $row .= "<td class=\"seravo-ellipsis\" title=\"{$columns[1]}\">{$columns[1]}</td>";
      $row .= "<td class=\"seravo-ellipsis\" title=\"{$columns[2]}\">{$columns[2]}</td></tr>";
      $output[] = $row;
    }
    \array_pop($output);
    $output[] = '</table></div>';

    // Send the response
    $response = new Ajax\AjaxResponse();
    $response->is_success(true);
    $response->set_data(
      array(
        'output' => '<hr>' . \implode("\n", $output) . '<hr>',
        'dryrun-only' => false,
      )
    );
    return $response;
  }

  /**
   * @param \Seravo\Postbox\Component $base    The base component to add child elements.
   * @param \Seravo\Postbox\Postbox   $postbox The postbox to add the components.
   * @return void
   */
  public static function build_database_size( Component $base, Postbox\Postbox $postbox ) {
    $base->add_child($postbox->get_ajax_handler('db-info')->get_component());
    $base->add_child(Component::from_raw('<hr><b>' . \__('Table sizes', 'seravo') . '</b>'));
    $table_sizes_container = new Component('', '<div class="seravo-container">', '</div><br>');
    $table_sizes_container->add_child(Component::from_raw('<div id="database-bars-single"></div>'));
    $base->add_child($table_sizes_container);
    $base->add_child($postbox->get_ajax_handler('table-sizes')->get_component());
    $base->add_child(Template::section_title(\__('Details about database table sizes', 'seravo')));
    $base->add_child($postbox->get_ajax_handler('table-details')->get_component());
  }

  /**
   * AJAX function for fetching database name and size.
   * @return Ajax\AjaxResponse Response with error or db info in table.
   */
  public static function fetch_db_info() {
    $db_columns = array();
    $cmd = Compatibility::exec('wp db size', $output, $return_code);

    if ( $cmd === false || $return_code !== 0 ) {
      return AjaxResponse::command_error_response('wp db size', $return_code);
    }

    foreach ( $output as $value ) {
      // Columns are separated with tabs
      $columns = \explode("\t", $value);
      $updated_columns = array();

      foreach ( $columns as $column ) {
        $updated_columns[] = (Helpers::human_file_size((int) $column) == '0B') ? $column : Helpers::human_file_size((int) $column);
      }
      $db_columns[] = $updated_columns;
    }

    $db_info_table = Template::table_view('seravo-wb-db-info-table', 'db-info-th', 'db-info-td', array( '', '' ), $db_columns)->to_html();
    return AjaxResponse::response_with_output($db_info_table);
  }

  /**
   * AJAX function for fetching database table sizes.
   * @return Ajax\AjaxResponse Response with return data.
   */
  public static function fetch_db_table_sizes() {
    $response = new AjaxResponse();
    $size_in_format = Compatibility::exec('wp db size --size_format=b', $total, $result_code_normal);
    $size_in_json = Compatibility::exec('wp db size --tables --format=json', $json, $result_code_json);

    if ( $size_in_format === false || $result_code_normal !== 0 ) {
      return AjaxResponse::command_error_response('wp db size', $result_code_normal);
    }
    if ( $size_in_json === false || $result_code_json !== 0 ) {
      return AjaxResponse::command_error_response('wp db size', $result_code_json);
    }

    $tables = \json_decode($json[0], true);
    $data_folders = array();

    foreach ( $tables as $table ) {
      $size = \preg_replace('/[^0-9]/', '', $table['Size']);
      if ( $size === null ) {
        continue;
      }
      if ( \is_array($size) ) {
        $size = $size[0];
      }

      $data_folders[$table['Name']] = array(
        'percentage' => (($size / $total[0]) * 100),
        'human'      => Helpers::human_file_size((int) $size),
        'size'       => $size,
      );
    }

    $response->is_success(true);
    $response->set_data(
      array(
        'data' => array(
          'human' => Helpers::human_file_size($total[0]),
          'size'  => $total,
        ),
        'folders' => $data_folders,
      )
    );

    return $response;
  }

  /**
   * AJAX function for fetching database table sizes in detail.
   * @return Ajax\AjaxResponse Response with table sizes details.
   */
  public static function fetch_db_table_sizes_details() {
    $common_column_titles = array( '', '' );
    global $wpdb;
    // Make the database queries
    $cumulative_postmeta_sizes = $wpdb->get_results("SELECT meta_key, SUBSTRING(meta_value, 1, 30) AS meta_value_snip, LENGTH(meta_value) AS meta_value_length, SUM(LENGTH(meta_value)) AS length_sum FROM $wpdb->postmeta GROUP BY meta_key ORDER BY length_sum DESC LIMIT 15");
    $common_postmeta_values = $wpdb->get_results("SELECT SUBSTRING(meta_key, 1, 20) AS meta_key, COUNT(*) AS key_count FROM $wpdb->postmeta GROUP BY meta_key ORDER BY key_count DESC LIMIT 15");
    $autoload_option_count = $wpdb->get_results("SELECT COUNT(*) AS options_count FROM $wpdb->options WHERE autoload = 'yes'");
    $total_autoload_option_size = $wpdb->get_results("SELECT SUM(LENGTH(option_value)) AS total_size FROM $wpdb->options WHERE autoload='yes'");
    $long_autoload_option_values = $wpdb->get_results("SELECT SUBSTRING(option_name, 1, 20) AS option_name, LENGTH(option_value) AS option_value_length FROM $wpdb->options WHERE autoload='yes' ORDER BY LENGTH(option_value) DESC LIMIT 15");

    // Fetch the data in a readable format
    $cumulative_postmeta = array();
    foreach ( $cumulative_postmeta_sizes as $size ) {
      $cumulative_postmeta[] = array( $size->meta_key, $size->length_sum );
    }
    $common_postmeta = array();
    foreach ( $common_postmeta_values as $value ) {
      $common_postmeta[] = array( $value->meta_key, $value->key_count );
    }
    $autoload_option = 0;
    foreach ( $autoload_option_count as $value ) {
      $autoload_option = $value->options_count;
    }
    $total_autoload = '';
    foreach ( $total_autoload_option_size as $size ) {
      $total_autoload = Helpers::human_file_size($size->total_size);
    }
    $long_autoload = array();
    foreach ( $long_autoload_option_values as $value ) {
      $long_autoload[] = array( $value->option_name, $value->option_value_length );
    }
    // Add components for return output
    $db_details = new Component('', '<div class="seravo-container">', '</div>');
    $db_details->add_child(Component::from_raw('<b>' . \__('Longest wp_postmeta values', 'seravo') . '</b>'));
    $db_details->add_child(Template::table_view('result-table', 'sizes-th', 'sizes-td', $common_column_titles, $cumulative_postmeta));

    $db_details->add_child(Component::from_raw('<hr><b>' . \__('Cumulative size of meta_value per meta_key', 'seravo') . '</b>'));
    $db_details->add_child(Template::table_view('result-table', 'sizes-th', 'sizes-td', $common_column_titles, $common_postmeta));

    $db_details->add_child(Component::from_raw('<hr><b>' . \__('Autoload options count (read to memory on each WP page load)', 'seravo') . '</b>'));
    $db_details->add_child(Template::paragraph((string) $autoload_option));

    $db_details->add_child(Component::from_raw('<hr><b>' . \__('Autoload options total size of values', 'seravo') . '</b>'));
    $db_details->add_child(Template::paragraph($total_autoload));

    $db_details->add_child(Component::from_raw('<hr><b>' . \__('Longest autoloaded wp_option values', 'seravo') . '</b>'));
    $db_details->add_child(Template::table_view('result-table', 'sizes-th', 'sizes-td', $common_column_titles, $long_autoload));

    return AjaxResponse::response_with_output($db_details->to_html());
  }
}
