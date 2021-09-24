<?php

namespace Seravo\Page;

use \Seravo\Shell;

use \Seravo\Ajax;
use \Seravo\Postbox;
use \Seravo\Postbox\Settings;
use \Seravo\Postbox\Component;
use \Seravo\Postbox\Template;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Requirements;

/**
 * Class TestPage
 *
 * TestPage is a page for testing postbox
 * and toolpage features. Only shown in
 * SERAVO_PLUGIN_DEBUG mode.
 *
 * No strings should be translated for this page.
 */
class TestPage extends Toolpage {

  /**
   * @var \Seravo\Page\TestPage|null Instance of this page.
   */
  private static $instance;

  /**
   * Function for creating an instance of the page. This should be
   * used instead of 'new' as there can only be one instance at a time.
   * @return \Seravo\Page\TestPage Instance of this page.
   */
  public static function load() {
    if ( self::$instance === null ) {
      self::$instance = new TestPage();
    }

    return self::$instance;
  }

  /**
   * Constructor for TestPage. Will be called on new instance.
   * Basic page details are given here.
   */
  public function __construct() {
    parent::__construct(
      'Test-page',
      'tools_page_test_page',
      'test_page',
      'Seravo\Postbox\seravo_two_column_postboxes_page'
    );
  }

  /**
   * Will be called for page initialization. Includes scripts
   * and enables toolpage features needed for this page.
   */
  public function init_page() {
    self::init_postboxes($this);

    $this->enable_ajax();
    $this->enable_charts();
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
   * Initialize test-page postboxes.
   * @param \Seravo\Postbox\Toolpage $page The page for postboxes.
   * @return void
   */
  public static function init_postboxes( Toolpage $page ) {
    /**
     * Polling test postbox
     */
    $poller_demo = new Postbox\SimpleForm('poller-test');
    $poller_demo->set_title('Polling Test');
    $poller_demo->set_ajax_func(array( __CLASS__, 'long_polling_operation' ));
    $poller_demo->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $poller_demo->add_paragraph('Click the button to test the poller. It will sleep for 65 seconds and restart Nginx.');
    $poller_demo->add_paragraph('See browsers dev-tools network tab to see what happens.');
    $poller_demo->set_spinner_text('This will take a while...');
    $poller_demo->set_button_text('Click me');
    $page->register_postbox($poller_demo);

    /**
     * Fancy form test postbox
     */
    $fancy_demo = new Postbox\FancyForm('fancy-form-test', 'side');
    $fancy_demo->set_title('FancyForm Test');
    $fancy_demo->set_ajax_func(array( __CLASS__, 'fancy_form_test' ));
    $fancy_demo->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $fancy_demo->add_paragraph('Click the button to test the fancy form. The AJAX function executes <code>wp-test</code>.');
    $fancy_demo->set_spinner_text('Funtsaillaan for a while');
    $fancy_demo->set_title_text('Click the button NOW!');
    $fancy_demo->set_button_text('Click me');
    $page->register_postbox($fancy_demo);

    /**
     * Chart test postbox
     */
    $chart_demo = new Postbox\LazyLoader('chart-test');
    $chart_demo->set_title('Chart Test');
    $chart_demo->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $chart_demo->add_paragraph('You should see a chart below.');
    $chart_demo->set_ajax_func(array( __CLASS__, 'chart_test' ));
    $page->register_postbox($chart_demo);

    /**
     * Chart test postbox
     */
    $nag_demo = new Postbox\Postbox('nag-test', 'side');
    $nag_demo->set_title('Nag Test');
    $nag_demo->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $nag_demo->set_build_func(array( __CLASS__, 'build_nag_test' ));
    $purge_cache_btn = new Ajax\SimpleForm('nag-test');
    $purge_cache_btn->set_button_text('Purge Cache');
    $purge_cache_btn->set_spinner_flip(true);
    $purge_cache_btn->set_ajax_func(
      function() {
        // Use a proper function in real situation
        \exec('wp-purge-cache');
        $response = new Ajax\AjaxResponse();
        $response->is_success(true);
        return $response;
      }
    );
    $purge_cache_btn2 = new Ajax\SimpleForm('nag-test-2');
    $purge_cache_btn2->set_button_text('Purge Cache');
    $purge_cache_btn2->set_spinner_flip(true);
    $purge_cache_btn2->set_ajax_func(
      function() {
        // Use a proper function in real situation
        \exec('wp-purge-cache');
        return Ajax\AjaxResponse::response_with_output(Template::paragraph('This bar returned output :)')->to_html());
      }
    );
    $nag_demo->add_ajax_handler($purge_cache_btn);
    $nag_demo->add_ajax_handler($purge_cache_btn2);
    $page->register_postbox($nag_demo);

    /**
     * Settings test postbox
     */
    $settings_demo = new Postbox\SettingsForm('settings-test');
    $settings_demo->set_title('Settings Test');
    $settings_demo->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
    $settings_demo->add_paragraph('Here you can test the Seravo wrapper for WordPress settings API.');
    $settings_demo->add_setting_section(self::get_demo_settings());
    $page->register_postbox($settings_demo);
  }

  /**
   * Get setting section for the setting demo postbox.
   * @return \Seravo\Postbox\Settings The setting section instance.
   */
  private static function get_demo_settings() {
    $demo_settings = new Settings('demo-settings', 'This title is optional');
    $demo_settings->add_field('seravo-test-setting-enable', 'Enable a feature', '', '', Settings::FIELD_TYPE_BOOLEAN, 'on');
    $demo_settings->add_field('seravo-test-setting-string', 'Give a string', 'write something', '', Settings::FIELD_TYPE_STRING);
    $demo_settings->add_field('seravo-test-setting-integer', 'Give an integer', '', '<p>Integer fields only accept whole numbers</p>', Settings::FIELD_TYPE_INTEGER, 12345);
    $demo_settings->add_field('seravo-test-setting-number', 'Give a number', '', '<p>But number fields accept any numeric value</p>', Settings::FIELD_TYPE_NUMBER, 3.14159);
    return $demo_settings;
  }

  /**
   * AJAX function for testing polling. The function
   * sleeps for 65 seconds and restarts Nginx.
   * @return \Seravo\Ajax\AjaxResponse|mixed Response for long polling operation.
   */
  public static function long_polling_operation() {
    $polling = Ajax\AjaxHandler::check_polling();

    if ( $polling === true ) {
      return Ajax\AjaxResponse::response_with_output('<hr><pre>' . \file_get_contents('/data/log/nginx-restart-test.log') . '</pre><hr>');
    }

    if ( $polling === false ) {
      // Not polling yet
      $command = 'sleep 65 && wp-restart-nginx > /data/log/nginx-restart-test.log';
      $pid = Shell::background_command($command);
      if ( $pid === false ) {
        return Ajax\AjaxResponse::exception_response();
      }
      return Ajax\AjaxResponse::require_polling_response($pid);
    }

    // Not done yet, keep polling
    return $polling;
  }

  /**
   * AJAX function for testing fancy form.
   * The function runs wp-test.
   * @return \Seravo\Ajax\AjaxResponse Response for wp-test.
   */
  public static function fancy_form_test() {
    $retval = null;
    $output = array();
    \exec('wp-test', $output, $retval);

    if ( \count($output) === 0 ) {
      return Ajax\AjaxResponse::command_error_response('wp-test', $retval);
    }

    $message = 'This is bad mmkay?';
    $status_color = Ajax\FancyForm::STATUS_RED;
    $ok = \preg_grep('/OK \(/i', $output);
    if ( $ok !== false && \count($ok) >= 1 && $retval === 0 ) {
      // Success
      $message = "It's all good!";
      $status_color = Ajax\FancyForm::STATUS_GREEN;
    }

    return Ajax\FancyForm::get_response('<pre>' . \implode("\n", $output) . '</pre>', $message, $status_color);
  }

  /**
   * AJAX function for charts. Just returns test data.
   * @return \Seravo\Ajax\AjaxResponse Response with chart data.
   */
  public static function chart_test() {
    $response = new Ajax\AjaxResponse();
    $response->is_success(true);
    $response->set_data(
      array(
        'random_data' => array(
          'First thing' => 30,
          'Second thing' => 50,
          'Third thing' => 20,
        ),
        'output' => '<div id="test-page-test-chart"></div>',
      )
    );
    return $response;
  }

  /**
   * Function for building nag-test postbox.
   * @param \Seravo\Postbox\Component $base    Component to build on.
   * @param \Seravo\Postbox\Postbox   $postbox The nag-test postbox.
   * @return void
   */
  public static function build_nag_test( Component $base, Postbox\Postbox $postbox ) {
    // Normally render this block conditionally
    $notice = new Component('', '<table><tr>', '</tr></table>');
    $notice->add_child(new Component('You have cache! Please purge it now!', '<td><b>', '</b></td>'));
    $notice->add_child($postbox->get_ajax_handler('nag-test')->get_component()->set_wrapper('<td>', '</td>'));
    $base->add_child(Template::nag_notice($notice, 'notice-error', true));
    $notice = new Component('', '<table><tr>', '</tr></table>');
    $notice->add_child(new Component('You have cache! Please purge it now! (2)', '<td><b>', '</b></td>'));
    $notice->add_child($postbox->get_ajax_handler('nag-test-2')->get_component()->set_wrapper('<td>', '</td>'));
    $base->add_child(Template::nag_notice($notice, 'notice-error', true));

    $base->add_child(Template::paragraph('You should see two nags above. The second nag will return output.'));
  }

}
