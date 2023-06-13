<?php

namespace Seravo;

use \Seravo\API\SWD;

// Deny direct access to this file
if ( ! \defined('ABSPATH') ) {
  die('Access denied!');
}

function seravo_respond_error_json( $reason = '' ) {
  return \json_encode(
    array(
      'status' => 400,
      'reason' => $reason,
    )
  );
}

function seravo_get_domains_table() {
  if ( Domains::$domains_table === null ) {
    Domains::$domains_table = new Seravo_Domains_List_Table();
  }

  Domains::$domains_table->prepare_items();
  Domains::$domains_table->display();
  \wp_die();
}

function seravo_get_forwards_table() {
  if ( Domains::$mails_table === null ) {
    Domains::$mails_table = new Seravo_Mails_Forward_Table();
  }

  Domains::$mails_table->prepare_items();
  Domains::$mails_table->display();
  \wp_die();
}

function seravo_get_forwards() {
  if ( ! isset($_REQUEST['domain']) ) {
    return;
  }

  $response = SWD::get_domain_mailforwards($_REQUEST['domain']);

  if ( \is_wp_error($response) ) {
    return seravo_respond_error_json($response->get_error_message());
  }

  return \json_encode($response);
}

function seravo_get_dns_table() {
  if ( ! isset($_REQUEST['domain']) ) {
    return;
  }

  $action = $_REQUEST['section'] === 'get_dns_table' ? 'zone' : 'sniff';

  Seravo_DNS_Table::display_zone_table($action, $_REQUEST['domain']);
  \wp_die();
}

function seravo_edit_dns_table() {
  if ( ! isset($_REQUEST['domain']) ) {
    return;
  }

  Seravo_DNS_Table::display_zone_edit($_REQUEST['domain']);
  \wp_die();
}

function seravo_admin_change_zone_file() {

  if ( isset($_REQUEST['zonefile']) && isset($_REQUEST['domain']) ) {
    // Attach the editable records to the compulsory
    $zone = isset($_REQUEST['compulsory']) ? $_REQUEST['compulsory'] . "\n" . $_REQUEST['zonefile'] : $_REQUEST['zonefile'];

    // Remove the escapes that are not needed.
    // This makes \" into "
    $data_str = \str_replace('\"', '"', $zone);
    // This makes \\\\" into \"
    $data_str = \str_replace('\\\\"', '\"', $data_str);
    $data = \explode("\r\n", $data_str);

    $current_state = SWD::get_domain_zone($_REQUEST['domain']);
    if ( \is_wp_error($current_state) ) {
      return seravo_respond_error_json($current_state->get_error_message());
    }

    $updated_state = SWD::update_domain_zone($_REQUEST['domain'], $data);
    if ( \is_wp_error($updated_state) ) {
      return seravo_respond_error_json($updated_state->get_error_message());
    }

    $old_zone = $current_state['editable']['records'];
    $new_zone = $updated_state['editable']['records'];

    $diff = [];

    foreach ( $new_zone as $line ) {
      if ( in_array($line, $old_zone) ) {
        // This line already was in the zonefile
        $diff[] = $line;
      } else {
        // This line is new in the zonefile
        $diff[] = '+' . $line;
      }
    }

    $diff_removed = array_diff($old_zone, $new_zone);
    foreach ( $diff_removed as $line ) {
      $diff[] = '-' . $line;
    }

    $new_zone['diff'] = \implode("\n", $diff);
    return \json_encode($new_zone);

  }

  // 'No data returned'
  return '';
}

function seravo_fetch_dns() {

  if ( isset($_REQUEST['domain']) ) {
    $records = Seravo_Domains_DNS_Table::fetch_dns_records($_REQUEST['domain']);
    if ( \is_wp_error($records) ) {
      return seravo_respond_error_json($records->get_error_message());
    }
    return \json_encode($records);
  }

}

function seravo_set_primary_domain() {
  if ( isset($_REQUEST['domain']) ) {
    $response = SWD::set_primary_domain($_REQUEST['domain']);
    if ( \is_wp_error($response) ) {
      return seravo_respond_error_json($response->get_error_message());
    }
    return \json_encode($response);
  }
}

function seravo_edit_forwards() {

  if ( isset($_REQUEST['domain']) && ! empty($_REQUEST['domain']) ) {

    $domain = $_REQUEST['domain'];
    $old_source = isset($_REQUEST['old_source']) ? $_REQUEST['old_source'] : '';
    $new_source = isset($_REQUEST['new_source']) ? $_REQUEST['new_source'] : '';
    $destinations = isset($_REQUEST['destinations']) ? \explode("\n", $_REQUEST['destinations']) : '';

    function create_forward( $domain, $source, $destinations ) {
      if ( empty($source) || empty($destinations) ) {
        return seravo_respond_error_json(__('All fields are required!', 'seravo'));
      }

      // Parse invalid destinations
      $destinations_parsed = array();
      foreach ( $destinations as $key => $destination ) {
        if ( \strpos($destination, '@') > 0 && \strpos($destination, '.') > 0 ) {
          $destinations_parsed[] = \trim($destination);
        } elseif ( empty($destination) ) {
          unset($destinations[$key]);
        }
      }

      $forwards = array(
        'source' => $source,
        'destinations' => $destinations_parsed,
      );

      $response = SWD::update_domain_mailforwards($domain, $forwards);

      if ( \is_wp_error($response) ) {
        return \json_encode(
          array(
            'status' => 200,
            'message' => \sprintf(__("Some of the changes weren't made", 'seravo'), $source . '@' . $domain),
          )
        );
      }

      return \json_encode(
        array(
          'status' => 200,
          // translators: %s is an email address
          'message' => \sprintf(__('Forwards for %s have been set', 'seravo'), $source . '@' . $domain),
        )
      );
    }

    function delete_forward( $domain, $source ) {
      $forwards = array(
        'source' => $source,
        'destinations' => array(),
      );

      $response = SWD::update_domain_mailforwards($domain, $forwards);

      if ( \is_wp_error($response) ) {
        return seravo_respond_error_json($response->get_error_message());
      }

      return \json_encode(
        array(
          'status' => 200,
          'message' => __('The forwards were deleted', 'seravo'),
        )
      );
    }
    if ( $old_source === '' && $new_source !== '' && ! empty($destinations) ) {
        // Create a new source (or replace)
        return create_forward($domain, $new_source, $destinations);
    }
    if ( $old_source !== '' && $new_source === '' && empty($destinations) ) {
        // Delete a source
        return delete_forward($domain, $old_source);
    }

    if ( $old_source !== '' && $new_source !== '' ) {
        // Edit an existing source
        if ( $old_source === $new_source ) {
        // Don't modify the source
        return create_forward($domain, $old_source, $destinations);
        }
        // Modify the source by creating a new one and deleting the old one
        $new = \json_decode(create_forward($domain, $new_source, $destinations), true);
        if ( isset($new['status']) && $new['status'] === 200 ) {
        $old = \json_decode(delete_forward($domain, $old_source), true);
        if ( isset($old['status']) && $old['status'] === 200 ) {
          return \json_encode(
            array(
              'status' => 200,
              'message' => __('The forwards were modified', 'seravo'),
            )
          );
        }
        return seravo_respond_error_json(__("Something went wrong, the old source couldn't be removed.", 'seravo'));
        }
        return seravo_respond_error_json(__("Something went wrong, the forwards couln't be modified.", 'seravo'));
    }
  }
}

function seravo_publish_domain() {
  if ( isset($_REQUEST['domain']) ) {
    $response = SWD::publish_domain($_REQUEST['domain']);
    if ( \is_wp_error($response) ) {
      return seravo_respond_error_json($response->get_error_message());
    }
    error_log('PUBLISH');
    error_log(print_r($response, true));
    return \json_encode($response);
  }
}

function seravo_ajax_domains() {

  \check_ajax_referer('seravo_domains', 'nonce');

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

    case 'publish':
      echo seravo_publish_domain();
      break;

    default:
      \error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  \wp_die();

}
