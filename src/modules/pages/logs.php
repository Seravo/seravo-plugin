<?php

namespace Seravo\Page;

use \Seravo\Ajax;

use \Seravo\Postbox;
use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Requirements;

/**
 * Class Logs
 *
 * Logs page is for browsing the site logs.
 */
class Logs extends Toolpage {

  /**
   * @var \Seravo\Page\Logs|null Instance of this page.
   */
  private static $instance;

  /**
   * Function for creating an instance of the page. This should be
   * used instead of 'new' as there can only be one instance at a time.
   * @return \Seravo\Page\Logs Instance of this page.
   */
  public static function load() {
    if ( self::$instance === null ) {
      self::$instance = new Logs();
    }

    return self::$instance;
  }

  /**
   * Constructor for Logs. Will be called on new instance.
   * Basic page details are given here.
   */
  public function __construct() {
    parent::__construct(
      \__('Logs', 'seravo'),
      'tools_page_logs_page',
      'logs_page',
      'Seravo\Postbox\seravo_wide_column_postboxes_page'
    );
  }

  /**
   * Will be called for page initialization. Includes scripts
   * and enables toolpage features needed for this page.
   */
  public function init_page() {
    self::init_postboxes($this);

    \add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));

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
   * Register scripts.
   * @param string $screen The current screen.
   * @return void
   */
  public static function enqueue_scripts( $screen ) {
    if ( $screen !== 'tools_page_logs_page' ) {
      return;
    }

    //wp_enqueue_script('seravo-site-status-js', SERAVO_PLUGIN_URL . 'js/sitestatus.js', array(), Helpers::seravo_plugin_version());
    \wp_enqueue_script('seravo-log-viewer-js', SERAVO_PLUGIN_URL . 'js/log-viewer.js', array( 'jquery' ), \Seravo\Helpers::seravo_plugin_version());
    \wp_enqueue_style('seravo-log-viewer-css', SERAVO_PLUGIN_URL . 'style/log-viewer.css', array(), \Seravo\Helpers::seravo_plugin_version());
  }

  /**
   * Initialize logs page postboxes.
   * @param \Seravo\Postbox\Toolpage $page The page for postboxes.
   * @return void
   */
  public static function init_postboxes( Toolpage $page ) {
    /**
     * Logs postbox
     */
    $logs = new Postbox\Postbox('seravologs');
    $logs->set_title(\__('Logs', 'seravo'));
    $logs->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $logs->set_data_func(array( __CLASS__, 'get_log_entries' ));
    $logs->set_build_func(array( __CLASS__, 'build_logs' ));

    $log_handler = new Ajax\AjaxHandler('fetch-logs');
    $log_handler->set_ajax_func(array( __CLASS__, 'fetch_logs' ));
    $logs->add_ajax_handler($log_handler);

    $page->register_postbox($logs);
  }

  /**
   * Data function for Logs -postbox. Gets all log files
   * in groups and ordered by time.
   * @return array<string,mixed> Array with log files, default log and the keyword.
   */
  public static function get_log_entries() {
    $log_files = \Seravo\Logs::get_logs_with_time();

    // Select default group and variation
    $group = null;
    $variation = 0;
    if ( isset($_GET['logfile']) ) {
      foreach ( $log_files as $log_group => $logs ) {
        foreach ( $logs as $i => $log ) {
          if ( $log['file'] === $_GET['logfile'] ) {
            // Found the file, default to it
            $group = $log_group;
            $variation = $i;
            break;
          }
        }
        if ( 0 === \strpos($logs[0]['file'], $_GET['logfile']) ) {
          // Didn't find the file but found the group
          $group = $log_group;
        }
      }
    }

    if ( $group === null ) {
      $group = $log_files !== array() ? \array_keys($log_files)[0] : '';
    }

    return array(
      'logs' => $log_files,
      'group' => $group,
      'variation' => $variation,
      'keyword' => isset($_GET['log-keyword']) ? \esc_attr($_GET['log-keyword']) : '',
    );
  }

  /**
   * Build the Logs -postbox.
   * @param \Seravo\Postbox\Component $base    The base component to build on.
   * @param \Seravo\Postbox\Postbox   $postbox The postbox to build for.
   * @param mixed                     $data    Data from 'get_log_entries' data function.
   * @return void
   */
  public static function build_logs( Component $base, Postbox\Postbox $postbox, $data ) {
    $php_error_log = '<a href="' . \site_url('/wp-admin/tools.php?page=logs_page&logfile=php-error.log') . '">php-error.log</a>';
    $base->add_child(
      Template::paragraph(
        \__('Here you can browse and view the logs for your sites. The same log files can be found on server under <code>/data/log/*</code>.', 'seravo') . ' ' .
        // translators: Link to php-error.log
        \sprintf(\__("Be sure to check %s, it's a good metric of the site's health.", 'seravo'), $php_error_log)
      )
    );

    $log_viewer = new Component('', '<div class="log-viewer-wrapper">', '</div>');
    // Log menu wrapper
    $log_menu_wrapper = new Component('', '<div class="log-menu-wrapper">', '</div>');
    $log_viewer->add_child($log_menu_wrapper);
    // Log menu
    $log_menu = new Component('', '<div class="log-menu"><ul>', '</ul></div>');
    $log_menu_wrapper->add_child($log_menu);
    // Log menu entries
    foreach ( $data['logs'] as $log_group => $logs ) {
      $sel = $data['group'] === $log_group ? ' selected' : '';
      $json = \json_encode($logs);
      if ( $json === false ) {
        continue;
      }
      $vars = \htmlspecialchars($json, ENT_QUOTES, 'UTF-8');

      $menu_entry = new Component('', '<li title="' . $log_group . '">', '</li>');
      $menu_entry->add_child(new Component($log_group, '<div class="log-menu-entry button' . $sel . '" data-variations="' . $vars . '">', '</div>'));
      $log_menu->add_child($menu_entry);
    }
    // Log view wrapper
    $log_view_wrapper = new Component('', '<div class="log-view-wrapper">', '</div>');
    $log_viewer->add_child($log_view_wrapper);
    // Filter bar
    $filter_bar = new Component('', '<div class="filter-bar-wrapper">', '</div>');
    $log_view_wrapper->add_child($filter_bar);
    // Date picker
    $log_date = new Component('', '<div class="log-view-date" data-default-variation="' . $data['variation'] . '">', '</div>');
    $log_date->add_child(Template::button('<', 'log-date-previous', 'button log-date-pick date-previous disabled'));
    $log_date->add_child(Component::from_raw('<input type="text" name="log-view-date" class="log-date-input" value="" disabled> '));
    $log_date->add_child(Template::button('>', 'log-date-next', 'button log-date-pick date-next disabled'));
    $filter_bar->add_child($log_date);
    // Search bar
    $log_search = new Component('', '<div class="log-view-search">', '</div>');
    $log_search->add_child(new Component('', '<input type="text" name="log-view-keyword" value="' . $data['keyword'] . '" placeholder="' . \__('Keyword', 'seravo') . '"/>'));
    $log_search->add_child(Template::button(\__('Search', 'seravo'), 'log-view-search', 'button'));
    $filter_bar->add_child($log_search);
    // Log view
    $log_view = new Component('', '<div class="log-view">', '</div>');
    $log_view_wrapper->add_child($log_view);
    // Log view table
    $log_view->add_child(new Component('', '<table class="log-table wp-list-table widefat striped" style="display:none;"><tbody>', '</tbody></table>'));
    // The loading spinner
    $log_view->add_child(Template::spinner('log-view-spinner', 'seravo-spinner log-view-spinner', false));
    // Bottom info bar
    $log_view_wrapper->add_child(new Component('', '<div class="info-bar-wrapper">', '</div>'));

    $base->add_child($log_viewer);
  }

  /**
   * AJAX function for fetching lines from logfile.
   * @return \Seravo\Ajax\AjaxResponse Response data.
   */
  public static function fetch_logs() {
    $response = new Ajax\AjaxResponse();
    $response->is_success(true);

    $logs = \Seravo\Logs::read_log_lines_backwards('/data/log/' . $_GET['file'], $_GET['offset'], 30);
    if ( isset($logs['error']) && $logs['error'] !== '' ) {
      // Something went wrong
      $response->is_success(false);
    } else {
      $keyword = isset($_GET['log-keyword']) && $_GET['log-keyword'] !== '' ? $_GET['log-keyword'] : null;
      if ( $keyword !== null ) {
        $output = \preg_replace('/' . $keyword . '/i', '<span class="highlight">$0</span>', $logs['output']);
        if ( $output !== null ) {
          $logs['output'] = $output;
        }
      }
    }

    $response->set_data($logs);
    return $response;
  }

}
