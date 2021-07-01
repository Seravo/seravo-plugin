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
      $poller_demo = new Postbox\SimpleForm('poller-test', 'normal');
      $poller_demo->set_title('Polling Test');
      $poller_demo->set_button_text('Click me');
      $poller_demo->set_button_func(array( __CLASS__, 'long_polling_operation' ));
      $poller_demo->add_paragraph('Click the button to test the poller. It will sleep for 65 seconds and restart Nginx.');
      $poller_demo->add_paragraph('See browsers dev-tools network tab to see what happens.');
      $poller_demo->set_spinner_text('This will take a while...');
      $poller_demo->set_requirements(array( Requirements::CAN_BE_ANY_ENV => true ));
      $page->register_postbox($poller_demo);
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

  }

  TestPage::load();
}
