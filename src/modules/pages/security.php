<?php

namespace Seravo\Page;

use \Seravo\Helpers;
use \Seravo\CruftRemover;

use \Seravo\Ajax\AjaxHandler;
use \Seravo\Ajax\AjaxResponse;

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
   * @var \Seravo\Page\Security Instance of this page.
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

    add_action('admin_notices', array( __CLASS__, '_seravo_check_security_options' ));
    add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ));

    // AJAX functionality for listing and removing plugins
    add_action('wp_ajax_seravo_list_cruft_plugins', 'Seravo\seravo_ajax_list_cruft_plugins');
    add_action('wp_ajax_seravo_remove_plugins', 'Seravo\seravo_ajax_remove_plugins');
    // AJAX functionality for listing and removing themes
    add_action('wp_ajax_seravo_list_cruft_themes', 'Seravo\seravo_ajax_list_cruft_themes');
    add_action('wp_ajax_seravo_remove_themes', 'Seravo\seravo_ajax_remove_themes');

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
    if ( $screen !== 'tools_page_security_page' ) {
      return;
    }

    wp_enqueue_script('cruftremover-js', SERAVO_PLUGIN_URL . 'js/cruftremover.js', 'jquery', Helpers::seravo_plugin_version());
    wp_enqueue_style('cruftremover-css', SERAVO_PLUGIN_URL . 'style/cruftremover.css', '', Helpers::seravo_plugin_version());

    wp_enqueue_script('seravo-cruftplugins-js', SERAVO_PLUGIN_URL . 'js/cruftplugins.js', '', Helpers::seravo_plugin_version());
    wp_enqueue_script('seravo-cruftthemes-js', SERAVO_PLUGIN_URL . 'js/cruftthemes.js', '', Helpers::seravo_plugin_version());
    wp_enqueue_style('seravo-security-css', SERAVO_PLUGIN_URL . 'style/security.css', '', Helpers::seravo_plugin_version());

    $cruftremover_l10n = array(
      'confirm'       => __('Are you sure you want to proceed? Deleted files can not be recovered.', 'seravo'),
      'confirmation_title' => __('Cruft remove confirmation', 'seravo'),
      'no_cruftfiles' => __('Congratulations! You do not have any unnecessary files around.', 'seravo'),
      'delete'        => __('Delete', 'seravo'),
      'mod_date'      => __('Last modified', 'seravo'),
      'select_all'    => __('Select all files', 'seravo'),
      'filesize'      => __('File size', 'seravo'),
      'failed_to_remove' => __('The following files could not be deleted', 'seravo'),
      'files_removed' => __('The selected files have been removed.', 'seravo'),
    );

    $loc_translation_files = array(
      'confirm'       => __('Are you sure you want to proceed? Deleted files can not be recovered.', 'seravo'),
      'no_cruftfiles' => __('Congratulations! You do not have any unnecessary files around.', 'seravo'),
      'delete'        => __('Delete', 'seravo'),
      'mod_date'      => __('Last modified', 'seravo'),
      'select_all'    => __('Select all files', 'seravo'),
      'filesize'      => __('File size', 'seravo'),
    );
    $loc_translation_plugins = array(
      'inactive'              => __('Inactive Plugins:', 'seravo'),
      'inactive_desc'         => __('These plugins are currently not in use. They can be removed to save disk storage space.', 'seravo'),
      'cache_plugins'         => __('Unnecessary Cache Plugins:', 'seravo'),
      'cache_plugins_desc'    => __('Your website is running on a server that does takes care of caching automatically. Any additional plugins that do caching will not improve the service.', 'seravo'),
      'security_plugins'      => __('Unnecessary Security Plugins:', 'seravo'),
      'security_plugins_desc' => __('Your website runs on a server that is designed to provide a high level of security. Any plugins providing additional security measures will likely just slow down your website.', 'seravo'),
      'db_plugins'            => __('Unnecessary Database Manipulation Plugins:', 'seravo'),
      'db_plugins_desc'       => __('These plugins may cause issues with your database.', 'seravo'),
      'backup_plugins'        => __('Unnecessary Backup Plugins:', 'seravo'),
      'backup_plugins_desc'   => __('Backups of your website are automatically run on the server on a daily basis. Any plugins creating additional backups are redundant and will unnecessesarily fill up your data storage space.', 'seravo'),
      'poor_security'         => __('Unsecure Plugins:', 'seravo'),
      'poor_security_desc'    => __('These plugins have known issues with security.', 'seravo'),
      'bad_code'              => __('Bad Code:', 'seravo'),
      'bad_code_desc'         => __('These plugins code are hard to differentiate from actual malicious codes.', 'seravo'),
      'foolish_plugins'       => __('Foolish Plugins:', 'seravo'),
      'foolish_plugins_desc'  => __('These plugins are known to do foolish things.', 'seravo'),
      'no_cruftplugins'       => __('All the plugins that were found are currently active and do not have any known issues.', 'seravo'),
      'cruftplugins'          => __('The following plugins were found and are suggested to be removed:', 'seravo'),
      'confirm'               => __('Are you sure you want to remove this plugin?', 'seravo'),
      'failure'               => __('Failed to remove plugin', 'seravo'),
      'ajaxurl'               => admin_url('admin-ajax.php'),
      'ajax_nonce'            => wp_create_nonce('seravo_cruftplugins'),
    );
    $loc_translation_themes = array(
      'isparentto'     => __('is parent to: ', 'seravo'),
      'confirm'        => __('Are you sure you want to remove this theme?', 'seravo'),
      'failure'        => __('Failed to remove some themes!', 'seravo'),
      'no_cruftthemes' => __('There are currently no unused themes on the website.', 'seravo'),
      'cruftthemes'    => __('The following themes are inactive and can be removed.', 'seravo'),
      'no_data'       => __('No data returned for the section.', 'seravo'),
      'fail'          => __('Failed to load. Please try again.', 'seravo'),
      'ajaxurl'        => admin_url('admin-ajax.php'),
      'ajax_nonce'     => wp_create_nonce('seravo_cruftthemes'),
    );

    wp_localize_script('cruftremover-js', 'cruftremover_l10n', $cruftremover_l10n);
    wp_localize_script('seravo-cruftplugins-js', 'seravo_cruftplugins_loc', $loc_translation_plugins);
    // Register this for cruftplugins as it uses the loc files. Will be converted when widget rewrite
    wp_localize_script('seravo-cruftplugins-js', 'seravo_cruftfiles_loc', $loc_translation_files);
    wp_localize_script('seravo-cruftthemes-js', 'seravo_cruftthemes_loc', $loc_translation_themes);
  }

  /**
   * Init postboxes on Security page.
   * @param Toolpage $page Page to init postboxes.
   * @return void
   */
  public static function init_postboxes( Toolpage $page ) {

    \Seravo\Postbox\seravo_add_raw_postbox(
      'cruft-plugins',
      __('Unnecessary plugins', 'seravo'),
      array( __CLASS__, 'cruftplugins_postbox' ),
      'tools_page_security_page',
      'column4'
    );

    \Seravo\Postbox\seravo_add_raw_postbox(
      'cruft-themes',
      __('Unnecessary themes', 'seravo'),
      array( __CLASS__, 'cruftthemes_postbox' ),
      'tools_page_security_page',
      'column4'
    );

    /**
     * Security settings postbox
     */
    $security = new Postbox\SettingsForm('security');
    $security->set_title(__('Security', 'seravo'));
    $security->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $security->add_paragraph('Seravo has security built-in. There are however a few extra measures that the site owner can choose to do if their site will not miss any functionality because of it.');
    $security->add_setting_section(self::get_security_settings());
    $page->register_postbox($security);

    /**
     * Check passwords postbox (Beta)
     */
    $passwords = new Postbox\SimpleCommand('check-passwords');
    $passwords->set_title(__('Check passwords (Beta)', 'seravo'));
    $passwords->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $passwords->set_button_text(__('Run', 'seravo'));
    $passwords->set_spinner_text(__('Running the password check', 'seravo'));
    $passwords->set_command('wp-check-passwords');
    $passwords->add_paragraph(__('This tool can be used to run command <code>wp-check-passwords</code> which finds weak passwords from the users of the site. Note: This may fail, if there are many users.', 'seravo'));
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
    $cruft_files = new Postbox\LazyLoader('cruftfiles');
    $cruft_files->set_title(__('Cruft Files', 'seravo'));
    $cruft_files->set_build_func(array( __CLASS__, 'build_cruft_files' ));
    $cruft_files->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $cruft_files->set_ajax_func(array( __CLASS__, 'list_cruft_files' ));

    $remove_cruft_files = new AjaxHandler('remove-cruft-files');
    $remove_cruft_files->set_ajax_func(array( __CLASS__, 'remove_cruft_files' ));
    $cruft_files->add_ajax_handler($remove_cruft_files);
    $page->register_postbox($cruft_files);
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
      if ( get_option($option) === false ) {
        ?>
        <div class="notice notice-error">
          <p>
            <?php
            printf(
              // translators: URL to security page
              __('Please enable all possible <a href="%s">security features</a>. Save settings even if no changes were made to get rid of this notice.', 'seravo'),
              esc_url(get_option('siteurl')) . '/wp-admin/tools.php?page=security_page'
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
      $label = $details;
      $description = '';
      if ( is_array($details) ) {
        $label = $details[0];
        $description = $details[1];
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
    $logfile = dirname(ini_get('error_log')) . '/wp-login.log';
    $login_data = is_readable($logfile) ? file($logfile) : array();
    $login_data = preg_grep('/SUCCESS/', $login_data);

    // If the wp-login.log has less than $max entries check older log files
    if ( count($login_data) < $max ) {
      // Check the second newest log file (not gzipped yet)
      $login_data2_filename = glob('/data/log/wp-login.log-[0-9]*[!\.gz]');
      // There should be only a maximum of one file matching previous criterion, but
      // count the files just in case and choose the biggest index
      $login_data2_count = count($login_data2_filename) - 1;
      // Merge log file if it exists
      if ( $login_data2_count >= 0 ) {
        // Merge with the first log filelogins_info
        $login_data2 = file($login_data2_filename[$login_data2_count]);
        $login_data = array_merge(preg_grep('/SUCCESS/', $login_data2), $login_data);
      }

      // Opening necessary amount of gzipped log files
      // Find the gzip log files
      $login_data_gz_filename = glob('/data/log/wp-login.log-[0-9]*.gz');
      // Get the number of gzip log files
      // Using the count as an index to go through gzips starting from the newest
      $gz_count = count($login_data_gz_filename) - 1;
      // Opening gzips and merging to $login_data until enough logins or out of data
      $successful_logins_count = count(preg_grep('/SUCCESS/', $login_data));
      while ( $successful_logins_count < $max && $gz_count >= 0 ) {
        $zipped_data = preg_grep('/SUCCESS/', gzfile($login_data_gz_filename[$gz_count]));
        $login_data = array_merge($zipped_data, $login_data);
        --$gz_count;
      }
    }

    // Limit amount of login lines to $max
    $login_data = array_slice($login_data, -$max);

    // Clean up login lines, remove unnecessary characters
    $total_row_count = count($login_data);
    for ( $i = 0; $i < $total_row_count; ++$i ) {
      preg_match_all('/^(?<ip>[.:0-9a-f]+) - (?<name>[\w\-_.*@ ]+) \[(?<datetime>[\d\/\w: +]+)\]/', $login_data[$i], $matches);

      if ( isset($matches['ip'][0]) && isset($matches['name'][0]) && isset($matches['datetime'][0]) ) {
        // If valid line
        $timezone = get_option('timezone_string');
        $datetime = \DateTime::createFromFormat('d/M/Y:H:i:s T', $matches['datetime'][0]);
        $datetime->setTimezone(new \DateTimeZone(empty($timezone) ? 'UTC' : $timezone));
        $date = $datetime->format(get_option('date_format'));
        $time = $datetime->format(get_option('time_format'));

        // Fetch login IP and the reverse domain name
        $domain = gethostbyaddr($matches['ip'][0]);
        $address = empty($domain) ? $matches['ip'][0] : $domain;

        $login_data[$i] = array( $date . ' ' . $time, $matches['name'][0], $address );
      } else {
        // If invalid line
        unset($login_data[$i]);
      }
    }
    // Re-index the array after unsetting invalid lines
    $login_data = array_values($login_data);

    $response = new AjaxResponse();
    $response->is_success(true);

    if ( empty($login_data) ) {
      $response->set_data(
        array(
          'output' => Template::error_paragraph(__('No login data available', 'seravo'))->to_html(),
        )
      );
    } else {
      // Adding column titles
      $column_titles = array( __('Time', 'seravo'), __('User', 'seravo'), __('Address', 'seravo') );
      $login_data = array_reverse($login_data);
      $table_component = Template::table_view('result-table', 'result-table th', 'seravo-tooltip', $column_titles, $login_data, true);

      $response->set_data(
        array(
          'output' => $table_component->to_html(),
        )
      );
    }
    return $response;
  }

  /**
   * Build func for cruft files postbox.
   * @param Component       $base    The base of the postbox to add elements.
   * @param Postbox\Postbox $postbox The postbox to build.
   */
  public static function build_cruft_files( Component $base, Postbox\Postbox $postbox ) {
    $base->add_child(Template::paragraph(__('Find and delete any extraneous and potentially harmful files taking up space in the file system. Note that not everything is necessarily safe to delete.', 'seravo')));
    $base->add_child($postbox->get_ajax_handler('cruftfiles')->get_component());
    $base->add_child(Component::from_raw('<div class="cruft-remove-status"></div>'));
    $base->add_child(Component::from_raw('<div class="cruft-area" style="display: none;"><div class="seravo-container"><table class="cruft-entries-table"><tbody class="cruft-entries"></tbody></table></div></div>'));
    $base->add_child(Template::confirmation_modal('remove-cruft-files-modal', __('Are you sure you want to proceed? Deleted files can not be recovered.', 'seravo'), __('Proceed', 'seravo'), __('Cancel', 'seravo')));
  }

  /**
   * AJAX function for fetching cruft files
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function list_cruft_files() {
    $response = new AjaxResponse();
    $response->is_success(true);
    $cruft_files_found = CruftRemover::list_cruft_files();
    $response->set_data(
      array(
        'data' => $cruft_files_found,
      )
    );

    return $response;
  }

  /**
   * AJAX func for removing the found cruft files.
   * @return \Seravo\Ajax\AjaxResponse
   */
  public static function remove_cruft_files() {
    $response = new AjaxResponse();
    $results = array();
    $files = (isset($_POST['deletefile']) && ! empty($_POST['deletefile'])) ? $_POST['deletefile'] : array();

    if ( is_string($files) ) {
      $files = array( $files );
    }
    if ( ! empty($files) ) {
      foreach ( $files as $file ) {
        $legit_cruft_files = get_transient('cruft_files_found'); // Check first that given file or directory is legitimate
        if ( in_array($file, $legit_cruft_files, true) ) {
          $unlink_result = is_dir($file) ? CruftRemover::rmdir_recursive($file, 0) : unlink($file);
          // Log files if removing fails
          if ( $unlink_result === false ) {
            $results[] = $file;
          }
        }
      }
    }

    $response->is_success(true);
    $response->set_data(
      array(
        'data' => $results,
      )
    );

    return $response;
  }

  /**
   * @return void
   */
  public static function cruftplugins_postbox() {
    ?>
    <p>
      <?php _e('Find and remove any plugins that are currently inactive or otherwise potentially harmful. For more information, please read our <a href="https://help.seravo.com/article/165-recommended-plugins" target="_BLANK">recommendations for plugins in our environment</a>.', 'seravo'); ?>
    </p>
    <p>
    <div id="cruftplugins_status">
      <div id="cruftplugins_status_loading">
        <?php _e('Searching for plugins...', 'seravo'); ?>
        <img src="/wp-admin/images/spinner.gif">
      </div>
      <!-- Filled by JS -->
      <div id="cruftplugins_status_loading" style="display: none;">
        <?php _e('Searching for files...', 'seravo'); ?>
        <img src="/wp-admin/images/spinner.gif">
      </div>
    </div>
    </p>
    <?php
  }

  /**
   * @return void
   */
  public static function cruftthemes_postbox() {
    ?>
    <p>
      <?php _e('Find and remove themes that are inactive. For more information, please read our <a href="https://help.seravo.com/article/70-can-i-install-my-own-plugins-and-themes-on-the-website" target="_BLANK">documentation concerning themes and plugins</a>.', 'seravo'); ?>
    </p>
    <p>
    <div id="cruftthemes_status">
      <div id="cruftthemes_status_loading">
        <?php _e('Searching for themes...', 'seravo'); ?>
        <img src="/wp-admin/images/spinner.gif">
      </div>
    </div>
    </p>
    <?php
  }
}
