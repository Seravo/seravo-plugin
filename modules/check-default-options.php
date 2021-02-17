<?php

/*
 * Checks if an option has a default value attached to it
 * If not, set a default value
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

// The options that will be checked
$options = array(
  'seravo-disable-xml-rpc',
  'seravo-disable-json-user-enumeration',
  'seravo-disable-get-author-enumeration',
  'seravo-enable-optimize-images',
  'seravo-enable-strip-image-metadata',
);

foreach ( $options as $option ) {
  if ( ! get_option($option) ) {
    update_option($option, 'on');
  }
}
