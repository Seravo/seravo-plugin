<?php
class Seravo_Domains_DNS_Table {

  function __construct() {
    global $status, $page;
    $records = array('' => '' );
  }

  function display() {
    if( empty($this->records)){
      return;
    }
    echo '<div><h2>' . __('Zone for: ', 'seravo') . $this->records['name'] . '</h2>';
    if( isset($this->records['error'])){
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
        <th>' . __('Value', 'seravo') .'</th>
    </thead>';
    foreach ($this->records['records'] as $record){
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

  function fetch_dns_records($url) {
    $site = getenv('USER');

    $ch = curl_init('http://localhost:8888/v1/site/' . $site . '/domain/' . $url . '/zone');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-Api-Key: ' . getenv('SERAVO_API_KEY') ));
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ( curl_error($ch) || $httpcode !== 200 ) {
      error_log('SWD API error ' . $httpcode . ': ' . curl_error($ch));
      echo '<b>' . $url . '</b><br>';
      die(__('API call failed. Aborting. The error has been logged.', 'seravo'));
    }

    curl_close($ch);

    $data = json_decode($response, true);
    $this->records = $data;
  }

}
?>
