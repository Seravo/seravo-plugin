<?php
/**
 * File for test postboxes page. No need to
 * translate anything here.
 */

namespace Seravo;

use \Seravo\Ajax;
use \Seravo\Postbox;
use \Seravo\Postbox\Toolpage;
use \Seravo\Postbox\Requirements;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('TestPage') ) {
  class TestPage {

    /**
     * Initialize test-page.
     */
    public static function load() {
      $page = new Toolpage('tools_page_test_page');

      self::init_test_postboxes($page);

      $page->enable_ajax();
      $page->register_page();
    }

    /**
     * Initialize test-page postboxes.
     * @param \Seravo\Postbox\Toolpage $page The page for postboxes.
     */
    public static function init_test_postboxes( Toolpage $page ) {
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
      $fancy_demo->set_title('FancyForm test');
      $fancy_demo->set_ajax_func(array( __CLASS__, 'fancy_form_test' ));
      $fancy_demo->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
      $fancy_demo->add_paragraph('Click the button to test the fancy form. The AJAX function executes <code>wp-test</code>.');
      $fancy_demo->set_spinner_text('Funtsaillaan for a while');
      $fancy_demo->set_title_text('Click the button NOW!');
      $fancy_demo->set_button_text('Click me');
      $page->register_postbox($fancy_demo);

    }

    /**
     * AJAX function for testing polling. The function
     * sleeps for 65 seconds and restarts Nginx.
     * @return \Seravo\Ajax\AjaxResponse|mixed Response for long polling operation.
     */
    public static function long_polling_operation() {
      $polling = Ajax\AjaxHandler::check_polling();

      if ( $polling === true ) {
        // Done polling
        $response = new Ajax\AjaxResponse();
        $response->is_success(true);
        $response->set_data(
          array(
            'output' => '<pre>' . file_get_contents('/data/log/nginx-restart-test.log') . '</pre>',
          )
        );
        return $response;
      }

      if ( $polling === false ) {
        // Not polling yet
        $command = 'sleep 65 && wp-restart-nginx > /data/log/nginx-restart-test.log';
        $pid = Shell::backround_command($command);
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
      exec('wp-test', $output, $retval);

      if ( count($output) === 0 ) {
        return Ajax\AjaxResponse::command_error_response('wp-test');
      }

      $message = 'This is bad mmkay?';
      $status_color = Ajax\FancyForm::STATUS_RED;
      if ( count(preg_grep('/OK \(/i', $output)) >= 1 && $retval === 0 ) {
        // Success
        $message = "It's all good!";
        $status_color = Ajax\FancyForm::STATUS_GREEN;
      }

      $response = new Ajax\AjaxResponse();
      $response->is_success(true);
      $response->set_data(
        array(
          'output' => '<pre>' . implode("\n", $output) . '</pre>',
          'title' => $message,
          'color' => $status_color,
        )
      );
      return $response;
    }

  }

  TestPage::load();
}
