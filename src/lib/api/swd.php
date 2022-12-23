<?php

namespace Seravo\API;

class SWD {

  public static function get_site_info() {
    return self::get('/');
  }

  public static function update_site_info( $updates, $contacts, $webhooks ) {
    $data = [
      'seravo_updates' => $updates,
      'contact_emails' => $contacts,
      'notification_webhooks_json' => $webhooks,
    ];

    return self::put('/', $data);;
  }

  public static function get_site_shadows() {
    return self::get('/shadows/');
  }

  public static function get_site_shadow( $shadow ) {
    return self::get("/shadows/$shadow");
  }

  public static function get_site_domains() {
    return self::get('/domains/');
  }

  public static function set_primary_domain( $domain ) {
    return self::post("/domains/$domain/primary", []);
  }

  public static function get_domain_zone( $domain ) {
    return self::get("/domains/$domain/zone");
  }

  public static function sniff_domain_zone( $domain ) {
    return self::get("/domains/$domain/sniff");
  }

  public static function update_domain_zone( $domain, $records ) {
    return self::put("/domains/$domain/zone", ['items' => $records]);
  }

  public static function get_domain_mailforwards( $domain ) {
    return self::get("/domains/$domain/mailforwards/");
  }

  public static function update_domain_mailforwards( $domain, $forwards ) {
    return self::post("/domains/$domain/mailforwards/", $forwards);
  }

  public static function publish_domain( $domain ) {
    return self::post("/domains/$domain/publish" );
  }

  private static function get( $query ) {
    return self::request('GET', $query);
  }

  private static function put( $query, $data = null ) {
    return self::request('PUT', $query, $data);
  }

  private static function post( $query, $data = null ) {
    return self::request('POST', $query, $data);
  }

  private static function request( $method, $path, $data = null ) {
    $url = "http://localhost:8888/v2/site$path";
    $user = \wp_get_current_user();
    $headers = [];

    // Initialize a new cURL session handle.
    $handle = \curl_init($url);

    if ( $handle === false ) {
      // cURL failed to initialize.
      return self::error($method, $path, 'cURL session initialization failure');
    }

    if ( $user instanceof \WP_User ) {
      // If logged in, include the username in headers for logs.
      $auth = \base64_encode($user->user_login . ':xxx');
      $headers[] = "Authorization: Basic $auth";
    }

    // Set the HTTP method.
    \curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
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
      return self::error($method, $path, "HTTP status code $status", $response);
    }

    // Free up the session resources.
    \curl_close($handle);

    return $response;
  }

  private static function error($method, $path, $message, $response = null) {
    $code = "seravo-swd-$method-error";
    $error = "SWD API error on $method to '$path' failed: $message";

    return new \WP_Error($code, $error);
  }

}
