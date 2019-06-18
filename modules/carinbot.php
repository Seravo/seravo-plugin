<?php

namespace Seravo;

require_once dirname( __FILE__ ) . '/../lib/carinbot-ajax.php';

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('CarinBot') ) {
  class CarinBot {
    
    public static function load() {
      add_action('wp_ajax_seravo_carinbot', 'seravo_ajax_carinbot');
      add_action( 'wp_dashboard_setup', array( __CLASS__, 'seravo_plugin_custom_carinbot') );
    }
    public static function seravo_plugin_custom_carinbot() {
      wp_add_dashboard_widget( 'seravo_plugin_carinbot', 'Carin Bot', array( __CLASS__, 'carinbot_postbox') );
    }
    public static function carinbot_postbox() {
      ?>
      <p><?php _e('Carin Bot is here to help you!', 'seravo'); ?></p>
      <p class="carinbot">
        <button id="carinbot_button" class="button"><?php _e('Chat with Carin Bot', 'seravo'); ?> </button>
        <div id="carinbot_loading"><img class="hidden" src="/wp-admin/images/spinner.gif"></div>
        <pre><div id="carinbot"></div></pre>
      </p>
      <?php
    }
  }
  CarinBot::load();
} 