<?php
/**
 * File for Seravo postbox rendering and common functionality.
 */

namespace Seravo\Postbox;

use \Seravo\Helpers;

if ( ! class_exists('Seravo_Postbox_Factory') ) {

  /**
   * Singleton class responsible for creating Seravo postboxes.
   */
  class Seravo_Postbox_Factory {

    /**
     * Instance of this class.
     * @var \Seravo\Postbox\Seravo_Postbox_Factory|null
     */
    private static $instance;

    /**
     * Store data of added postboxes.
     * @var mixed[][]
     */
    private $postboxes = array();

    /**
     * Enable fast postbox search based on id.
     * @var string[]
     */
    private $registered_postbox_ids = array();

    /**
     * Cache closed postboxes to private variable in order to prevent repeated lookups.
     * @var mixed[]
     */
    private $closed_postboxes = array();

    /**
     * Get singleton instance.
     * @return \Seravo\Seravo_Postbox_Factory|null Instance of the Seravo_Postbox_Factory class
     */
    public static function get_instance() {
      if ( self::$instance === null ) {
        self::$instance = new Seravo_Postbox_Factory();
      }
      return self::$instance;
    }

    /**
     * Class constructor.
     */
    private function __construct() {
      $this->load();
    }

    /**
     * Add a Seravo postbox.
     * @param string       $id            Unique id/slug of the postbox.
     * @param string       $title         Display title of the postbox.
     * @param callable     $callback      A function that outputs the postbox content.
     * @param string       $screen        Admin screen id where the postbox should be displayed in.
     * @param string       $context       Default admin dashboard context where the postbox should be displayed in.
     * @param array[mixed] $callback_args Array of arguments that will get passed to the callback function.
     */
    public function add_postbox( $id, $title, $callback, $screen, $context, $callback_args ) {
      // Index the postboxes base on the page they are registered to, allowing faster filtering based
      // on current screen.
      if ( isset($this->postboxes[ $screen ]) ) {
        if ( isset($this->postboxes[ $screen ][ $context ]) ) {
          // Add postbox only if it does not exist, otherwise throw exception
          if ( in_array($id, $this->registered_postbox_ids, true) ) {
            throw new \Exception('Seravo postbox "' . $id . '" already exists');
          }
        } else {
          $this->postboxes[ $screen ][ $context ] = array();
        }
      } else {
        $this->postboxes[ $screen ] = array();
      }

      $this->postboxes[ $screen ][ $context ][ $id ] = array(
        'title'         => $title,
        'callback'      => $callback,
        'callback_args' => $callback_args,
      );

      $this->registered_postbox_ids[] = $id;
    }

    /**
     * Save info about closed/opened Seravo postboxes to wp user meta.
     */
    public function ajax_save_closed_postboxes() {
      check_ajax_referer('seravo-save-closed-postboxes', 'seravo_closed_postboxes_nonce');

      $closed = isset($_POST['closed']) ? explode(',', $_POST['closed']) : array();
      $closed = array_filter($closed);
      $page = isset($_POST['page']) ? sanitize_key($_POST['page']) : '';

      $user_id = get_current_user_id();
      if ( $user_id !== 0 ) {
        update_user_option($user_id, 'seravo-closed-postboxes_' . $page, $closed, true);
      }
      wp_die();
    }

    /**
     * Save the Seravo postbox order into wp user meta.
     */
    public function ajax_save_postbox_order() {
      check_ajax_referer('seravo-save-postbox-order', 'seravo_save_postbox_order_nonce');

      $order = isset($_POST['order']) ? array_filter($_POST['order']) : false;
      $page = isset($_POST['page']) ? sanitize_key($_POST['page']) : '';

      $user_id = get_current_user_id();

      if ( $user_id !== 0 && $order && $page ) {
        update_user_option($user_id, 'seravo-postbox-order_' . $page, $order, true);
      }
      wp_die();
    }

    /**
     * Enqueue necessary scripts and styles for Seravo postbox functionality.
     */
    public function enqueue_postboxes_scripts() {
      if ( ! empty($this->postboxes) ) {
        // seravo-postbox.js
        wp_enqueue_script('seravo_postbox', SERAVO_PLUGIN_URL . 'js/postbox/seravo-postbox.js', array( 'jquery', 'jquery-ui-sortable' ), Helpers::seravo_plugin_version());
        $postbox_l10n = array(
          'postBoxEmptyString' => __('Drag boxes here', 'seravo'),
        );
        wp_localize_script('seravo_postbox', 'seravoPostboxl10n', $postbox_l10n);

        // seravo-ajax.js
        wp_enqueue_script('seravo_ajax', SERAVO_PLUGIN_URL . 'js/postbox/seravo-ajax.js', array( 'jquery', 'jquery-ui-sortable' ), Helpers::seravo_plugin_version());
        $ajax_l10n = array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'server_invalid_response' => __('Error: Something unexpected happened! Server responded with invalid data.', 'seravo'),
          'server_timeout' => __("Error: Request timeout! Server didn't respond in time.", 'seravo'),
          'server_error' => __("Error: Oups, this wasn't supposed to happen! Please see the php-error.log.", 'seravo'),
        );
        wp_localize_script('seravo_ajax', 'seravo_ajax_l10n', $ajax_l10n);

        // seravo-postbox.css
        wp_enqueue_style('seravo_postbox', SERAVO_PLUGIN_URL . 'style/seravo-postbox.css', array(), Helpers::seravo_plugin_version());

        // common.css
        wp_enqueue_style('seravo_common', SERAVO_PLUGIN_URL . 'style/common.css', array(), Helpers::seravo_plugin_version());
      }
    }

    /**
     * Load the Seravo custom postbox functionality.
     */
    private function load() {
      if ( is_admin() ) {
        // Scripts and styles
        add_action(
          'admin_enqueue_scripts',
          function () {
            return $this->enqueue_postboxes_scripts();
          }
        );

        // AJAX endpoints for saving postbox order and closed/opened state
        add_action(
          'wp_ajax_seravo-postbox-order',
          function () {
            return $this->ajax_save_postbox_order();
          }
        );
        add_action(
          'wp_ajax_seravo-closed-postboxes',
          function () {
            return $this->ajax_save_closed_postboxes();
          }
        );
      }
    }


    /**
     * Filter the postboxes in such a way that they are in the user specified order.
     */
    private function apply_user_postbox_settings( $column_count = 'four_column' ) {
      $screen = get_current_screen()->id;

      // Preload closed postboxes. If the setting is not set, WP returns an empty string, so don't
      // do anything in that case. If only a single postbox is closed, WP returns a single string so
      // it should be converted into an array.
      $closed_postboxes = get_user_meta(get_current_user_id(), 'seravo-closed-postboxes_' . $screen, true);
      if ( ! empty($closed_postboxes) ) {
        if ( ! is_array($closed_postboxes) ) {
          $closed_postboxes = array( $closed_postboxes );
        }
        $this->closed_postboxes = $closed_postboxes;
      }

      // Use user-specified postbox order if set
      $custom_postbox_order = get_user_meta(get_current_user_id(), 'seravo-postbox-order_' . $screen, true);
      if ( $custom_postbox_order ) {
        foreach ( $custom_postbox_order as $custom_context => $postbox_order_str ) {

          $postbox_order = explode(',', $postbox_order_str);
          // Search for the data for that postbox ID
          foreach ( $postbox_order as $postbox_id ) {
            foreach ( $this->postboxes[ $screen ] as &$postboxes_array ) {

              // Move the postbox data to the new context
              if ( array_key_exists($postbox_id, $postboxes_array) ) {

                if ( $column_count === 'two_column' ) {
                  if ( $custom_context === 'column3' ) {
                    $custom_context = 'normal';
                  } elseif ( $custom_context === 'column4' ) {
                    $custom_context = 'side';
                  }
                } elseif ( $column_count === 'one_column' ) {
                  if ( $custom_context !== 'normal' ) {
                    $custom_context = 'normal';
                  }
                }

                $postbox_data = $postboxes_array[$postbox_id];
                unset($postboxes_array[$postbox_id]);
                $this->postboxes[$screen][$custom_context][$postbox_id] = $postbox_data;
                break;
              }
            }
          }
        }
      }
    }

    /**
     * Display Seravo postboxes that are registered to a certain screen and context.
     */
    private function do_postboxes( $screen, $context ) {
      // Loop through the postboxes for this context
      if ( isset($this->postboxes[ $screen ][ $context ]) && ! empty($this->postboxes[ $screen ][ $context ]) ) {
        foreach ( $this->postboxes[ $screen ][ $context ] as $postbox_id => &$postbox_content ) {
          $this->display_single_postbox($postbox_id, $postbox_content);
        }
      }
    }

    /**
     * Display a page with all currently registered postboxes.
     */
    public function display_postboxes_page( $column_count = 'four_column' ) {
      // These are the same postbox contexts that are used in WP core.
      $container_contexts = array( 'normal', 'side', 'column3', 'column4' );

      if ( $column_count === 'two_column' ) {
        $container_contexts = array( 'normal', 'side' );
      } elseif ( $column_count === 'one_column' ) {
        $container_contexts = array( 'normal' );
      }

      $context_index = 1;
      $current_screen = get_current_screen()->id;
      $this->apply_user_postbox_settings($column_count);

      if ( $column_count === 'two_column' ) {
        $container_class = 'two-column-layout';
      } elseif ( $column_count === 'one_column' ) {
        $container_class = 'one-column-layout';
      } else {
        // Prevent line 256 from emitting Notices on unset variable
        $container_class = '';
      }

      // Fire pre-postbox action
      do_action('before_seravo_postboxes_' . $current_screen);
      ?>

      <!-- Postbox wrapper -->
      <div class="dashboard-widgets-wrap">
        <div id="dashboard-widgets" class="metabox-holder seravo-postbox-holder">
          <?php foreach ( $container_contexts as $container_context ) : ?>
            <div id="postbox-container-<?php echo $context_index; ?>" class="postbox-container <?php echo $container_class; ?>">
              <div id="<?php echo $container_context; ?>-sortables" class="meta-box-sortables ui-sortable">
                <?php $this->do_postboxes($current_screen, $container_context); ?>
              </div>
            </div>
            <?php ++$context_index; ?>
          <?php endforeach; ?>

        <?php
        // AJAX nonces for saving order and open/closed status of Seravo postboxes
        wp_nonce_field('seravo-save-postbox-order', 'seravo-postbox-order-nonce');
        wp_nonce_field('seravo-save-closed-postboxes', 'seravo-closed-postboxes-nonce');
        ?>
        </div>
      </div>
      <?php

      // Fire after postbox action
      do_action('after_seravo_postboxes_' . $current_screen);
    }

    private function display_single_postbox( $postbox_id, $postbox_content ) {
      $closed = in_array($postbox_id, $this->closed_postboxes);
      ?>

      <div id="seravo-postbox-<?php echo $postbox_id; ?>" data-postbox-id="<?php echo $postbox_id; ?>"
        class="postbox seravo-postbox <?php echo $closed ? 'closed' : ''; ?>">

        <!-- Handle for toggling postbox panel -->
        <button type="button" class="handlediv button-link" aria-expanded="<?php echo $closed ? 'false' : 'true'; ?> ">
          <span class="screen-reader-text">
            <?php
              /* translators: %s: Togglable postbox title */
              printf(__('Toggle panel: %s', 'seravo'), $postbox_content['title']);
            ?>
          </span>
          <span class="toggle-indicator" aria-hidden="true"></span>
        </button>

        <!-- Postbox title -->
        <h2 class="hndle ui-sortable-handle">
          <span><?php echo $postbox_content['title']; ?></span>
        </h2>

        <!-- Postbox content -->
        <div class="inside">
          <div class="seravo-section">
            <?php \call_user_func_array($postbox_content['callback'], $postbox_content['callback_args']); ?>
          </div>
        </div>
      </div>
      <?php
    }
  }
}
