<?php

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

/**
 * @return string[] Output from verify-checksums command.
 */
function seravo_report_wp_core_verify() {
  exec('wp core verify-checksums 2>&1', $output);
  array_unshift($output, '$ wp core verify-checksums');
  return $output;
}

/**
 * @return string[] Output from git status or error if git not available.
 */
function seravo_report_git_status() {
  exec('git -C /data/wordpress status', $output);

  if ( $output !== array() ) {
    return array(
      'Git is not used on this site. To start using it,
      read our documentation for WordPress developers at
      <a href="https://seravo.com/docs/" target="_BLANK">seravo.com/docs</a>.',
    );
  }

  array_unshift($output, '$ git status');
  return $output;
}

/**
 * @return void
 */
function seravo_reset_shadow() {
  if ( isset($_POST['shadow']) && $_POST['shadow'] !== '' ) {
    $shadow = $_POST['shadow'];
    $output = array();

    // Check if the shadow is known
    $shadows = API::get_site_data('/shadows');

    if ( is_wp_error($shadows) ) {
      wp_die();
    }

    foreach ( $shadows as $data ) {
      if ( $data['name'] == $shadow ) {
        exec('wp-shadow-reset ' . $shadow . ' --force 2>&1', $output);
        echo json_encode($output);
        wp_die();
      }
    }
  }
  wp_die();
}

/**
 * @return void
 */
function seravo_ajax_site_status() {
  check_ajax_referer('seravo_site_status', 'nonce');

  switch ( sanitize_text_field($_REQUEST['section']) ) {

    case 'wp_core_verify':
      echo wp_json_encode(seravo_report_wp_core_verify());
      break;

    case 'git_status':
      echo wp_json_encode(seravo_report_git_status());
      break;

    case 'seravo_reset_shadow':
      seravo_reset_shadow();
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();

}
