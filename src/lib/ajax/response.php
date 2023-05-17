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
   * @var bool[]|string[]|mixed[] Data to respond with.
   */
  public $data = array();

  public function __construct() {
    $this->data['success'] = false;
  }

  /**
   * Set whether the request was succesful or not.
   * @param bool $is_success Value for 'success' field.
   * @return void
   */
  public function is_success( $is_success ) {
    $this->data['success'] = $is_success;
  }

  /**
   * Set error message in 'error' field in case there was one.
   * @param string $error Error to be shown for user.
   * @return void
   */
  public function set_error( $error ) {
    $this->data['error'] = $error;
  }

  /**
   * Set the data to be responded with. Data is merged with
   * success and error fields.
   * @param mixed[] $data The response data.
   * @return void
   */
  public function set_data( $data ) {
    $this->data = \array_merge($this->data, $data);
  }

  /**
   * Set raw response data overwriting all current fields.
   * @param mixed[] $response Raw response that won't be tampered with.
   * @return void
   */
  public function set_raw_response( $response ) {
    $this->data = $response;
  }

  /**
   * Jsonify the response data.
   * @return string The response data as JSON.
   */
  public function to_json() {
    $json = \json_encode($this->data);

    if ( false === $json ) {
      $json = self::unknown_error_response()->to_json();
    }

    return $json;
  }

  /**
   * Send the response. No coming back from here.
   * @return void
   */
  public function send() {
    echo $this->to_json();
    \wp_die();
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
   * Get exception response that's supposed to be send on command execution errors.
   * @param string   $command The command that errored.
   * @param int|null $ret_val Optional return value for the errored command.
   * @return \Seravo\Ajax\AjaxResponse Exception response
   */
  public static function command_error_response( $command, $ret_val = null ) {
    if ( $ret_val !== null ) {
      // translators: the command that failed to execute with specified return code.
      $message = __('Error: Command %1$1s failed to execute and returned with status %2$2s. Try running it manually.', 'seravo');
      $error = \sprintf($message, "<code>{$command}</code>", "<code>{$ret_val}</code>");
    } else {
      // translators: the command that failed to execute.
      $message = __('Error: Command %s failed to execute. Try running it manually.', 'seravo');
      $error = \sprintf($message, "<code>{$command}</code>");
    }

    $response = new AjaxResponse();
    $response->is_success(false);
    $response->set_error($error);
    return $response;
  }

  /**
   * Get exception response that's supposed to be send on common error with message cases.
   * @param string $error_message Error message to display.
   * @return \Seravo\Ajax\AjaxResponse Exception response
   */
  public static function error_response( $error_message ) {
    $response = new AjaxResponse();
    $response->is_success(false);
    $response->set_error($error_message);
    return $response;
  }

  /**
   * Get exception response that's supposed to be send on common API errors.
   * @return \Seravo\Ajax\AjaxResponse Exception response
   */
  public static function api_error_response() {
    $response = new AjaxResponse();
    $response->is_success(false);
    $response->set_error(__('An API error occured. Please try again later.', 'seravo'));
    return $response;
  }

  /**
   * Get response that's supposed to be send on common AJAX output cases.
   * @param mixed  $output       AJAX output to display.
   * @param string $output_field The field name to associate the output with when returned.
   * @return \Seravo\Ajax\AjaxResponse Response with output.
   */
  public static function response_with_output( $output, $output_field = 'output' ) {
    $response = new AjaxResponse();
    $response->is_success(true);
    $response->set_data(
      array(
        $output_field => $output,
      )
    );
    return $response;
  }

  /**
   * Get response for requesting polling.
   * @param string $id ID of the program to poll.
   * @param string $type ID type, either 'pid' or 'task'.
   * @return \Seravo\Ajax\AjaxResponse Polling response.
   */
  public static function require_polling_response( $id, $type = 'pid' ) {
    $response = new AjaxResponse();
    $response->is_success(true);
    $response->set_data(
      array(
        'poller_id' => \base64_encode($id),
        'poller_type' => $type,
      )
    );
    return $response;
  }

  /**
   * Get response for errors on user inputs in forms.
   * @param string $message Message to display.
   * @return \Seravo\Ajax\AjaxResponse Error response.
   */
  public static function form_input_error( $message ) {
    $response = new AjaxResponse();
    $response->is_success(true);
    $response->set_data(
      array(
        'output' => $message,
        'dryrun-only' => true,
      )
    );
    return $response;
  }

}
