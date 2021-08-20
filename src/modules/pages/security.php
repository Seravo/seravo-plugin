<?php

namespace Seravo\Page;

use \Seravo\Helpers;
use \Seravo\CruftRemover;

use \Seravo\Ajax;
use \Seravo\Ajax\AjaxHandler;
use \Seravo\Ajax\AjaxResponse;
use \Seravo\Compatibility;
use \Seravo\Postbox;
use \Seravo\Postbox\Settings;
use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Requirements;

/**
 * Class Security
 *
 * Security is a page for managing security
 * features and info about logins and plugins/themes/files.
 */
class Security extends Toolpage {

  /**
   * @var \Seravo\Page\Security|null Instance of this page.
   */
  private static $instance;

  /**
   * Function for creating an instance of the page. This should be
   * used instead of 'new' as there can only be one instance at a time.
   * @return \Seravo\Page\Security Instance of this page.
   */
  public static function load() {
    if ( self::$instance === null ) {
      self::$instance = new Security();
    }

    return self::$instance;
  }

  /**
   * Constructor for Security. Will be called on new instance.
   * Basic page details are given here.
   */
  public function __construct() {
    parent::__construct(
      __('Security', 'seravo'),
      'tools_page_security_page',
      'security_page',
      'Seravo\Postbox\seravo_postboxes_page'
    );
  }

  /**
   * Will be called for page initialization. Includes scripts
   * and enables toolpage features needed for this page.
   */
  public function init_page() {
    self::init_postboxes($this);

    \add_action('admin_notices', array( __CLASS__, '_seravo_check_security_options' ));
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
   * Init postboxes on Security page.
   * @param Toolpage $page Page to init postboxes.
   * @return void
   */
  public static function init_postboxes( Toolpage $page ) {
    /**
     * Security settings postbox
     */
    $security = new Postbox\SettingsForm('security');
    $security->set_title(__('Security', 'seravo'));
    $security->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $security->add_paragraph(__('Seravo has security built-in. There are however a few extra measures that the site owner can choose to do if their site will not miss any functionality because of it.', 'seravo'));
    $security->add_setting_section(self::get_security_settings());
    $page->register_postbox($security);

    /**
     * Check passwords postbox (Beta)
     */
    $passwords = new Postbox\Postbox('check-passwords');
    $passwords->set_title(__('Check passwords (Beta)', 'seravo'));
    $passwords->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    // Add AJAX handler for checking passwords
    $check_passwords = new Ajax\SimpleCommand('ajax-check-passwords');
    $check_passwords->set_button_text(__('Run', 'seravo'));
    $check_passwords->set_spinner_text(__('Running the password check', 'seravo'));
    $check_passwords->set_ajax_func(array( __CLASS__, 'check_passwords' ));
    $passwords->add_ajax_handler($check_passwords);
    $passwords->set_build_func(
      function( Component $base, Postbox\Postbox $postbox ) {
        $base->add_child(Template::paragraph(__('This tool can be used to run command <code>wp-check-passwords</code> which finds weak passwords from the users of the site. Note: This may fail, if there are many users.', 'seravo')));
        $base->add_child($postbox->get_ajax_handler('ajax-check-passwords')->get_component());
      }
    );
    $page->register_postbox($passwords);

    /**
     * Last successful logins postbox
     */
    $logins = new Postbox\LazyLoader('logins-info');
    $logins->set_title(__('Last successful logins', 'seravo'));
    $logins->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $logins->set_ajax_func(array( __CLASS__, 'get_last_successful_logins' ));
    $logins->set_build_func(array( __CLASS__, 'build_last_logins' ));
    $page->register_postbox($logins);

    /**
     * Cruft files postbox
     */
    $cruft_files = self::create_cruft_box('cruftfiles', 'files');
    $cruft_files->set_title(__('Cruft Files', 'seravo'));
    $cruft_files->add_paragraph(__('Find and delete any extraneous and potentially harmful files taking up space in the file system. Note that not everything is necessarily safe to delete.', 'seravo'));
    $page->register_postbox($cruft_files);

    /**
     * Cruft plugins postbox
     */
    $cruft_plugins = self::create_cruft_box('cruftplugins', 'plugins');
    $cruft_plugins->set_title(__('Unnecessary plugins', 'seravo'));
    $cruft_plugins->add_paragraph(__('Find and remove any plugins that are currently inactive or otherwise potentially harmful. For more information, please read our <a href="https://help.seravo.com/article/165-recommended-plugins" target="_BLANK">recommendations for plugins in our environment</a>.', 'seravo'));
    $page->register_postbox($cruft_plugins);

    /**
     * Cruft themes postbox
     */
    $cruft_themes = self::create_cruft_box('cruftthemes', 'themes');
    $cruft_themes->set_title(__('Unnecessary themes', 'seravo'));
    $cruft_themes->add_paragraph(__('Find and remove themes that are inactive. For more information, please read our <a href="https://help.seravo.com/article/70-can-i-install-my-own-plugins-and-themes-on-the-website" target="_BLANK">documentation concerning themes and plugins</a>.', 'seravo'));
    $page->register_postbox($cruft_themes);
  }

  /**
   * AJAX function for running wp-check-passwords
   * @return Ajax\AjaxResponse
   */
  public static function check_passwords() {
    $check_passwords = Compatibility::exec('wp-check-passwords', $output, $ret_val);

    if ( $check_passwords === false ) {
      return Ajax\AjaxResponse::command_error_response('wp-check-passwords', $ret_val);
    }
    // Filter and format the color chars from output.
    $pattern = '/\x1b\[[0-9;]*m/';
    $filter_output = \preg_replace($pattern, '', $output);

    if ( $filter_output !== null ) {
      $output = $filter_output;
    }
    $output = '<pre>' . \implode("\n", $output) . '</pre>';
    return Ajax\AjaxResponse::response_with_output($output);

  }

  /**
   * Create initialized postbox for the cruft removers.
   * @param string $id ID for the postbox.
   * @param string $type Type of the cruft remover (files/plugins/themes).
   * @return \Seravo\Postbox\LazyLoader Pre-initialized postbox.
   */
  public static function create_cruft_box( $id, $type ) {
    $cruftbox = new Postbox\LazyLoader($id, 'side');
    $cruftbox->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));

    // Postbox build function
    $cruftbox->set_build_func(
      function( Component $base, Postbox\Postbox $postbox ) use ( $type ) {
        self::build_cruftbox($base, $postbox, $type);
      }
    );

    // LazyLoader AJAX function
    $cruftbox->set_ajax_func(
      function() use ( $type ) {
        return self::get_cruft($type);
      }
    );

    // Remove cruft AJAX function
    $remove_cruft = new AjaxHandler('remove-cruft-' . $id);
    $remove_cruft->set_ajax_func(
      function() use ( $type ) {
        return self::remove_cruft($type);
      }
    );
    $cruftbox->add_ajax_handler($remove_cruft);

    return $cruftbox;
  }

  /**
   * Register scripts.
   * @param string $screen The current screen.
   * @return void
   */
  public static function enqueue_scripts( $screen ) {
    if ( $screen !== 'tools_page_security_page' ) {
      return;
    }

    \wp_enqueue_script('cruftremover-js', SERAVO_PLUGIN_URL . 'js/cruftremover.js', array( 'jquery', 'seravo-common-js' ), Helpers::seravo_plugin_version());
    \wp_enqueue_style('cruftremover-css', SERAVO_PLUGIN_URL . 'style/cruftremover.css', array(), Helpers::seravo_plugin_version());

    $cruftremover_l10n = array(
      'confirm'    => __('Cruft remove confirmation', 'seravo'),
      'no_cruft'   => __("Congratulations! There's nothing to remove.", 'seravo'),
      'delete'     => __('Delete', 'seravo'),
      'select_all' => __('Select all', 'seravo'),
      'failure'    => __('Failed to remove some files!', 'seravo'),
    );

    \wp_localize_script('cruftremover-js', 'cruftremover_l10n', $cruftremover_l10n);
  }

  /**
   * Check if the security settings have been set and show a notice if they
   * haven't been. No matter if the features are disabled or enabled.
   * @return void
   */
  public static function _seravo_check_security_options() {
    $options = array(
      'seravo-disable-xml-rpc',
      'seravo-disable-xml-rpc-all-methods',
      'seravo-disable-json-user-enumeration',
      'seravo-disable-get-author-enumeration',
    );

    foreach ( $options as $option ) {
      if ( \get_option($option) === false ) {
        ?>
        <div class="notice notice-error">
          <p>
            <?php
            \printf(
              // translators: URL to security page
              __('Please enable all possible <a href="%s">security features</a>. Save settings even if no changes were made to get rid of this notice.', 'seravo'),
              \esc_url(\get_option('siteurl')) . '/wp-admin/tools.php?page=security_page'
            );
            ?>
          </p>
        </div>
        <?php
        break;
      }
    }
  }

  /**
   * Get setting section for the security settings postbox.
   * @return \Seravo\Postbox\Settings The setting section instance.
   */
  public static function get_security_settings() {
    $security_settings = new Settings('seravo-security-settings');

    // Fake checkboxes
    $fake_fields = array(
      'seravo-automatic-backups'         => __('Automatic backups', 'seravo'),
      'seravo-security-updates'          => __('Quick security updates', 'seravo'),
      'seravo-malicious-code-monitoring' => __('Monitoring of malicius code and database contents', 'seravo'),
      'seravo-dos-protection'            => __('Denial-of-service protection', 'seravo'),
      'seravo-brute-force-protection'    => __('Brute-force login protection', 'seravo'),
    );
    foreach ( $fake_fields as $name => $label ) {
      $security_settings->add_field($name, $label, '', '', Settings::FIELD_TYPE_BOOLEAN, '', null, array( Template::class, 'fake_checkbox' ));
    }

    // Real fields
    $real_fields = array(
      'seravo-disable-xml-rpc' => array(
        __('Disable authenticated XML-RPC', 'seravo'),
        __("Disabling authenticated XML-RPC prevents brute-force attempts via XML-RPC. Disables e.g. using the WordPress mobile app. Doesn't affect the Jetpack plugin as its IPs are whitelisted.", 'seravo'),
      ),
      'seravo-disable-xml-rpc-all-methods' => array(
        __('Completely disable XML-RPC', 'seravo'),
        __('Completely disabling XML-RPC prevents XML-RPC from responding to any methods at all. Disables e.g. pingbacks.', 'seravo'),
      ),
      'seravo-disable-json-user-enumeration'  => __('Disable WP-JSON user enumeration', 'seravo'),
      'seravo-disable-get-author-enumeration' => __('Disable GET author enumeration', 'seravo'),
    );
    foreach ( $real_fields as $name => $details ) {
      $description = '';
      if ( \is_array($details) ) {
        $label = $details[0];
        $description = $details[1];
      } else {
        $label = $details;
      }

      $security_settings->add_field($name, $label, '', '<small>' . $description . '</small>', Settings::FIELD_TYPE_BOOLEAN, 'off');
    }

    return $security_settings;
  }

  /**
   * Build function for last successfull logins postbox.
   * @param \Seravo\Postbox\Component $base    Base component.
   * @param \Seravo\Postbox\Postbox   $postbox Postbox widget.
   * @return void
   */
  public static function build_last_logins( Component $base, Postbox\Postbox $postbox ) {
    $base->add_child(Template::paragraph(__('This tool can be used to retrieve last 10 successful logins. For more details and full login log see <a href="tools.php?page=logs_page&logfile=wp-login.log" target="_blank">wp-login.log</a>.', 'seravo')));
    $base->add_child($postbox->get_ajax_handler('logins-info')->get_component());
  }

  /**
   * AJAX function for last successful logins postbox.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function get_last_successful_logins() {
    $max = 10;
    $logfile = '/data/log/wp-login.log';

    $login_data = \is_readable($logfile) ? \file($logfile) : array();
    if ( $login_data === false ) {
      $login_data = array();
    }

    $login_data = \preg_grep('/SUCCESS/', $login_data);
    if ( $login_data === false ) {
      $login_data = array();
    }

    // If the wp-login.log has less than $max entries check older log files
    if ( \count($login_data) < $max ) {
      // Check the second newest log file (not gzipped yet)
      $login_data2_filename = \glob('/data/log/wp-login.log-[0-9]*[!\.gz]');
      if ( $login_data2_filename === false ) {
        $login_data2_filename = array();
      }
      // There should be only a maximum of one file matching previous criterion, but
      // count the files just in case and choose the biggest index
      $login_data2_count = \count($login_data2_filename) - 1;
      // Merge log file if it exists
      if ( $login_data2_count >= 0 ) {
        // Merge with the first log filelogins_info
        $login_data2 = \file($login_data2_filename[$login_data2_count]);
        if ( $login_data2 !== false ) {
          $login_data2 = \preg_grep('/SUCCESS/', $login_data2);
          if ( $login_data2 !== false ) {
            $login_data = \array_merge($login_data2, $login_data);
          }
        }
      }

      // Opening necessary amount of gzipped log files
      // Find the gzip log files
      $login_data_gz_filename = \glob('/data/log/wp-login.log-[0-9]*.gz');
      if ( $login_data_gz_filename === false ) {
        $login_data_gz_filename = array();
      }
      // Get the number of gzip log files
      // Using the count as an index to go through gzips starting from the newest
      $gz_count = \count($login_data_gz_filename) - 1;
      // Opening gzips and merging to $login_data until enough logins or out of data
      $success_lines = \preg_grep('/SUCCESS/', $login_data);
      if ( $success_lines === false ) {
        $success_lines = array();
      }

      $successful_logins_count = \count($success_lines);
      while ( $successful_logins_count < $max && $gz_count >= 0 ) {
        $gz_lines = \gzfile($login_data_gz_filename[$gz_count]);
        if ( $gz_lines === false ) {
          $gz_lines = array();
        }

        $zipped_data = \preg_grep('/SUCCESS/', $gz_lines);
        if ( $zipped_data === false ) {
          $zipped_data = array();
        }

        $login_data = \array_merge($zipped_data, $login_data);
        --$gz_count;
      }
    }

    // Limit amount of login lines to $max
    $login_data = \array_slice($login_data, -$max);

    // Clean up login lines, remove unnecessary characters
    $total_row_count = \count($login_data);
    for ( $i = 0; $i < $total_row_count; ++$i ) {
      \preg_match_all('/^(?<ip>[.:0-9a-f]+) - (?<name>[\w\-_.*@ ]+) \[(?<datetime>[\d\/\w: +]+)\]/', $login_data[$i], $matches);

      if ( isset($matches['ip'][0]) && isset($matches['name'][0]) && isset($matches['datetime'][0]) ) {
        // If valid line
        $timezone = \get_option('timezone_string');
        $datetime = \DateTime::createFromFormat('d/M/Y:H:i:s T', $matches['datetime'][0]);
        if ( $datetime === false ) {
          continue;
        }

        $datetime->setTimezone(new \DateTimeZone(($timezone === false || $timezone === '') ? 'UTC' : $timezone));
        $date_format = \get_option('date_format', 'Y-m-d');
        $date = \date_i18n($date_format, \strtotime($matches['datetime'][0]));
        $time = $datetime->format(\get_option('time_format'));

        // Fetch login IP and the reverse domain name
        $domain = \gethostbyaddr($matches['ip'][0]);
        $address = ($domain === false || $domain === '') ? $matches['ip'][0] : $domain;

        $login_data[$i] = array( $date . ' ' . $time, $matches['name'][0], $address );
      } else {
        // If invalid line
        unset($login_data[$i]);
      }
    }
    // Re-index the array after unsetting invalid lines
    $login_data = \array_values($login_data);

    if ( $login_data === array() ) {
      $output = Template::error_paragraph(__('No login data available', 'seravo'))->to_html();
    } else {
      // Adding column titles
      $column_titles = array( __('Time', 'seravo'), __('User', 'seravo'), __('Address', 'seravo') );
      $login_data = \array_reverse($login_data);
      $table_component = Template::table_view('result-table', 'result-table th', 'seravo-tooltip', $column_titles, $login_data, true);
      $output = $table_component->to_html();
    }
    return AjaxResponse::response_with_output($output);
  }

  /**
   * Build cruftbox postbox.
   * @param \Seravo\Postbox\Component  $base    Base component to build on.
   * @param \Seravo\Postbox\Postbox    $postbox The postbox to build components for.
   * @param string                     $type    Type of the cruftbox (files/plugins/themes).
   * @return void
   */
  public static function build_cruftbox( Component $base, Postbox\Postbox $postbox, $type ) {
    if ( \property_exists($postbox, 'paragraphs') ) {
      foreach ( $postbox->paragraphs as $paragraph ) {
        $base->add_child(Template::paragraph($paragraph));
      }
    }

    $cruft_section = new Component('', '<div class="seravo-cruft-section" data-cruft-type="' . $type . '">', '</div>');
    $cruft_section->add_child($postbox->get_ajax_handler($postbox->id)->get_component());
    $cruft_section->add_child(Component::from_raw('<div class="cruft-area"></div>'));
    $base->add_child($cruft_section);

    $warning = \sprintf(__('Are you sure you want to proceed? Deleted files can not be recovered.', 'seravo'), $type);
    $base->add_child(Template::confirmation_modal('remove-cruft-' . $type, $warning, __('Proceed', 'seravo'), __('Cancel', 'seravo')));
  }

  /**
   * Get cruft of specific type.
   * @param string $type Cruft type (files/plugins/themes).
   * @return \Seravo\Ajax\AjaxResponse Cruft list response.
   */
  public static function get_cruft( $type ) {
    $cruft = array();
    if ( $type === 'files' ) {
      $files = CruftRemover::list_cruft_files();
      if ( $files !== array() ) {
        $cruft = array(
          array(
            'category' => 'default',
            'title' => '',
            'description' => '',
            'cruft' => $files,
          ),
        );
      }
    } elseif ( $type === 'plugins' ) {
      $plugins = CruftRemover::list_cruft_plugins();
      if ( $plugins !== array() ) {
        $cruft = $plugins;
      }
    } elseif ( $type === 'themes' ) {
      $themes = CruftRemover::list_cruft_themes();
      if ( $themes !== array() ) {
        $cruft = array(
          array(
            'category' => 'default',
            'title' => '',
            'description' => '',
            'cruft' => $themes,
          ),
        );
      }
    } else {
      return AjaxResponse::invalid_request_response();
    }

    return AjaxResponse::response_with_output($cruft, 'data');
  }

  /**
   * Remove given cruft of specific type.
   * @param string $type Cruft type (files/plugins/themes).
   * @return \Seravo\Ajax\AjaxResponse Ajax response with cruft that couldn't be removed.
   */
  public static function remove_cruft( $type ) {
    $cruft = (isset($_POST['cruft']) && $_POST['cruft'] !== '' && $_POST['cruft'] !== array()) ? $_POST['cruft'] : array();

    $failed_to_remove = array();
    if ( $type === 'files' ) {
      $failed_to_remove = CruftRemover::remove_cruft_files($cruft);
    } elseif ( $type === 'plugins' ) {
      $failed_to_remove = CruftRemover::remove_cruft_plugins($cruft);
    } elseif ( $type === 'themes' ) {
      $failed_to_remove = CruftRemover::remove_cruft_themes($cruft);
    } else {
      return AjaxResponse::invalid_request_response();
    }

    return AjaxResponse::response_with_output($failed_to_remove, 'data');
  }

}
