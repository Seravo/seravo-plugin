<?php

namespace Seravo;

if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Welcome_Panel') ) {
  class Welcome_Panel {
    public static function load() {
      remove_action('welcome_panel', 'wp_welcome_panel');
      add_action('welcome_panel', array( __CLASS__, 'seravo_welcome_panel' ));
      add_action('admin_enqueue_scripts', array( __CLASS__, 'seravo_welcome_panel_scripts' ));
    }

    public static function seravo_welcome_panel() {
      ?>
      <div class="seravo-welcome-panel-welcome-text">
        <h1>
          <?php
            _e('Welcome!', 'seravo');
          ?>
        </h1>
        <p>
          <?php
            _e('Welcome to your brand new Seravo WordPress installation, we are excited to have you here!', 'seravo');
          ?>
        </p>
      </div>
      <div class="welcome-panel-container">
        <div class="welcome-panel-column">
          <div class="seravo-text-center">
            <h3>
              <?php
              _e('Get Started', 'seravo');
              ?>
            </h3>
            <p>
              <?php
              _e('Seravo Toolbox is a handy way to manage WordPress sites. Take a look at some of these features to get to know it better:', 'seravo');
              ?>
            </p>
            <ul class="seravo-welcome-panel-links">
              <li><a href="">Seravo Toolbox</a></li>
              <li><a href="">Site Status</a></li>
              <li><a href="">Upkeep</a></li>
              <li><a href="">Development</a></li>
            </ul>
          </div>
        </div>
        <div class="welcome-panel-column">
         <div class="seravo-text-center">
          <h3>
            <?php
            _e('Learn More', 'seravo');
            ?>
          </h3>
          <p>
            <?php
            _e('Learn from our resident WordPress experts in our blog. If you want to be the first to hear about our new blog posts, sign up to our newsletter:', 'seravo');
            ?>
          </p>
           <br>
          <a id="seravo-welcome-panel-subscribe-button" href="" target="_blank"><?php _e('Subscribe!', 'seravo'); ?></a>
           <br>
           <br>
          <p>
            <?php
            printf(
              // translators: Link to Seravo developer newsletter
              __('Looking for the latest and greatest in WordPress development? Check out <a href="%s" target="_blank"> our developer newsletter!</a>', 'seravo'),
              'https://seravo.com/newsletter-for-wordpress-developers/'
            );
            ?>
          </p>
         </div>
        </div>
        <div class="welcome-panel-column">
          <div class="seravo-text-center">
            <h3>
              <?php
              _e('Need help?', 'seravo');
              ?>
            </h3>
            <p>
              <?php
              _e('Sometimes we all need a helping hands. If you feel stuck or don\'t know what to do next, fear not, help is just a few clicks away:', 'seravo');
              ?>
            </p>
            <br>
            <a id="seravo-welcome-panel-contact-support-button" href="https://help.seravo.com" target="_blank"><?php _e('Contact Support', 'seravo'); ?></a>
            <br>
            <br>
            <p>
              <?php
              printf(
                // translators: Link to developer docs
                __('Looking for the documentation instead? Check out <a href="%s" target="_blank">our developer Docs.</a>', 'seravo'),
                'https://seravo.com/docs/'
              );
              ?>
            </p>
          </div>
        </div>
      </div>
      <?php
    }

    public static function seravo_welcome_panel_scripts( $page ) {
      wp_register_style('seravo_welcome_panel', plugin_dir_url(__DIR__) . '/style/welcome-panel.css', '', Helpers::seravo_plugin_version());

      if ( $page === 'index.php' ) {
        wp_enqueue_style('seravo_welcome_panel');
      }
    }
  }

  Welcome_Panel::load();
}
