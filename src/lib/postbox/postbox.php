<?php
/**
 * File for Seravo Postbox and Toolpage.
 */

namespace Seravo\Postbox;

use \Seravo\Ajax\AjaxHandler;

/**
 * Class Postbox
 *
 * Postbox is abstraction on Seravo_Postbox_Factory to
 * ease the manageability of Seravo tools.
 */
class Postbox {

  /**
   * @var string String for transient key to be prefixed with.
   */
  const CACHE_KEY_PREFIX = 'seravo_';
  /**
   * @var string String for transient key to be suffixed with.
   */
  const CACHE_KEY_SUFFIX = '_data';


  /**
   * @var string Unique id/slug of the postbox.
   */
  public $id;
  /**
   * @var string Display title of the postbox.
   */
  public $title = 'WIP';
  /**
   * @var string Default admin dashboard context where the postbox should be displayed in.
   */
  public $context = 'normal';
  /**
   * @var string|null Admin screen id where the postbox should be displayed in.
   */
  private $screen;


  /**
   * @var callable|null Function to be called for building the components.
   */
  private $build_func;
  /**
   * @var callable|null Function to be called for data processing.
   */
  private $data_func;
  /**
   * @var int|null      Seconds to cache data returned by $data_func.
   */
  private $data_cache_time;
  /**
   * @var mixed|null Data returned by $data_func.
   */
  private $data;
  /**
   * @var \Exception|null Exception thrown during data_func or null.
   */
  private $error;

  /**
   * @var float|int|null Time it took to build the postbox components.
   */
  private $buildtime;


  /**
   * @var \Seravo\Ajax\AjaxHandler[] Ajax handlers assigned for this postbox.
   */
  protected $ajax_handlers = array();
  /**
   * @var \Seravo\Postbox\Settings[] Setting sections assigned for this postbox.
   */
  protected $setting_sections = array();


  /**
   * @var \Seravo\Postbox\Requirements Requirements for this postbox.
   */
  private $requirements;
  /**
   * @var \Seravo\Postbox\Component The base component to add other components on.
   */
  private $component;

  /**
   * Constructor for Postbox. Will be called on new instance.
   * @param string $id      Unique id/slug of the postbox.
   * @param string $context Default admin dashboard context where the postbox should be displayed in.
   */
  public function __construct( $id, $context = 'normal' ) {
    $this->id = $id;
    $this->context = $context;

    $this->requirements = new Requirements();
    $this->component = new Component();
  }

  /**
   * Check if the postbox should be shown or not. Applies
   * the 'seravo_show_postbox-$id' filter which defaults to true.
   * @return bool Whether the postbox should be shown or not.
   */
  public function _is_allowed() {
    if ( ! $this->requirements->is_allowed() ) {
      return false;
    }

    return (bool) \apply_filters('seravo_show_postbox-' . $this->id, true);
  }



  /**
   * Called when postbox is assigned a page. This is the reason
   * the same postbox instance can't be added on multiple pages.
   * @param string $screen Admin screen id where the postbox should be displayed in.
   * @return void
   */
  public function on_page_assign( $screen ) {
    $this->screen = $screen;
    foreach ( $this->ajax_handlers as $ajax_handler ) {
      $ajax_handler->init($this->id, $this->screen);
    }
  }

  /**
   * Calls the data function of postbox. Caching
   * and exceptions are taken care of here.
   *
   * Result is stored in either $this->data or $this->error.
   * Only valid results are cache in transients.
   * @return void
   */
  public function _get_data() {
    if ( $this->data_func === null ) {
      return;
    }

    $cache_key = self::CACHE_KEY_PREFIX . $this->id . self::CACHE_KEY_SUFFIX;

    try {

      // Check if we should be using transients
      if ( $this->data_cache_time > 0 ) {
        // We should be, check if the data is cached
        $this->data = \get_transient($cache_key);
        if ( false === $this->data ) {
          // The data was not cached, call $data_func
          $this->data = \call_user_func($this->data_func);
          if ( null !== $this->data ) {
            // Cache new result unless it's null
            \set_transient($cache_key, $this->data, $this->data_cache_time);
          }
        }
        return;
      }

      // We are not using cache, just call $data_func
      $this->data = \call_user_func($this->data_func);

    } catch ( \Exception $exception ) {
      \error_log('### Seravo Plugin experienced an error!');
      \error_log('### Please report this on GitHub (https://github.com/Seravo/seravo-plugin) with following:');
      \error_log($exception);

      $this->error = $exception;
      $this->data = null;
    }
  }

  /**
   * WordPress will call this when it's time to render the postbox.
   * This will take care of calling custom data and build functions.
   * @return void
   */
  public function _build() {
    if ( \defined('SERAVO_PLUGIN_DEBUG') && SERAVO_PLUGIN_DEBUG ) {
      $this->buildtime = \hrtime(true);
    }

    $this->_get_data();

    if ( $this->error !== null ) {
        // Show error instead of the real content
        // translators: link to php-error.log
        $message = __('Whoops! Something went wrong. Please see %s for instructions.', 'seravo');
        $url = \get_option('siteurl') . '/wp-admin/tools.php?page=logs_page&logfile=php-error.log';
        $link = \sprintf('<a href="%s">php-error.log</a>', $url);
        $error = \sprintf($message, $link);
        $this->component = Template::error_paragraph($error);
    } elseif ( \is_callable($this->build_func) ) {
        // Call the $build_func
        \call_user_func($this->build_func, $this->component, $this, $this->data);
    }

    $this->component->print_html();

    if ( \defined('SERAVO_PLUGIN_DEBUG') && SERAVO_PLUGIN_DEBUG ) {
      $this->buildtime = $this->buildtime === null ? -1 : \hrtime(true) - $this->buildtime;

      $this->debug_print();
    }
  }

  /**
   * Print debug info table for the postbox.
   * @return void
   */
  private function debug_print() {
    echo '<table style="border:2px solid black;width:100%;margin-top:10vh;">';
    echo '<th colspan="2" style="border-bottom:1px solid black;">Postbox Info</th>';
    echo "<tr><td>Postbox ID</td><td>{$this->id}</td></tr>";
    echo "<tr><td>Screen</td><td>{$this->screen}</td></tr>";
    echo "<tr><td>Title</td><td>{$this->title}</td></tr>";
    echo "<tr><td>Context</td><td>{$this->context}</td></tr>";
    echo '<th colspan="2" style="border-bottom:1px solid black;">Postbox Requirements</th>';
    echo '<tr><td>User must be admin</td><td>' . ($this->requirements->is_admin ? 'true' : 'false') . '</td></tr>';
    echo '<tr><td>User must be WP-CLI</td><td>' . ($this->requirements->is_wp_cli ? 'true' : 'false') . '</td></tr>';
    echo '<tr><td>Site must be multisite</td><td>' . ($this->requirements->is_multisite ? 'true' : 'false') . '</td></tr>';
    echo "<tr><td>Site can't be multisite</td><td>" . ($this->requirements->is_not_multisite ? 'true' : 'false') . '</td></tr>';
    echo '<tr><td>Can be production</td><td>' . ($this->requirements->can_be_production ? 'true' : 'false') . '</td></tr>';
    echo '<tr><td>Can be development</td><td>' . ($this->requirements->can_be_development ? 'true' : 'false') . '</td></tr>';
    echo '<tr><td>Can be staging</td><td>' . ($this->requirements->can_be_staging ? 'true' : 'false') . '</td></tr>';
    echo '<th colspan="2" style="border-bottom:1px solid black;">Postbox Functionality</th>';
    echo '<tr><td>Postbox build time</td><td>' . ($this->buildtime !== null ? \round($this->buildtime / 1000, 2) . ' µs' : '-') . '</td></tr>';
    echo '<tr><td>Uses data function</td><td>' . ($this->data_func !== null ? 'true' : 'false') . '</td></tr>';
    echo '<tr><td>Data cache time</td><td>' . ($this->data_func !== null ? $this->data_cache_time : '-') . '</td></tr>';
    echo '<th colspan="2" style="border-bottom:1px solid black;">AJAX Functionality</th>';
    $ajax_handler_count = \count($this->ajax_handlers);
    echo "<tr><td>Amount of AJAX handlers</td><td>{$ajax_handler_count}</td></tr>";
    for ( $i = 1; $i <= $ajax_handler_count; ++$i ) {
      $handler = $this->ajax_handlers[\array_keys($this->ajax_handlers)[$i - 1]];
      echo "<tr><td>{$i}. handler section</td><td>{$handler->get_section()}</td></tr>";
      echo "<tr><td>{$i}. handler cache time</td><td>{$handler->get_cache_time()}</td></tr>";
    }
    echo '</table>';
  }

  /**
   * Set the build function for the postbox. The function will be
   * called when it's time render the postbox.
   * @param callable $build_func Function to be called for building the components.
   * @return void
   */
  public function set_build_func( $build_func ) {
    $this->build_func = $build_func;
  }

  /**
   * Set the optional data function for the postbox. The function will be
   * called right before build function.
   * @param callable $data_func  Function to be called for data processing.
   * @param int      $cache_time Seconds to cache the data for (default is 0).
   * @return void
   */
  public function set_data_func( $data_func, $cache_time = 0 ) {
    $this->data_func = $data_func;
    $this->data_cache_time = $cache_time;
  }

  /**
   * Adds an AJAX handler for the postbox. The same AjaxHandler instance
   * shouldn't be added to multiple postboxes without cloning.
   * @param \Seravo\Ajax\AjaxHandler $ajax_handler Ajax handler to be added for the postbox.
   * @return void
   */
  public function add_ajax_handler( $ajax_handler ) {
    $this->ajax_handlers[$ajax_handler->get_section()] = $ajax_handler;
  }

  /**
   * Gets AJAX handler by section.
   * @param string $section Section to get handler by.
   * @return \Seravo\Ajax\AjaxHandler AJAX handler with $section as section. It must exists.
   */
  public function get_ajax_handler( $section ) {
    return $this->ajax_handlers[$section];
  }

  /**
   * Adds a setting section for the postbox. The same instance
   * shouldn't be added to multiple postboxes without cloning.
   * @param \Seravo\Postbox\Settings $settings Setting section instance to be added.
   * @return void
   */
  public function add_setting_section( $settings ) {
    $settings->set_postbox($this->id);
    $settings->register();

    $this->setting_sections[$settings->get_section()] = $settings;
  }

  /**
   * Gets setting section by section id.
   * @param string $section The section ID to get the handler by.
   * @return \Seravo\Postbox\Settings|null The setting section.
   */
  public function get_setting_section( $section ) {
    if ( isset($this->setting_sections[$section]) ) {
      return $this->setting_sections[$section];
    }
    return null;
  }

  /**
   * Set the requirements for the postbox. Requirements can be given as
   * Requirements instance or array in "[Requirements::*] => mixed" format.
   * @param array<string, mixed>|Requirements $requirements Requirements for the postbox.
   * @return void
   */
  public function set_requirements( $requirements ) {
    if ( \is_array($requirements) ) {
      $this->requirements->init_from_array($requirements);
    } else {
      $this->requirements = $requirements;
    }
  }

  /**
   * Get the postbox ID.
   * @return string Unique id/slug of the postbox.
   */
  public function get_id() {
    return $this->id;
  }

  /**
   * Get the postbox context.
   * @return string Default admin dashboard context where the postbox should be displayed in.
   */
  public function get_context() {
    return $this->context;
  }

  /**
   * Set the title for the postbox.
   * @param string $title Display title of the postbox.
   * @return void
   */
  public function set_title( $title ) {
    $this->title = $title;
  }

  /**
   * Get the postbox title.
   * @return string Display title of the postbox.
   */
  public function get_title() {
    return $this->title;
  }

}
