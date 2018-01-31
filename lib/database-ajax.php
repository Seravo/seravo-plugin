<?php
/**
 * Ajax function for database info
 */

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
        $output .= '<td>' . $column . '</td>';
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

  exec('wp db size --tables', $output);

  return $output;
}


/**
 * Compose one string from multiple commands
 *
 * @return string HTML table markup
 */
function seravo_get_wp_db_info() {

  return seravo_wp_db_info_to_table(seravo_get_wp_db_info_totals()) . seravo_wp_db_info_to_table(seravo_get_wp_db_info_tables());

}

/**
 * Run AJAX request
 */
switch ( $_REQUEST['section'] ) {

  case 'seravo_wp_db_info':
    echo wp_json_encode(seravo_get_wp_db_info());
    break;

  default:
    error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
    break;

}
