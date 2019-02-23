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
    //     $actions['edit'] = sprintf( $action_request, 'edit', 'Edit');
    }

    // Domains managed by customers themselves can only be added, viewed or deleted
    // $actions['delete'] = sprintf( $action_request, 'delete', 'Delete');
    */

    $page = ! empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : 'domains_page';
    $paged_str = ! empty( $_REQUEST['paged'] ) ? '&paged=' . $_REQUEST['paged'] : '';

    $action_request = '<a href="?page=' . $page . '&domain=' . $item['domain'] . $paged_str . '&action=%s">%s</a>';

    $actions['view'] = sprintf( $action_request, 'view', __( 'View', 'seravo' ) );
    $actions['edit'] = sprintf( $action_request, 'edit', __( 'Edit', 'seravo' ) );

    $primary_str = ! empty( $item['primary'] ) ? ' — ' . __( 'Primary Domain', 'seravo' ) : '';

    switch ( $item['management'] ) {
      case 'Customer':
        // translators:  %1$s is opening tag for a link, %2$s a closing tag.
        $action_row_msg = sprintf( __( 'DNS not managed by Seravo, see %1$smore details%2$s', 'seravo' ),
                                       '<a href="https://help.seravo.com/en/docs/18-can-i-use-my-own-dns" target="_blank">', '</a>' );
        break;
      case null:
        $action_row_msg = __( "Subdomains don't have their own zone", 'seravo' );
        break;
      case 'Seravo':
        $action_row_msg = '';
        break;
      default:
        $action_row_msg = __( "This domain doesn't have a zone", 'seravo' );
    }

    return sprintf( '<strong class="row-title">%1$s<small>%2$s</small></strong> %3$s',
        /*$1%s*/ $item['domain'],
        /*$2%s*/ $primary_str,
        /*$3%s*/ empty( $action_row_msg ) ? $this->row_actions($actions) : '<div class="row-actions" style="color:#8e8d8d"><b>' . $action_row_msg . '</b></div>'
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

  public function column_dns( $item ) {
    $dns = $item['dns'];
    if ( ! empty ( $dns ) ) {
      return implode( '<br>', $dns );
    }
    return '';
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

  public function single_row( $item ) {

    // Print the rows normally
    echo '<tr>';
    $this->single_row_columns( $item );
    echo '</tr>';

    // If there was a DNS table request for the previously printed domain
    if ( ! empty( $_REQUEST['domain']) && $item['domain'] === $_REQUEST['domain'] && ! is_null($this->dns) ) {

      // Add empty row to keep the row color same as the one above
      echo '<tr></tr>';
      echo '<tr><td style="padding-top:0px; padding-bottom:0px;" colspan="' . $this->get_column_count() . '">';

      if ( 'view' === $this->current_action() ) {
        $this->dns->display();
      } else if ( 'edit' === $this->current_action() ) {
        $this->dns->display_edit();
      } elseif ( isset( $_REQUEST['zone-updated'] ) ) {
        if ( $_REQUEST['zone-updated'] === 'true' ) {
          $modifications = isset( $_REQUEST['modifications'] ) ? $_REQUEST['modifications'] : false;
          $error = isset( $_REQUEST['error'] ) ? $_REQUEST['error'] : false;
          $this->dns->display_results( $modifications, $error );
        }
      }

      echo '</td></tr>';

    }
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

</div>
