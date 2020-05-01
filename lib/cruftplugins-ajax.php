<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}
// [plugin, reason, size]
function seravo_ajax_list_cruft_plugins() {
  check_ajax_referer('seravo_cruftplugins', 'nonce');
  exec('wp plugin list --fields=name,title,status --format=json --skip-plugins --skip-themes', $output);
  //https://help.seravo.com/en/knowledgebase/19-themes-and-plugins/docs/51-wordpress-plugins-in-seravo-com
  $plugins_list = array(
    'cache_plugins'    => array(                               //Unneeded cache plugins
      'w3-total-cache',
      'wp-super-cache',
      'wp-file-cache',
      'wp-fastest-cache',
      'litespeed-cache',
      'comet-cache',
    ),
    'security_plugins' => array(                            //False sense of security
      'better-wp-security',                                 //iThemes Security aka Better WP Security
      'wordfence',
      'limit-login-attempts-reloaded',
      'wp-limit-login-attempts',
      'wordfence-assistant',
    ),
    'db_plugins'       => array(                                  //Known to mess up your DB
      'broken-link-checker',                               //Broken Link Checker
      'tweet-blender',                                     //Tweet Blender
    ),
    'backup_plugins'   => array(                             //A list of most used backup-plugins
      'updraftplus',
      'backwpup',
      'jetpack',
      'duplicator',
      'backup',
      'all-in-one-wp-migration',
      'dropbox-backup',
      'wp-db-backup',
      'really-simple-ssl',
      'xcloner-backup-and-restore',
    ),
    'poor_security'    => array(                             //Known for poor security
      'wp-phpmyadmin-extension',                          //phpMyAdmin
      'ari-adminer',                                      //Adminer
      'sweetcaptcha-revolutionary-free-captcha-service',  //Sweet Captcha
      'wp-cerber',
      'sucuri-scanner',
      'wp-simple-firewall',
    ),
    'bad_code'         => array(                                  //Hard to differentiate from actual malicious
      'wp-client',
      'wp-filebase-pro',
      'miniorange-oauth-client-premium',
    ),
    'foolish_plugins'  => array(                            //Not malicious but do unwanted things
      'all-in-one-wp-migration',
      'video-capture',
      'simple-subscribe',
    ),
  );
  $remove_from_list = array();
  $output = json_decode($output[0]);
  foreach ( $output  as $plugin ) {
    //as default, we want to keep plugins - ie, remove them from the suggestions
    $rm_tag = true;
    foreach ( $plugins_list as $plugin_set_title => $plugins_set ) {
      if ( in_array($plugin->name, $plugins_set) ) {
        $plugin->status = $plugin_set_title;
        $rm_tag = false;
      }
    }
    if ( $plugin->status != 'inactive' && $rm_tag ) {
      $remove_from_list[] = $plugin;
    }
  }
  $output = array_udiff(
    $output,
    $remove_from_list,
    function ( $obj_a, $obj_b ) {
      return strcmp($obj_a->name, $obj_b->name);
    }
  );
  //to check if the system has these
  set_transient('cruft_plugins_found', $output, 600);
  echo json_encode($output);
  wp_die();
}

function seravo_ajax_remove_plugins() {
  check_ajax_referer('seravo_cruftplugins', 'nonce');
  if ( isset($_POST['removeplugin']) && ! empty($_POST['removeplugin']) ) {
    $plugins = $_POST['removeplugin'];
    if ( is_string($plugins) ) {
      $plugins = array( $plugins );
    }
    if ( ! empty($plugins) ) {
      $result = array();
      foreach ( $plugins as $plugin ) {
        $legit_removeable_plugins = get_transient('cruft_plugins_found');
        foreach ( $legit_removeable_plugins as $legit_plugin ) {
          if ( $legit_plugin->name == $plugin ) {
            exec('wp plugin deactivate ' . $plugin . ' --skip-plugins --skip-themes && wp plugin delete ' . $plugin . ' --skip-plugins --skip-themes', $output);
          }
        }
      }
    }
  }
  echo json_encode($output);
  wp_die();
}
