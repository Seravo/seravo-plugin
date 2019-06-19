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
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_shadows_scripts' ));
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

    public static function register_shadows_scripts() {
      wp_register_script( 'seravo_carinbot', plugin_dir_url( __DIR__ ) . '/js/carinbot.js', '', Helpers::seravo_plugin_version());
      wp_enqueue_script( 'seravo_carinbot' );

      $loc_translation = array(
        'ajaxurl'    => admin_url('admin-ajax.php'),
        'ajax_nonce' => wp_create_nonce('seravo_carinbot'),
      );

      wp_localize_script( 'seravo_carinbot', 'seravo_carinbot_loc', $loc_translation );
    }
  }
  CarinBot::load();
}

/*
<script src='//helpy.io/js/helpybot.js'></script>
<script>
var Helpy = Helpy || {};
Helpy.domain = '//help.seravo.com';

Helpy.botIcon = '';
Helpy.botBackground = '#f0b40e';

// Use the following attributes to identify the user in your app/store
// Unidentified users will appear as anonymous users
Helpy.email_address = '';
Helpy.customer_name = '';

$script(['//helpy.io/js/bot.v5.js'], function() {
 Helpy.initBot('d02VpXBADWPR31G8kQMjK8waxEvO4r');
});</script>
*/