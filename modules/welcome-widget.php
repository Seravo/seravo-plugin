<?php
/**
 * Plugin name: Seravo Welcome Widget
 * Description: Replace default welcome widget
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Seravo_Welcome_Widget') ) {
  class Seravo_Welcome_Widget {
    public static function load() {
      remove_action('welcome_panel', 'wp_welcome_panel');
      add_action('welcome_panel', array( __CLASS__, 'seravo_custom_welcome_panel' ));
    }

    public static function seravo_custom_welcome_panel() {
      ?>
      <div class="custom-welcome-panel-content">
        <h2>Custom welcome panel</h2>
        <p>Welcome to Seravo's WordPress environment</p>
        <div class="welcome-panel-column-container">
          <div class='welcome-panel-column'>
            <p>Links</p>
            <ul>
              <li><a href="https://seravo.com/docs/" target="_blank">Developer Documentation</a></li>
              <li><a href="https://help.seravo.com/fi" target="_blank">Help Center</a></li>
              <li><a href="https://wp-palvelu.fi/blogi/" target="_blank">Blogs</a></li>
            </ul>
          </div>
          <div class='welcome-panel-column'>
            <p>FAQ</p>
            <ul>
              <li><a href="https://help.seravo.com/en/docs/19-is-my-site-public-from-the-get-go" target="_blank">Is My Site Public from the Get-go?</a></li>
              <li><a href="https://help.seravo.com/en/docs/101-does-seravo-have-a-cpanel" target="_blank">Does Seravo have a cPanel?</a></li>
              <li><a href="https://help.seravo.com/en/docs/92-how-does-the-caching-work" target="_blank">How does the Caching work?</a></li>
              <li><a href="https://help.seravo.com/en/docs/51-which-wordpress-plugins-are-recommended-with-seravo-com" target="_blank">Which WordPress Plugins are Recommended with Seravo.com?</a></li>
            </ul>
          </div>
          <div class='welcome-panel-column'>
            <p>Social media & Support</p>
            <ul>
              <li><a href="https://twitter.com/SeravoStatus" target="_blank">Site status (Twitter)</a></li>
            </ul>
          </div>
        </div>
        <div class="welcome-panel-column-container">
          <h2>Custom welcome panel</h2>
          <p>This is dev version</p>
        </div>
      </div>
      <?php
    }
  }

  Seravo_Welcome_Widget::load();
}
