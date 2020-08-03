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
 * Get details about database tables
 *
 * @return array tables
 */
function seravo_get_wp_db_details() {
  global $wpdb;

  $long_postmeta_values = $wpdb->get_results("SELECT meta_key, SUBSTRING(meta_value, 1, 30) AS meta_value_snip, LENGTH(meta_value) AS meta_value_length FROM $wpdb->postmeta ORDER BY LENGTH(meta_value) DESC LIMIT 15");
  $cumulative_postmeta_sizes = $wpdb->get_results("SELECT meta_key, SUBSTRING(meta_value, 1, 30) AS meta_value_snip, LENGTH(meta_value) AS meta_value_length, SUM(LENGTH(meta_value)) AS length_sum FROM $wpdb->postmeta GROUP BY meta_key ORDER BY length_sum DESC LIMIT 15");
  $common_postmeta_values = $wpdb->get_results("SELECT SUBSTRING(meta_key, 1, 20) AS meta_key, COUNT(*) AS key_count FROM $wpdb->postmeta GROUP BY meta_key ORDER BY key_count DESC LIMIT 15");
  $autoload_option_count = $wpdb->get_results("SELECT COUNT(*) AS options_count FROM $wpdb->options WHERE autoload = 'yes'");
  $total_autoload_option_size = $wpdb->get_results("SELECT SUM(LENGTH(option_value)) AS total_size FROM $wpdb->options WHERE autoload='yes'");
  $long_autoload_option_values = $wpdb->get_results("SELECT SUBSTRING(option_name, 1, 20) AS option_name, LENGTH(option_value) AS option_value_length FROM $wpdb->options WHERE autoload='yes' ORDER BY LENGTH(option_value) DESC LIMIT 15");
  $common_autoload_option_values = $wpdb->get_results("SELECT SUBSTRING(option_name, 1, 20) AS option_name_start, COUNT(*) AS option_count FROM wp_options WHERE autoload='yes' GROUP BY option_name_start ORDER BY option_count DESC LIMIT 15");

  return array(
    'long_postmeta_values' => $long_postmeta_values,
    'cumulative_postmeta_sizes' => $cumulative_postmeta_sizes,
    'common_postmeta_values' => $common_postmeta_values,
    'autoload_option_count' => $autoload_option_count,
    'total_autoload_option_size' => $total_autoload_option_size,
    'long_autoload_option_values' => $long_autoload_option_values,
    'common_autoload_option_values' => $common_autoload_option_values,
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
    'details' => seravo_get_wp_db_details(),
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
