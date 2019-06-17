<?php
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
if ( ! class_exists('CarinBot_Widget') ) {
  class CarinBot_Widget {
    
    public static function load() {
      add_action( 'wp_dashboard_setup', array( __CLASS__, 'seravo_plugin_custom_carinbot_widget') );
    }
    public static function seravo_plugin_custom_carinbot_widget() {
      wp_add_dashboard_widget( 'seravo_plugin_carinbot_widget', 'Carin Bot', array( __CLASS__, 'seravo_plugin_carinbot_widget') );
    }
    public static function seravo_plugin_carinbot_widget() {
      ?>
      <p><?php _e('Carin Bot is here to help you!', 'seravo'); ?></p>
      <p class="create_backup">
        <button id="create_backup_button" class="button"><?php _e('Chat with Carin Bot', 'seravo'); ?> </button>
        <div id="create_backup_loading"><img class="hidden" src="/wp-admin/images/spinner.gif"></div>
        <pre><div id="create_backup"></div></pre>
      </p>
      <?php
    }
  }
  CarinBot_Widget::load();
} 