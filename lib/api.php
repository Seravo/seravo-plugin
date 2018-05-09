<?php
/*
 * Description: File for accessing and modifying site-related data.
 */

namespace Seravo;

 // Deny direct access
 if ( ! defined('ABSPATH') ) {
   die('Access denied!');
 }

 if ( ! class_exists('API') ) {

   class API {

     /**
      * Get various data from the site API for the current site.
      */
     public static function get_site_data( $api_query = '' ) {
       $site = getenv('USER');
       $ch = curl_init('http://localhost:8888/v1/site/' . $site . $api_query );
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-Api-Key: ' . getenv('SERAVO_API_KEY') ));
       $response = curl_exec($ch);
       $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

       // Check for errors
       if ( curl_error($ch) || $httpcode !== 200 ) {
         error_log('SWD API (' . $api_query . ') error ' . $httpcode . ': ' . curl_error($ch));
         curl_close($ch);
         return new WP_Error('seravo-api-get-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
       }

       curl_close($ch);
       $data = json_decode($response, true);
       return $data;
     }

     public static function update_site_data( $data, $api_query = '' ) {
       $data_json = json_encode($data);
       $site = getenv('USER');
       $ch = curl_init('http://localhost:8888/v1/site/' . $site . $api_query);

       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
           'X-Api-Key: ' . getenv('SERAVO_API_KEY'),
           'Content-Type: application/json',
           'Content-Length: ' . strlen($data_json),
       ));
       curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

       $response = curl_exec($ch);
       $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

       // Check for errors
       if ( curl_error($ch) || $httpcode !== 200 ) {
         error_log('SWD API (' . $api_query . ') error ' . $httpcode . ': ' . curl_error($ch));
         curl_close($ch);
         return new WP_Error('seravo-api-put-fail', __('API call failed. Aborting. The error has been logged.', 'seravo'));
       }

       return $response;
     }
   }
 }
 ?>
