<?php

class Seravo_Domains_DNS_Table {

  function __construct() {
    global $status, $page;
    $records = array( '' => '' );
  }

  function display() {
    if ( empty($this->records) ) {
      return;
    }
    echo '<div><h2>' . __('Zone for: ', 'seravo') . $this->records['name'] . '</h2>';
    if ( isset($this->records['error']) ) {
      echo $this->records['error'] . '<br>';
      return;
    }
    $timestamp = date_create_from_format('Y-m-d\TH:i:s.uO', $this->records['timestamp']);
    echo '<i>' . __('updated: ', 'seravo') . date_format($timestamp, 'Y-m-d H:i O') . ' </i></div>';
    echo '<table class="wp-list-table widefat fixed striped domains" id="dns_zone">';
    echo '<thead>
        <th>' . __('Name','seravo') . '</th>
        <th>' . __('TTL','seravo') . '</th>
        <th> </th>
        <th>' . __('Type','seravo') . '</th>
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

  function fetch_dns_records( $url ) {
    $api_query = '/domain/' . $url . '/zone';
    $records = Seravo\API::get_site_data($api_query);
    if ( is_wp_error($records) ) {
      die($records->get_error_message());
    }

    $this->records = $records;
  }

}
