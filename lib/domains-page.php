<?php

if ( ! current_user_can( 'level_10' ) ) {
  wp_die(
      '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
      '<p>' . __( 'Sorry, you are not allowed to access domains.' ) . '</p>',
      403
  );
}

if ( ! class_exists('WP_List_Table') ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Seravo_Domains_List_Table extends WP_List_Table {

  function __construct() {
    global $status, $page;

    // Set parent defaults
    parent::__construct(
        array(
        'singular'  => 'domain',
        'plural'    => 'domains',
        'ajax'      => false,
        )
    );
  }

  function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'domain':
      case 'expires':
      case 'dns':
      case 'management':
        return $item[$column_name];
      default:
        return print_r($item, true); // Show the whole array for troubleshooting purposes
    }
  }

  function column_domain( $item ) {

    $actions = array();

    // Domains managed by Seravo can be added, edited or deleted
    if ( $item['management'] == 'Seravo' ) {
      $actions['edit'] = sprintf('<a href="?page=%s&action=%s&domain=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['domain']);
    }

    // Domains managed by customers themselves can only be added or deleted
    $actions['delete'] = sprintf('<a href="?page=%s&action=%s&domain=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['domain']);

    return sprintf('%1$s %2$s',
        /*$1%s*/ $item['domain'],
        /*$2%s*/ $this->row_actions($actions)
    );
  }

  function column_cb( $item ) {
    return sprintf(
        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
        /*$1%s*/ $this->_args['singular'],  // Let's simply repurpose the table's singular label ("domain")
        /*$2%s*/ $item['domain']            // The value of the checkbox should be the domain name
    );
  }

  function get_columns() {
    $columns = array(
      'cb'         => '<input type="checkbox">', // Render a checkbox instead of text
      'domain'     => 'Domain',
      'expires'    => 'Expires',
      'dns'        => 'DNS',
      'management' => 'Managed by',
    );
    return $columns;
  }

  function get_sortable_columns() {
    $sortable_columns = array(
      'domain'     => array( 'domain', false ),     // true means it's already sorted
      'expires'    => array( 'expires', false ),
      'dns'        => array( 'dns', false ),
      'management' => array( 'management', false ),
    );
    return $sortable_columns;
  }

  function get_bulk_actions() {
    $actions = array(
      'delete' => 'Delete',
    );
    return $actions;
  }

  function process_bulk_action() {
    if ( 'delete' === $this->current_action() ) {
      wp_die('Items deleted (or they would be if we had items to delete)!');
    }
  }

  function prepare_items() {
    global $wpdb; // This is used only if making any database queries

    // Domains per page
    $per_page = 5;

    // Define column headers
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array( $columns, $hidden, $sortable );

    // Define custom bulk actions
    $this->process_bulk_action();

    // Fetch list of domains

    $site = getenv('USER');

    $ch = curl_init('http://localhost:8888/v1/site/' . $site . '/domains');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-Api-Key: ' . getenv('SERAVO_API_KEY') ));
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ( curl_error($ch) || $httpcode != 200 ) {
      error_log('SWD API error ' . $httpcode . ': ' . curl_error($ch));
      die('API call failed. Aborting. The error has been logged.');
    }

    curl_close($ch);

    $data = json_decode($response, true);

    /**
     * This checks for sorting input and sorts the data in our array accordingly.
     *
     * In a real-world situation involving a database, you would probably want
     * to handle sorting by passing the 'orderby' and 'order' values directly
     * to a custom query. The returned data will be pre-sorted, and this array
     * sorting technique would be unnecessary.
     */
    function usort_reorder( $a, $b ) {
        $orderby = ( ! empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'domain'; // If no sort, default to domain name
        $order = ( ! empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; // If no order, default to asc
        $result = strcmp($a[$orderby], $b[$orderby]); // Determine sort order
        return ($order === 'asc') ? $result : -$result; // Send final sort direction to usort
    }
    usort($data, 'usort_reorder');

    // Required for pagnation
    $current_page = $this->get_pagenum();
    $total_items = count($data);

    /**
     * The WP_List_Table class does not handle pagination for us, so we need
     * to ensure that the data is trimmed to only the current page. We can use
     * array_slice() to
     */
    $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

    /**
     * REQUIRED. Now we can add our *sorted* data to the items property, where
     * it can be used by the rest of the class.
     */
    $this->items = $data;

    /**
     * REQUIRED. We also have to register our pagination options & calculations.
     */
    $this->set_pagination_args(
        array(
        'total_items' => $total_items,                  // WE have to calculate the total number of items
        'per_page'    => $per_page,                     // WE have to determine how many items to show on a page
        'total_pages' => ceil($total_items / $per_page),// WE have to calculate the total number of pages
        )
    );
  }

}


// Create an instance of our package class
$domainsTable = new Seravo_Domains_List_Table();
// Fetch, prepare, sort, and filter our data...
$domainsTable->prepare_items();

?>
<div class="wrap">

  <h1>
    Domains
    <a href="tools.php?page=add_domains_page" class="page-title-action"><?php echo esc_html_x('Add New', 'post'); ?></a>
  </h1>

  <p>Listing all domains routed to this WordPress site. The Add and Delete buttons are work-in-progress and not functional quite yet.</p>

  <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
  <form id="domains-filter" method="get">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
    <!-- Now we can render the completed list table -->
    <?php $domainsTable->display() ?>
  </form>

</div>
