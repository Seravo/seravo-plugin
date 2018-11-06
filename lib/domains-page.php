<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! current_user_can( 'level_10' ) ) {
  wp_die(
    '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>
    <p>' . __( 'Sorry, you are not allowed to access domains.', 'seravo' ) . '</p>',
    403
  );
}

if ( ! class_exists( 'Seravo_Domains_DNS_Table' ) ) {
  require_once dirname( __FILE__ ) . '/domains-dns.php';
}

if ( ! class_exists('WP_List_Table') ) {
  require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Seravo_Domains_List_Table extends WP_List_Table {
  public $dns;

  public function __construct() {
    global $status, $page;
    $this->dns = new Seravo_Domains_DNS_Table();

    // Set parent defaults
    parent::__construct(
      array(
        'singular' => 'domain',
        'plural'   => 'domains',
        'ajax'     => false,
      )
    );
  }

  public function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'domain':
      case 'expires':
      case 'dns':
      case 'management':
        return $item[ $column_name ];
      default:
        return print_r($item, true); // Show the whole array for troubleshooting purposes
    }
  }

  public function column_domain( $item ) {

    $actions = array();

    /*
    // Domains managed by Seravo can be added, edited or deleted
    // if ( $item['management'] === 'Seravo' ) {
    //     $actions['edit'] = sprintf('<a href="?page=%s&action=%s&domain=%s">Edit</a>',
    //                                  $_REQUEST['page'], 'edit', $item['domain']);
    }

    // Domains managed by customers themselves can only be added, viewed or deleted
    // $actions['delete'] = sprintf('<a href="?page=%s&action=%s&domain=%s">Delete</a>',
    //                                $_REQUEST['page'], 'delete', $item['domain']);
    */
    $actions['view'] = sprintf( '<a href="?page=%s&action=%s&domain=%s">View</a>',
                                  $_REQUEST['page'], 'view', $item['domain']);
    $actions['edit'] = sprintf( '<a href="?page=%s&action=%s&domain=%s">Edit</a>',
                                  $_REQUEST['page'], 'edit', $item['domain']);

    return sprintf('%1$s %2$s',
        /*$1%s*/ $item['domain'],
        /*$2%s*/ $this->row_actions($actions)
    );
  }

  public function column_cb( $item ) {
    return sprintf(
        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
          // Let's simply repurpose the table's singular label ("domain")
        /*$1%s*/ $this->_args['singular'],
          // The value of the checkbox should be the domain name
        /*$2%s*/ $item['domain']
    );
  }

  public function get_columns() {
    $columns = array(
      'cb'         => '<input type="checkbox">', // Render a checkbox instead of text
      'domain'     => __('Domain', 'seravo'),
      'expires'    => __('Expires', 'seravo'),
      'dns'        => __('DNS', 'seravo'),
      'management' => __('Managed by', 'seravo'),
    );
    return $columns;
  }

  public function get_sortable_columns() {
    $sortable_columns = array(
      'domain'     => array( 'domain', false ),     // true means it's already sorted
      'expires'    => array( 'expires', false ),
      'dns'        => array( 'dns', false ),
      'management' => array( 'management', false ),
    );
    return $sortable_columns;
  }

  public function get_bulk_actions() {
    $actions = array(
    //  'delete' => 'Delete',
    );
    return $actions;
  }

  public function process_bulk_action() {
    if ( 'delete' === $this->current_action() ) {
      wp_die('Items deleted (or they would be if we had items to delete)!');
    } elseif ( 'view' === $this->current_action() ) {
      $this->dns->fetch_dns_records($_REQUEST['domain']);
    } elseif ( 'edit' === $this->current_action() ) {
      // Fetch something to edit
      $this->dns->fetch_dns_records($_REQUEST['domain']);
    }
  }

  public function prepare_items() {
    global $wpdb; // This is used only if making any database queries

    // Domains per page
    $per_page = 500;

    // Define column headers
    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array( $columns, $hidden, $sortable );

    // Define custom bulk actions
    $this->process_bulk_action();

    // Fetch list of domains
    $api_query = '/domains';
    $data = Seravo\API::get_site_data($api_query);
    if ( is_wp_error($data) ) {
      die($data->get_error_message());
    }

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
      $orderby = ( ! empty($_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'domain';
        // If no order, default to asc
      $order = ( ! empty($_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
        // Determine sort order
      $result = strcmp($a[ $orderby ], $b[ $orderby ]);
        // Send final sort direction to usort
      return ( $order === 'asc' ) ? $result : -$result;
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
    $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

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
        // WE have to calculate the total number of items
        'total_items' => $total_items,
        // WE have to determine how many items to show on a page
        'per_page'    => $per_page,
        // WE have to calculate the total number of pages
        'total_pages' => ceil($total_items / $per_page),
      )
    );
  }

}


// Create an instance of our package class
$domains_table = new Seravo_Domains_List_Table();
// Fetch, prepare, sort, and filter our data...
$domains_table->prepare_items();

?>
<div class="wrap">

  <h1><?php _e('Domains', 'seravo'); ?> (beta)</h1>

  <p><?php _e('Domains routed to this WordPress site are listed below.', 'seravo'); ?></p>

  <!-- Forms are NOT created automatically, so you need to wrap
    the table in one to use features like bulk actions -->
  <form id="domains-filter" method="get">
    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
    <!-- Now we can render the completed list table -->
    <?php $domains_table->display(); ?>
  </form>
  <?php
  // Create the dns-table section if it's available
  if ( ! is_null($domains_table->dns) ) {
    if ( 'view' === $domains_table->current_action() ) {
      $domains_table->dns->display();
    } elseif ( 'edit' === $domains_table->current_action() ) {
      $domains_table->dns->display_edit();
    } elseif ( $_REQUEST['zone-updated'] ) {
      $domains_table->dns->display_results( $_REQUEST['modifications'], $_REQUEST['error'] );
    }
  }
  ?>

</div>
