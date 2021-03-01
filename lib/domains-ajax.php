<?php

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_respond_error_json( $reason = '' ) {
  return json_encode(
    array(
      'status' => 400,
      'reason' => $reason,
    )
  );
}

function seravo_get_domains_table() {
  if ( Domains::$domains_table === null ) {
    // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
    Domains::$domains_table = new Seravo_Domains_List_Table();
  }

  Domains::$domains_table->prepare_items();
  Domains::$domains_table->display();
  wp_die();
}

function seravo_get_forwards_table() {
  if ( Domains::$mails_table === null ) {
    // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
    Domains::$mails_table = new Seravo_Mails_Forward_Table();
  }

  Domains::$mails_table->prepare_items();
  Domains::$mails_table->display();
  wp_die();
}

function seravo_get_forwards() {
  if ( ! isset($_REQUEST['domain']) ) {
    return;
    wp_die();
  }

  $response = API::get_site_data('/domain/' . $_REQUEST['domain'] . '/mailforwards');
  if ( is_wp_error($response) ) {
    return seravo_respond_error_json($response->get_error_message());
    wp_die();
  }

  return json_encode($response);
}

function seravo_get_dns_table() {
  if ( ! isset($_REQUEST['domain']) ) {
    return;
    wp_die();
  }

  if ( $_REQUEST['section'] === 'get_dns_table' ) {
    $action = 'zone';
  } else {
    $action = 'sniff';
  }

  Seravo_DNS_Table::display_zone_table($action, $_REQUEST['domain']);
  wp_die();
}

function seravo_edit_dns_table() {
  if ( ! isset($_REQUEST['domain']) ) {
    return;
    wp_die();
  }

  Seravo_DNS_Table::display_zone_edit($_REQUEST['domain']);
  wp_die();
}

function seravo_admin_change_zone_file() {

  $response = '';

  if ( isset($_REQUEST['zonefile']) && isset($_REQUEST['domain']) ) {
    // Attach the editable records to the compulsory
    if ( $_REQUEST['compulsory'] ) {
      $zone = $_REQUEST['compulsory'] . "\n" . $_REQUEST['zonefile'];
    } else {
      $zone = $_REQUEST['zonefile'];
    }

    // Remove the escapes that are not needed.
    // This makes \" into "
    $data_str = str_replace('\"', '"', $zone);
    // This makes \\\\" into \"
    $data_str = str_replace('\\\\"', '\"', $data_str);
    $data = explode("\r\n", $data_str);

    $response = API::update_site_data($data, '/domain/' . $_REQUEST['domain'] . '/zone', array( 200, 400 ));
    if ( is_wp_error($response) ) {
      return seravo_respond_error_json($response->get_error_message());
      wp_die();
    }

    // Create 'diff' field by combining modified lines with the
    // the untouched records and remove the duplicate lines
    $response_decoded = json_decode($response, true);
    if ( isset($response_decoded['diff']) && strlen($response_decoded['diff']) > 0 ) {
      $records = Seravo_DNS_Table::fetch_dns_records('zone', $_REQUEST['domain']);
      if ( ! isset($records['error']) ) {
        $zone_diff = array();
        $diff = explode("\n", $response_decoded['diff']);
        // Go through the diff lines
        foreach ( $diff as $line ) {
          // Only lines prefixed with + or - are accepted,
          // not lines with +++ or ---
          if ( substr($line, 0, 1) === '+' && substr($line, 1, 1) !== '+' ) {
            // Find the line matching the modified one and prefix it with '+'
            foreach ( $records['editable']['records'] as $index => $record ) {
              if ( $line === '+' . $record ) {
                $records['editable']['records'][$index] = $line;
                break;
              }
            }
          } elseif ( substr($line, 0, 1) === '-' && substr($line, 1, 1) !== '-' ) {
            // Removed lines (-) can be added as they are
            array_push($zone_diff, $line);
          }
        }
        $zone_diff = array_merge($records['editable']['records'], $zone_diff);

        $response_decoded['diff'] = implode("\n", $zone_diff);
        return json_encode($response_decoded);
      }
    }
  } else {
    // 'No data returned'
    return;
  }

  return $response;

  wp_die();

}

function seravo_fetch_dns() {

  if ( isset($_REQUEST['domain']) ) {
    $records = Seravo_Domains_DNS_Table::fetch_dns_records($_REQUEST['domain']);
    if ( is_wp_error($records) ) {
      return seravo_respond_error_json($records->get_error_message());
      wp_die();
    }
    return json_encode($records);
  }

  // 'No data returned'
  return;

  wp_die();

}

function seravo_set_primary_domain() {
  if ( isset($_REQUEST['domain']) ) {
    $response = API::update_site_data(array( 'domain' => $_REQUEST['domain'] ), '/primary_domain', array( 200 ), 'POST');
    if ( is_wp_error($response) ) {
      return seravo_respond_error_json($response->get_error_message());
      wp_die();
    }
    return $response;
  }

  // 'No data returned'
  return;

  wp_die();

}

function seravo_edit_forwards() {

  if ( isset($_REQUEST['domain']) && ! empty($_REQUEST['domain']) ) {

    $domain = $_REQUEST['domain'];
    $old_source = isset($_REQUEST['old_source']) ? $_REQUEST['old_source'] : '';
    $new_source = isset($_REQUEST['new_source']) ? $_REQUEST['new_source'] : '';
    $destinations = isset($_REQUEST['destinations']) ? explode("\n", $_REQUEST['destinations']) : '';

    function create_forward( $domain, $source, $destinations ) {
      if ( empty($source) || empty($destinations) ) {
        return seravo_respond_error_json(__('All fields are required!', 'seravo'));
      }

      // Parse invalid destinations
      $destinations_parsed = array();
      foreach ( $destinations as $key => $destination ) {
        if ( strpos($destination, '@') > 0 && strpos($destination, '.') > 0 ) {
          array_push($destinations_parsed, trim($destination));
        } elseif ( empty($destination) ) {
          unset($destinations[$key]);
        }
      }

      $endpoint = '/domain/' . $domain . '/mailforwards';
      $forwards = array(
        'source' => $source,
        'destinations' => $destinations_parsed,
      );

      $response = API::update_site_data($forwards, $endpoint, array( 200, 404 ), 'POST');
      if ( is_wp_error($response) ) {
        return seravo_respond_error_json($response->get_error_message());
        wp_die();
      }

      // Make sure it actually was added
      $response_json = json_decode($response, true);
      foreach ( $response_json['forwards'] as $forward ) {
        if ( $forward['source'] === $source ) {
          if ( empty(array_diff($destinations, $forward['destinations'])) ) {
            return json_encode(
              array(
                'status' => 200,
                // translators: %s is an email address
                'message' => sprintf(__('Forwards for %s have been set', 'seravo'), $source . '@' . $domain),
              )
            );
          } else {
            break;
          }
        }
      }

      return json_encode(
        array(
          'status' => 200,
          'message' => sprintf(__('Some of the changes weren\'t made', 'seravo'), $source . '@' . $domain),
        )
      );
    }

    function delete_forward( $domain, $source ) {
      $endpoint = '/domain/' . $domain . '/mailforwards';
      $forwards = array(
        'source' => $source,
        'destinations' => array(),
      );

      $response = API::update_site_data($forwards, $endpoint, array( 200, 404 ), 'POST');
      if ( is_wp_error($response) ) {
        return seravo_respond_error_json($response->get_error_message());
        wp_die();
      }

      return json_encode(
        array(
          'status' => 200,
          'message' => __('The forwards were deleted', 'seravo'),
        )
      );
    }

    if ( $old_source === '' && $new_source !== '' && ! empty($destinations) ) {
      // Create a new source (or replace)
      return create_forward($domain, $new_source, $destinations);
    } elseif ( $old_source !== '' && $new_source === '' && empty($destinations) ) {
      // Delete a source
      return delete_forward($domain, $old_source);
    } elseif ( $old_source !== '' && $new_source !== '' ) {
      // Edit an existing source
      if ( $old_source === $new_source ) {
        // Don't modify the source
        return create_forward($domain, $old_source, $destinations);
      } else {
         // Modify the source by creating a new one and deleting the old one
        $new = json_decode(create_forward($domain, $new_source, $destinations), true);
        if ( isset($new['status']) && $new['status'] === 200 ) {
          $old = json_decode(delete_forward($domain, $old_source), true);
          if ( isset($old['status']) && $old['status'] === 200 ) {
            return json_encode(
              array(
                'status' => 200,
                'message' => __('The forwards were modified', 'seravo'),
              )
            );
          } else {
            return seravo_respond_error_json(__('Something went wrong, the old source couldn\'t be removed.', 'seravo'));
          }
        } else {
          return seravo_respond_error_json(__('Something went wrong, the forwards couln\'t be modified.', 'seravo'));
        }
      }
    }
  }
  // 'No data returned'
  return;
}

function seravo_ajax_domains() {

  check_ajax_referer('seravo_domains', 'nonce');

  switch ( $_REQUEST['section'] ) {
    case 'update_zone':
      echo seravo_admin_change_zone_file();
      break;

    case 'fetch_dns':
      echo seravo_fetch_dns();
      break;

    case 'get_domains_table':
      seravo_get_domains_table();
      break;

    case 'get_dns_table':
    case 'sniff_dns_table':
      seravo_get_dns_table();
      break;

    case 'edit_dns_table':
      seravo_edit_dns_table();
      break;

    case 'set_primary_domain':
      echo seravo_set_primary_domain();
      break;

    case 'get_forwards_table':
      seravo_get_forwards_table();
      break;

    case 'fetch_forwards':
      echo seravo_get_forwards();
      break;

    case 'edit_forward':
      echo seravo_edit_forwards();
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();

}
