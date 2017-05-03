<?php
/*
 * Plugin name: InstanceSwitcher
 * Description: Enable users to manage their Seravo WordPress updates
 * Version: 1.0
 */

namespace Seravo;

if (!class_exists('InstanceSwitcher')) {
  class InstanceSwitcher {

    public static function load() {
      // only run the instance switcher when in a container environment
      if( ! getenv('CONTAINER') ) {
        return;
      }

      // admin ajax action
      add_action( 'wp_ajax_wpis_change_container', array( 'Seravo\InstanceSwitcher', 'change_wp_container' ) );
      add_action( 'wp_ajax_nopriv_wpis_change_container', array( 'Seravo\InstanceSwitcher', 'change_wp_container' ) );
      
      // styles and scripts for the switcher
      add_action( 'admin_enqueue_scripts', array( 'Seravo\InstanceSwitcher', 'assets' ), 999);
      add_action( 'wp_enqueue_scripts', array( 'Seravo\InstanceSwitcher', 'assets' ), 999);
      
      // add the instance switcher menu
      add_action( 'admin_bar_menu', array( 'Seravo\InstanceSwitcher', 'add_switcher' ), 999 );

      // display a notice at the bottom of the window when in a shadow
      if ( getenv('WP_ENV') && getenv('WP_ENV') != 'production' ) {
        add_action('admin_footer', array( 'Seravo\InstanceSwitcher', 'render_shadow_indicator' ) );
        add_action('wp_footer', array( 'Seravo\InstanceSwitcher', 'render_shadow_indicator' ) );
        add_action('login_footer', array( 'Seravo\InstanceSwitcher', 'render_shadow_indicator' ) );
        add_action('admin_notices', array( 'Seravo\InstanceSwitcher', 'render_shadow_admin_notice' ) );
      }
    }

    public static function register_updates_page() {
      if ( getenv('SERAVO_API_KEY') != '' ) {
        add_submenu_page( 'tools.php', 'Instance Switcher', 'Instance Switcher', 'manage_options', 'updates_page', array(__CLASS__, 'load_updates_page') );
      }
    }

    public static function load_updates_page() {
      require_once(dirname( __FILE__ ) . '/../lib/updates-page.php');
    }

    /**
    * Load javascript and stylesheets for the switcher
    */
    public static function assets(){
      if ( !function_exists( 'is_admin_bar_showing' ) ) {
        return;
      }

      // use this within the admin bar
      if ( !is_admin_bar_showing() ) {
        return;
      }
      
      wp_enqueue_script( 'wpisjs', plugins_url( '../js/instance_switcher.js' , __FILE__), null, null, true );
      wp_enqueue_style( 'wpisjs', plugins_url( '../style/instance_switcher.css' , __FILE__), null, null, 'all' );
    }

    /**
    * Create the menu itself
    */
    public static function add_switcher(  $wp_admin_bar ){
      
      if ( ! function_exists( 'is_admin_bar_showing' ) ) {
        return;
      }

      // use this within the admin bar
      if ( ! is_admin_bar_showing() ) {
        return;
      }

      // check permissions
      if( ! current_user_can( 'activate_plugins' )){
        return;
      }
      
      
      $instances = get_defined_constants();

      // get the wpis specific constants
      foreach( $instances as $key => $constant ) {
        if( ! preg_match( '#WPIS-#', $key ) ) {
          unset( $instances[$key] );
        }
      }
      

      if( empty( $instances ) ) {
        return;
      }

      $id = 'wpis';
      $current_instance = getenv('CONTAINER');

      $instance_index = strpos($current_instance,'_') + 1;
      for( $x = 0 ; $x < $instance_index; ++$x ) {
        $current_instance = substr($current_instance, 1);
      }

      // define the name of the current instance to be shown in the bar
      foreach( $instances as $key => $instance ) {
        if($current_instance == $instance){
          $current_instance = substr($key, 5);
        }
      }

      $domain = ""; //$this->get_domain( $_SERVER['HTTP_HOST'] );

      if ( getenv('WP_ENV') && getenv('WP_ENV') != 'production' ) {
        $menuclass = 'wpis-warning';
      }

      // create the parent menu here
      $wp_admin_bar->add_menu([
        'id' => $id,
        'title' => $current_instance,
        'href' => '#',
        'meta' => [
          'class' => $menuclass,
        ],
      ]);

      // add menu entries for each shadow
      foreach($instances as $key => $instance) {
        $wp_admin_bar->add_menu([
          'parent' => $id,
          'title' => substr($key, 5),
          'id' => $instance,
          'href' => "#$instance",
        ]);
      }

      // Last item is always to exit shadow
      $wp_admin_bar->add_menu(array(
        'parent' => $id,
        'title' => __('Exit Shadow', 'seravo-plugin'),
        'id' => 'exit-shadow',
        'href' => "#exit",
      ));
    }
    
    public static function render_shadow_indicator() {
?>
      <style>#shadow-indicator { font-family: Arial, sans-serif; position: fixed; bottom: 0; left: 0; right: 0; width: 100%; color: #fff; background: #cc0000; z-index: 3000; font-size:16px; line-height: 1; text-align: center; padding: 5px } #shadow-indicator a.clearlink { text-decoration: underline; color: #fff; }</style>
      <div id="shadow-indicator">
      <?php echo wp_sprintf( __('You are currently in %s.', 'seravo-plugin'), getenv( 'WP_ENV' ) ); ?> <a class="clearlink" href="/?wpp_shadow=clear"><?php _e('Exit', 'seravo-plugin'); ?></a>
      </div>
<?php
    }
    
    /**
    * Let plugins or themes display admin notice when inside a shadow
    */
    public static function render_shadow_admin_notice( $current_screen ) {
      $current_screen = get_current_screen();
      $admin_notice_content = apply_filters( 'seravo_instance_switcher_admin_notice', '', $current_screen );
      if(!empty($admin_notice_content)) {
        echo $admin_notice_content;
      }
    }
  }

  InstanceSwitcher::load();
}
