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
    echo '<div><h2>' . __( 'Zone for: ', 'seravo' ) . $this->records['name'] . '</h2>';
    if ( isset( $this->records['error'] ) ) {
      echo $this->records['error'] . '<br>';
      return;
    }
    $timestamp = date_create_from_format( 'Y-m-d\TH:i:s.uO', $this->records['timestamp'] );
    echo '<i>' . __( 'updated: ', 'seravo' ) . date_format( $timestamp, 'Y-m-d H:i O' ) . ' </i></div>';
    echo '<table class="wp-list-table widefat fixed striped domains" id="dns_zone">';
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
      // translators: %s domain of the site
      echo '<p>' . wp_sprintf( __( "Seravo's systems have detected that <strong>%s</strong> does not point to
       Seravo's servers. For your protection, manual editting is prohibited.
       Please contact Seravo's customer service if you want to changes to the zone.
       You can publish the site yourself when you want to with the following button:", 'seravo'), $this->records['name'] ) . '</p>';
      echo '<form name="seravo_edit_zone" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
      wp_nonce_field( 'seravo-zone-nonce' );
      echo '<input type="hidden" name="action" value="change_zone_file">';
      echo '<input type="hidden" name="domain" value="' . $this->records['name'] . '">';
      echo '<textarea type="hidden" name="zonefile" style="display:none;">' .
      $this->compulsory_as_string() . "\n" . $this->editable_as_string() .
      '</textarea>';
      echo '<input type="submit" value="' . __( 'Publish', 'seravo' ) . '" >';
      echo '</form>';
    } else {
      echo '<form name="seravo_edit_zone" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">';
      wp_nonce_field( 'seravo-zone-nonce' );
      echo '<input type="hidden" name="action" value="change_zone_file">';
      echo '<input type="hidden" name="domain" value="' . $this->records['name'] . '">';
      echo '<h2>' . __( 'Compulsory records', 'seravo' ) . '</h2>';
      echo '<p>' . __( 'These records are not recommended for editting. Please
      contact Seravo customer service if you want to make changes to them.', 'seravo' ) . '</p>';
      echo '<textarea name="compulsory" readonly rows="15" cols="80">';
      echo $this->compulsory_as_string();
      echo '</textarea>';
      echo '<h2>' . __( 'Editable records', 'seravo' ) . '</h2>';
      echo '<p>' . __( 'Here you can add, edit and delete records.
      Please do not try to add records conflicting with compulsory records, since
      they will be stripped off.', 'seravo') . '</p>';
      echo '<textarea name="zonefile" rows="15" cols="80">';
      echo $this->editable_as_string();
      echo '</textarea>';
      echo '<input type="submit">';
      echo '</form>';
    }
  }

  public function display_results( $modifications = false, $error = false ) {

    if ( ! $error ) {
      echo '<h2>' . __('Zone updated succesfully!', 'seravo') . '</h2>';
      if ( $modifications ) {
        echo '<div>' . __('The following modifications were made to the zone: ', 'seravo');
        echo '<ol>';
        foreach ( $modifications as $m ) {
          echo '<li>' . $m . '</li>';
        }
        echo '</ol></div>';
      }
    } else {
      echo '<h2>' . __( 'Zone update failed', 'seravo' ) . '</h2>';
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
