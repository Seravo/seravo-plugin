<?php

namespace Seravo\Ajax;

/**
 * Class AjaxResponse
 *
 * Ajax response should be used for responding to
 * AJAX requests so the responses always have the same format.
 */
class AjaxResponse {

  /**
   * @var bool[]|string[]|mixed[]|mixed Data to respond with.
   */
  public $data = array();

  public function __construct() {
    $this->data['success'] = false;
  }

  /**
   * Set whether the request was succesful or not.
   * @param bool $is_success Value for 'success' field.
   */
  public function is_success( $is_success ) {
    $this->data['success'] = $is_success;
  }

  /**
   * Set error message in 'error' field in case there was one.
   * @param string $error Error to be shown for user.
   */
  public function set_error( $error ) {
    $this->data['error'] = $error;
  }

  /**
   * Set the data to be responded with. Data is merged with
   * success and error fields.
   * @param array $data The response data.
   */
  public function set_data( $data ) {
    $this->data = array_merge($this->data, $data);
  }

  /**
   * Set raw response data overwriting all current fields.
   * @param mixed $response Raw response that won't be tampered with.
   */
  public function set_raw_response( $response ) {
    $this->data = $response;
  }

  /**
   * Jsonify the response data.
   * @return string The response data as JSON.
   */
  public function to_json() {
    $json = json_encode($this->data);

    if ( false === $json ) {
      $json = self::unknown_error_response()->to_json();
    }

    return $json;
  }

  /**
   * Send the response. No coming back from here.
   */
  public function send() {
    echo $this->to_json();
    wp_die();
  }

  /**
   * Get invalid request response that's supposed to be send for invalid requests.
   * @return \Seravo\Ajax\AjaxResponse Invalid request response.
   */
  public static function invalid_request_response() {
    $response = new AjaxResponse();
    $response->is_success(false);
    $response->set_error(__('Error: Your browser made an invalid request!', 'seravo'));
    return $response;
  }

  /**
   * Get unkown error response that's supposed to be send for unknown errors.
   * @return \Seravo\Ajax\AjaxResponse Unknown error response.
   */
  public static function unknown_error_response() {
    $response = new AjaxResponse();
    $response->is_success(false);
    $response->set_error(__('Error: Something went wrong! Please see the php-error.log', 'seravo'));
    return $response;
  }

  /**
   * Get exception response that's supposed to be send on AJAX function exceptions.
   * @return \Seravo\Ajax\AjaxResponse Exception response
   */
  public static function exception_response() {
    $response = new AjaxResponse();
    $response->is_success(false);
    $response->set_error(__("Error: Oups, this wasn't supposed to happen! Please see the php-error.log", 'seravo'));
    return $response;
  }

  /**
   * Get exception response that's supposed to be send on AJAX function exceptions.
   * @return \Seravo\Ajax\AjaxResponse Exception response
   */
  public static function command_error_response( $command ) {
    // translators: the command that failed to execute
    $message = __('Error: Command %s failed to execute. Try running it manually.', 'seravo');
    $error = sprintf($message, "<code>{$command}</code>");

    $response = new AjaxResponse();
    $response->is_success(false);
    $response->set_error($error);
    return $response;
  }

}
