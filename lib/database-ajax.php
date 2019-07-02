<?php
/**
 * Ajax function for database info
 */

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

use Seravo\Helpers;

/**
 * Turn wp-cli table into HTML table
 *
 * @param array $array WP-CLI table
 *
 * @return string HTML table markup
 */
function seravo_wp_db_info_to_table( $array ) {

  if ( is_array($array) ) {
    $output = '<table class="seravo-wb-db-info-table">';
    foreach ( $array as $i => $value ) {
      // Columns are separated with tabs
      $columns = explode("\t", $value);
      $output .= '<tr>';
      foreach ( $columns as $j => $column ) {
        $output .= '<td>' . ((Helpers::human_file_size($column) == '0B') ? $column : Helpers::human_file_size($column)) . '</td>';
      }
      $output .= '</tr>';
    }
    $output .= '</table>';
    return $output;
  }
  return '';

}

/**
 * Get database total size
 *
 * @return array sizes
 */
function seravo_get_wp_db_info_totals() {

  exec('wp db size', $output);

  return $output;

}

/**
 * Get database table sizes
 *
 * @return array sizes
 */
function seravo_get_wp_db_info_tables() {

  exec('wp db size --size_format=b', $total);

  exec('wp db size --tables --format=json', $json);

  $tables = json_decode($json[0], true);
  $data_folders = array();

  foreach ( $tables as $table ) {
    $size = preg_replace('/[^0-9]/', '', $table['Size']);
    $data_folders[ $table['Name'] ] = array(
      'percentage' => (($size / $total[0]) * 100),
      'human'      => Helpers::human_file_size($size),
      'size'       => $size,
    );
  }
  // Create output array
  return array(
    'data'         => array(
      'human' => Helpers::human_file_size($total[0]),
      'size'  => $total,
    ),
    'data_folders' => $data_folders,
  );
}


/**
 * Compose one string from multiple commands
 *
 * @return string HTML table markup
 */
function seravo_get_wp_db_info() {

  return array(
    'totals' => seravo_wp_db_info_to_table(seravo_get_wp_db_info_totals()),
    'tables' => seravo_get_wp_db_info_tables(),
  );

}

/**
 * Run AJAX request
 */
function seravo_ajax_get_wp_db_info() {
  check_ajax_referer('seravo_database', 'nonce');
  switch ( $_REQUEST['section'] ) {
    case 'seravo_wp_db_info':
      echo wp_json_encode(seravo_get_wp_db_info());
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}
