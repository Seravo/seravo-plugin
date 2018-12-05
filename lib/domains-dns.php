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
    if ( empty( $this->records ) ) {
      return;
    }
    if ( isset( $this->records['error'] ) ) {
      echo '<div><p style="margin-left: 3px;"><b>' . $this->records['error'] . '</b></p></div>';
      return;
    }
    $timestamp = date_create_from_format( 'Y-m-d\TH:i:s.uO', $this->records['timestamp'] );
    echo '<div><p style="margin-left: 3px;"><b>' . __( 'Zone for: ', 'seravo' ) . $this->records['name'] . '</b> <i>(' . __( 'updated: ', 'seravo' ) . date_format( $timestamp, 'Y-m-d H:i O' ) . ')</i></p></div>';
    echo '<table style="margin-bottom: 8px;" class="wp-list-table widefat fixed striped domains" id="dns_zone">';
    echo '<thead>
      <th>' . __( 'Name', 'seravo' ) . '</th>
      <th>' . __( 'TTL', 'seravo' ) . '</th>
      <th> </th>
      <th>' . __( 'Type', 'seravo' ) . '</th>
      <th>' . __( 'Value', 'seravo' ) . '</th>
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
    if ( ! isset( $this->records ) ) {
      return;
    }
    if ( $this->records['pending_activation'] ) {
      echo '<hr>';
      // translators: %s domain of the site
      echo '<p style="max-width:50%;">' . wp_sprintf( __( 'Our systems have detected that <strong>%s</strong> does not point to the Seravo servers. For your protection, manual editing is disabled. Please contact the Seravo customer service if you want changes to be done to the zone in question. You can publish the site yourself when you so desire with the following button:', 'seravo'), $this->records['name'] ) . '</p>';
      wp_nonce_field( 'seravo-zone-nonce' );
      echo '<input type="hidden" name="action" value="change_zone_file">';
      echo '<input type="hidden" name="domain" value="' . $this->records['name'] . '">';
      echo '<textarea type="hidden" name="zonefile" style="display:none;">' .
      $this->compulsory_as_string() . "\n" . $this->editable_as_string() .
      '</textarea>';
      echo '<input style="margin-bottom:8px;" type="submit" value="' . __( 'Publish', 'seravo' ) . '"" formaction="' . esc_url( admin_url( 'admin-post.php' ) ) . '" formmethod="post" >';
      echo '<hr>';
    } else {
      if ( isset( $this->records['error'] ) ) {
        echo '<div><p style="margin-left: 3px;"><b>' . $this->records['error'] . '</b></p></div>';
        return;
      }
      echo '<hr>';
      wp_nonce_field( 'seravo-zone-nonce' );
      echo '<input type="hidden" name="action" value="change_zone_file">';
      echo '<input type="hidden" name="domain" value="' . $this->records['name'] . '">';
      echo '<table>';
      echo '<tr><td style="padding-bottom: 0px;">';
      echo '<h2 style="margin: 0px 0px 5px 0px;">' . __( 'Compulsory Records', 'seravo' ) . '</h2>';
      echo '<p>' . __( 'It is not recommended to edit these records. Please contact the Seravo customer service if you want changes to be done to them.', 'seravo' ) . '</p>';
      echo '</td><td style="padding-bottom: 0px;">';
      echo '<h2 style="margin: 0px 0px 5px 0px;">' . __( 'Editable records', 'seravo' ) . '</h2>';
      echo '<p>' . __( 'Here you can add, edit and delete records. Please do not try to add records conflicting with the compulsory records. They will not be activated.', 'seravo') . '</p>';
      echo '</td></tr>';
      echo '<tr><td style="width:50%">';
      echo '<textarea name="compulsory" readonly style="width:100%" rows="15">';
      echo $this->compulsory_as_string();
      echo '</textarea>';
      echo '</td><td style="width:50%">';
      echo '<textarea name="zonefile" style="width:100%" rows="15">';
      echo $this->editable_as_string();
      echo '</textarea>';
      echo '</td></tr>';
      echo '<tr><td></td><td><input type="submit" class="button alignright" formaction="' . esc_url( admin_url( 'admin-post.php' ) ) . '" 
            formmethod="post" value="' . __( 'Update Zone', 'seravo' ) . '"></td></tr>';
      echo '</table>';
      echo '<hr>';
    }
  }

  public function display_results( $modifications = false, $error = false ) {

    if ( ! $error ) {
      echo '<p><b>' . __('The zone was updated succesfully!', 'seravo') . '</b></p>';
      if ( $modifications ) {
        echo '<div>' . __('The following modifications were done for the zone: ', 'seravo');
        echo '<ol>';
        foreach ( $modifications as $m ) {
          echo '<li>' . $m . '</li>';
        }
        echo '</ol></div>';
      }
    } else {
      echo '<p><b>' . __( 'The zone update failed', 'seravo' ) . '</b></p>';
      echo '<div>' . $error . '</div>';
    }
  }

  public function fetch_dns_records( $url ) {
    $api_query = '/domain/' . $url . '/zone';
    $records = Seravo\API::get_site_data( $api_query );
    if ( is_wp_error( $records ) ) {
      die( $records->get_error_message() );
    }
    $this->records = $records;
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
    return implode( "\n", $this->records['compulsory']['records'] );
  }

  private function editable_as_string() {
    return implode( "\n", $this->records['editable']['records'] );
  }
}
