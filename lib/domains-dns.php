<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

class Seravo_Domains_DNS_Table {

  public function __construct() {
    global $status, $page;
    $records = array( '' => '' );
  }

  public function display() {
    if ( empty($this->records) ) {
      return;
    }
    if ( isset($this->records['error']) ) {
      echo '<div><p style="margin-left: 3px;"><b>' . $this->records['error'] . '</b></p></div>';
      return;
    }
    $timestamp = date_create_from_format('Y-m-d\TH:i:s.uO', $this->records['timestamp']);
    echo '<div><p style="margin-left: 3px;"><b>' . __('Zone for: ', 'seravo') . $this->records['name'] . '</b> <i>(' . __('updated: ', 'seravo') . date_format($timestamp, 'Y-m-d H:i O') . ')</i></p></div>';
    echo '<table style="margin-bottom: 8px; max-width: 80em;" class="wp-list-table widefat fixed striped domains" id="dns_zone">';
    echo '<thead>
      <th>' . __('Name', 'seravo') . '</th>
      <th>' . __('TTL', 'seravo') . '</th>
      <th> </th>
      <th>' . __('Type', 'seravo') . '</th>
      <th>' . __('Value', 'seravo') . '</th>
      </thead>';
    foreach ( $this->records['records'] as $record ) {
      echo '<tr>';
      echo '<td>' . $record['name'] . '</td>
        <td>' . $record['ttl'] . '</td>
        <td> IN </td>
        <td>' . $record['type'] . '</td>
        <td>' . $record['value'] . '</td>';
      echo '</tr>';
    }
    echo '</table>';
  }

  public function display_edit() {
    if ( ! isset($this->records) ) {
      return;
    }

    $error = isset($this->records['error']);

    if ( ! $error && $this->records['pending_activation'] ) {
      echo '<hr>';
      echo '<input type="hidden" name="action" value="change_zone_file">';
      echo '<input type="hidden" name="domain" value="' . $this->records['name'] . '">';
      echo '<table>';
      echo '<tr><td style="padding-bottom: 0px;">';
      // translators: %s domain of the site
      echo '<p style="max-width:50%;">' . wp_sprintf(__('Our systems have detected that <strong>%s</strong> does not point to the Seravo servers. For your protection, manual editing is disabled. Please contact the Seravo customer service if you want changes to be done to the zone in question. You can publish the site yourself when you so desire with the following button:', 'seravo'), $this->records['name']) . '</p>';
      echo '</td></tr>';
      echo '<tr><td>';
      echo '<textarea type="hidden" name="zonefile" style="display: none; font-family: monospace;">' . ($this->compulsory_as_string() . "\n" . $this->editable_as_string()) . '</textarea>';
      echo '<div id="zone-edit-response"></div/>';
      echo '<button id="publish-zone-btn" class="button"' . ($error ? ' disabled' : '') . '>' . __('Publish', 'seravo') . '</button>';
      echo '<div id="zone-update-spinner" style="margin: 4px 10px 0 0"></div>';
      echo '</td></tr>';
      echo '</table>';
      echo '<hr>';
    } else {

      // If $error is true, show empty / disabled fields

      echo '<hr>';
      echo '<input type="hidden" name="action" value="change_zone_file">';
      echo '<input type="hidden" name="domain" value="' . ($error ? '' : $this->records['name']) . '">';
      echo '<table>';

      echo '<tr><td style="padding-bottom: 0px;">';
      echo '<h2 style="margin: 0px 0px 5px 0px;">' . __('Compulsory Records', 'seravo') . '</h2>';
      echo '<p>' . __('It is not recommended to edit these records. Please contact the Seravo customer service if you want changes to be done to them.', 'seravo') . '</p>';
      echo '</td><td style="padding-bottom: 0px;">';
      echo '<h2 style="margin: 0px 0px 5px 0px;">' . __('Editable records', 'seravo') . '</h2>';
      echo '<p>' . __('Here you can add, edit and delete records. Please do not try to add records conflicting with the compulsory records. They will not be activated.', 'seravo') . '</p>';
      echo '</td></tr>';

      echo '<tr><td style="padding:0 0 0 10px;"><div id="zone-fetch-response"><p style="margin:0;"><b>' . ($error ? $this->records['error'] : '') . '</p></b></div></td></tr>';
      echo '<tr><td style="width:50%"><textarea name="compulsory" readonly style="width: 100%; font-family: monospace;" rows="15">' . ($error ? '' : $this->compulsory_as_string()) . '</textarea></td>';
      echo '<td style="width:50%"><textarea name="zonefile" style="width: 100%; font-family: monospace;" rows="15"' . ($error ? ' readonly>' : '>' . $this->editable_as_string()) . '</textarea></td></tr>';
      echo '<tr><td><div id="zone-edit-response"></div></td><td>';
      echo '<button id="update-zone-btn" class="button alignright"' . ($error ? ' disabled' : '') . '>' . __('Update Zone', 'seravo') . '</button>';
      echo '<div id="zone-update-spinner" class="alignright" style="margin: 4px 10px 0 0"></div></td></tr>';
      echo '</table>';
      echo '<hr>';
    }
  }

  private function records_as_str( $records ) {
    $the_string = '';
    $keys = [ 'name', 'ttl', 'type', 'value' ];
    foreach ( $records['records'] as $record ) {
      foreach ( $keys as $k ) {
        $the_string .= $record[ $k ];
        if ( 'value' != $k ) {
          $the_string .= ' ';
        }
        if ( 'ttl' === $k ) {
          $the_string .= 'IN ';
        }
      }
      // Add a newline
      $the_string .= "\r\n";
    }
    return $the_string;
  }

  private function compulsory_as_string() {
    return implode("\n", $this->records['compulsory']['records']);
  }

  private function editable_as_string() {
    return implode("\n", $this->records['editable']['records']);
  }

  public static function fetch_dns_records( $url ) {

    $api_query = '/domain/' . $url . '/zone';

    $records = Seravo\API::get_site_data($api_query);

    if ( is_wp_error($records) ) {
      $records = array( 'error' => $records->get_error_message() );
    }

    return $records;

  }

}
