<?php

if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Dashboard_Widget') ) {
  class Dashboard_Widget {
    
    function load() {
      add_action( 'wp_dashboard_setup', array( __CLASS__, 'seravo_plugin_custom_dashboard_widget') );
    }

    function seravo_plugin_custom_dashboard_widget() {
      wp_add_dashboard_widget( 'seravo_plugin_welcome_widget', 'Seravo Plugin', array( __CLASS__, 'seravo_plugin_dashboard_widget') );
    }

    function seravo_plugin_dashboard_widget() {
      ?>
      <p>Dashboard widget</p>
      <?php
    }
  }

  Dashboard_Widget::load();
}