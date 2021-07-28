<?php
namespace Seravo;

/**
 * Class API
 *
 * Class for accessing and modifying site-related data.
 */
class API {

  /**
   * Get various data from the site API for the current site.
   * @param string $api_query          The API endpoint with the neccesary parameters.
   * @param int[]  $handled_http_codes HTTP codes that are handled. Others will return \WP_Error.
   * @return \WP_Error|mixed[] Decoded data returned or WP_Error object if request failed or JSON decode failed.
   */
  public static function get_site_data( $api_query = '', $handled_http_codes = array( 200 ) ) {
    $site = getenv('USER');

    $ch = curl_init('http://localhost:8888/v1/site/' . $site . $api_query);
    if ( $ch === false ) {
      // CurlHandle couldn't be created
      return new \WP_Error('seravo-api-get-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-Api-Key: ' . getenv('SERAVO_API_KEY') ));

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for errors
    if ( curl_error($ch) || ! in_array($httpcode, $handled_http_codes) || is_bool($response) ) {
      error_log('SWD API (' . $api_query . ') error ' . $httpcode . ': ' . curl_error($ch));
      curl_close($ch);
      return new \WP_Error('seravo-api-get-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
    }

    curl_close($ch);

    $decoded = json_decode($response, true);
    if ( $decoded === false ) {
      return new \WP_Error('seravo-api-json-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
    }
    return $decoded;
  }

  /**
   * @param mixed[] $data               JSON encodeable data to post.
   * @param string  $api_query          The API endpoint.
   * @param int[]   $handled_http_codes HTTP codes that are handled. Others will return \WP_Error.
   * @param string  $method             The HTTP method to use. Default is 'PUT'.
   * @return \WP_Error|string Raw data returned or WP_Error object if request failed or JSON decode/encode failed.
   */
  public static function update_site_data( $data, $api_query = '', $handled_http_codes = array( 200 ), $method = 'PUT' ) {
    $data_json = json_encode($data);
    if ( $data_json === false ) {
      return new \WP_Error('seravo-api-json-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
    }

    $site = getenv('USER');

    $ch = curl_init('http://localhost:8888/v1/site/' . $site . $api_query);
    if ( $ch === false ) {
      return new \WP_Error('seravo-api-put-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
    }

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      array(
        'X-Api-Key: ' . getenv('SERAVO_API_KEY'),
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_json),
      )
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for errors
    if ( curl_error($ch) || ! in_array($httpcode, $handled_http_codes) || is_bool($response) ) {
      error_log('SWD API (' . $api_query . ') error ' . $httpcode . ': ' . curl_error($ch));
      curl_close($ch);
      return new \WP_Error('seravo-api-put-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
    }

    return $response;
  }
}
