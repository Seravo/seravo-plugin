<?php
if ( ! current_user_can( 'level_10' ) ) {
  wp_die(
     '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
     '<p>' . __( 'Sorry, you are not allowed to access domains.', 'seravo' ) . '</p>',
     403
  );
}

if ( ! class_exists('WP_List_Table') ) {
  require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Seravo_Mails_Forward_Table extends WP_List_Table {

  public function __construct() {
      // Set parent defaults
    parent::__construct(
    array(
      'singular' => 'mail-forward',
      'plural'   => 'mail-forwards',
      'ajax'     => false,
    )
    );
  }

  public function display_tablenav( $which ) {
    if ( $which === 'top' ) {
        echo '<hr style="margin: 15px 0px;">';
    }
  }

  public function get_columns() {
    $columns = array(
      'cb'           => '<input type="checkbox">', // Render a checkbox instead of text
      'source'       => __( 'Source', 'seravo' ),
      'destinations' => __( 'Destinations', 'seravo' ),
    );
    return $columns;
  }

  public function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'source':
      case 'destionations':
         return $item[ $column_name ];
      default:
        return print_r($item, true); // Show the whole array for troubleshooting purposes
    }
  }

  public function get_sortable_columns() {
    return array( 'source' => array( 'source', false ) );     // true means it's already sorted
  }

  public function column_cb( $item ) {
    return sprintf(
        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
          // Let's simply repurpose the table's singular label ("domain")
        /*$1%s*/ $this->_args['singular'],
          // The value of the checkbox should be the source
        /*$2%s*/ $item['source']
    );
  }

  public function column_source( $item ) {
    return '<b>' . $item['source'] . '@' . $_GET['domain'] . '</b>';
  }

  public function column_destinations( $item ) {
      return implode( '<br>', $item['destinations'] );
  }

  public function prepare_items() {
      // The url we want to get the mail data for
    if ( ! empty( $_GET['domain'] ) ) {
        $url = $_GET['domain'];
    } else {
        return;
    }

    // Fetch the mail data
    $api_query = '/domain/' . $url . '/mailforwards';
    $fetch_data = Seravo\API::get_site_data($api_query);
    if ( is_wp_error($fetch_data) ) {
      die($fetch_data->get_error_message());
    }
    $data = $fetch_data['forwards'];

      // Define column headers
      $columns = $this->get_columns();
      $hidden = array();
      $sortable = $this->get_sortable_columns();
      $this->_column_headers = array( $columns, $hidden, $sortable );

    /**
     * This checks for sorting input and sorts the data in our array accordingly.
     *
     * In a real-world situation involving a database, you would probably want
     * to handle sorting by passing the 'orderby' and 'order' values directly
     * to a custom query. The returned data will be pre-sorted, and this array
     * sorting technique would be unnecessary.
     */
    function usort_reorder( $a, $b ) {
      // If no sort, default to domain name
      $orderby = ( ! empty($_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'source';
      // If no order, default to asc
      $order = ( ! empty($_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
      // Determine sort order
      $result = strcmp($a[ $orderby ], $b[ $orderby ]);
      // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }
    usort($data, 'usort_reorder');

    $this->items = $data;

  }

}

function list_domains() {
  // Fetch list of domains
  $api_query = '/domains';
  $data = Seravo\API::get_site_data($api_query);
  if ( is_wp_error($data) ) {
    die($data->get_error_message());
  }

  // Parse valid domains
  $valid_data = array();
  foreach ( $data as $row ) {
    if ( $row['management'] == 'Seravo' || $row['management'] == 'Customer' ) {
      array_push($valid_data, $row);
    }
  }

  // Reorder domains
  function domain_reorder( $a, $b ) {
    return strcmp($a['domain'], $b['domain']);
  }
  usort($valid_data, 'domain_reorder');

  // Render the list
  if ( ! empty($valid_data) ) {
    echo '<input type="submit" value="' . __('Fetch Forwards', 'seravo') . '" class="button" style="float: right; margin-left: 15px;">';
    echo '<div style="width: auto; overflow-x: hidden; padding-right: 5px;"><select name="domain" style="width: 100%;">';
    foreach ( $valid_data as $row ) {
      $domain = ! empty( $_GET['domain'] ) ? $_GET['domain'] : '';
      printf( '<option value="%1$s" %2$s>%1$s</option>', $row['domain'], $domain == $row['domain'] ? 'selected' : '' );
    }
    echo '</select></div>';
  } else {
    echo __( 'No valid domains were found!', 'seravo' );
  }
}

/**
 * Display mail forwards table
 */
function display_forwards_table() {
  // Create an instance of mail forwards class
  $forwards_table = new Seravo_Mails_Forward_Table();
  // Fetch, prepare and sort our data...
  $forwards_table->prepare_items();

  $forwards_table->display();
}
