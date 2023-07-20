<?php

namespace Seravo\API;

class Container {

  const API_URL = "http://localhost:8080/v2";

  public static function reset_shadow( $shadow ) {
    return self::post("/wordpress/shadow-reset/$shadow");
  }

  public static function php_compatibility_check( $version = null ) {
    $data = $version === null ? [] : ['php' => $version];
    return self::post('/wordpress/php-compatibility-check/', $data);
  }

  public static function php_version_set( $version ) {
    return self::post('/wordpress/php-version-set/', ['php' => $version]);
  }

  public static function backup_status() {
    return self::post('/wordpress/backup-status/', []);
  }

  public static function task_status( $id ) {
    return self::get("/tasks/$id");
  }

  public static function version() {
    $version = \get_transient('seravo_container_api_version');

    if ( $version !== false ) {
      return $version;
    }

    $response = self::get('/../');

    if ( \is_wp_error($response) || ! isset($response['version']) ) {
      $version = 0;
    } else {
      $version = $response['version'];
    }

    \set_transient('seravo_container_api_version', $version, 3600);
    return $version;
  }

  private static function get( $query ) {
    return self::request('get', $query);
  }

  private static function put( $query, $data = null ) {
    return self::request('put', $query, $data);
  }

  private static function post( $query, $data = null ) {
    return self::request('post', $query, $data);
  }

  private static function request( $method, $path, $data = null ) {
    $url = self::API_URL . $path;
    $headers = [];

    // Initialize a new cURL session handle.
    $handle = \curl_init($url);

    if ( $handle === false ) {
      // cURL failed to initialize.
      return self::error($method, $path, 'cURL session initialization failure');
    }

    // Set the HTTP method.
    \curl_setopt($handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    // Expect to get response as string.
    \curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

    if ( $data !== null ) {
      // Include data with POST and PUT requests and set the content type to JSON.
      $headers[] = 'Content-Type: application/json';
      \curl_setopt($handle, CURLOPT_POSTFIELDS, \json_encode($data));
    }

    if ( ! empty($headers) ) {
      // Set custom HTTP headers if needed.
      \curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    }

    // Execute the API request.
    $response = \curl_exec($handle);

    if ( $response === false ) {
      // Request failed, status codes are not regarded as failure.
      self::error($method, $path, \curl_error($handle));
    }

    // Validate the response JSON and decode it as associative array.
    $response = \json_decode($response, true);

    if ( $response === null ) {
      return self::error($method, $path, "JSON couldn't be parsed");
    }

    $status = \curl_getinfo($handle, CURLINFO_HTTP_CODE);

    // Only accept HTTP status codes indicating success.
    if ( $status < 200 || $status >= 300 ) {
      // Include extra information from the API response
      $detail = $response['detail'];
      return self::error($method, $path, "HTTP status code $status", $detail);
    }

    // Free up the session resources.
    \curl_close($handle);

    return $response;
  }

  private static function error($method, $path, $message, $extra = '') {
    $code = "seravo-container-$method-error";
    $error = "Container API error on $method to '$path' failed: $message";
    $timestamp = date("F j, Y, g:i a e O");
    $logmsg = "$timestamp $code $error $extra\n";
    error_log($logmsg);
    return new \WP_Error($code, $error);
  }

}
