<?php

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists( 'Seravo_Domains_DNS_Table' ) ) {
  require_once dirname( __FILE__ ) . '/domains-dns.php';
}

function seravo_respond_error_json( $reason = '' ) {
  return json_encode( array(
    'status' => 400,
    'reason' => $reason,
  ) );
}

function seravo_admin_change_zone_file() {

  $response = '';

  if ( isset( $_REQUEST['zonefile'] ) && isset( $_REQUEST['domain'] ) ) {
    // Attach the editable records to the compulsory
    if ( $_REQUEST['compulsory'] ) {
      $zone = $_REQUEST['compulsory'] . "\n" . $_REQUEST['zonefile'];
    } else {
      $zone = $_REQUEST['zonefile'];
    }

    // Remove the escapes that are not needed.
    // This makes \" into "
    $data_str = str_replace( '\"', '"', $zone );
    // This makes \\\\" into \"
    $data_str = str_replace( '\\\\"', '\"', $data_str );
    $data = explode( "\r\n", $data_str );

    $response = Seravo\API::update_site_data( $data, '/domain/' . $_REQUEST['domain'] . '/zone', [ 200, 400 ] );
    if ( is_wp_error( $response ) ) {
      return seravo_respond_error_json( $response->get_error_message() );
      wp_die();
    }
  } else {
    // 'No data returned'
    return;
  }

  return $response;

  wp_die();

}

function seravo_fetch_dns() {

  if ( isset( $_REQUEST['domain'] ) ) {
    $records = Seravo_Domains_DNS_Table::fetch_dns_records( $_REQUEST['domain'] );
    if ( is_wp_error( $records ) ) {
      return seravo_respond_error_json( $records->get_error_message() );
      wp_die();
    }
    return json_encode($records);
  }

  // 'No data returned'
  return;

  wp_die();

}

function seravo_ajax_domains() {

  check_ajax_referer( 'seravo_domains', 'nonce' );

  switch ( $_REQUEST['section'] ) {
    case 'update_zone':
      echo seravo_admin_change_zone_file();
      break;

    case 'fetch_dns':
      echo seravo_fetch_dns();
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();

}
