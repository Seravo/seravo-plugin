<?php

namespace Seravo\Postbox;

/**
 * TODO: REMOVE THIS TEMP FILE
 *
 * This code is horrible but is needed for now.
 * It can't be in handler.php with the new OOP design.
 *
 * This will be removed with the postbox refactoring.
 */


/**
 * Create singleton factory class for Seravo postboxes if not set.
 */
global $seravo_postbox_factory;
if ( ! isset($seravo_postbox_factory) ) {
  $seravo_postbox_factory = Seravo_Postbox_Factory::get_instance();
}

/**
 * Add a Seravo postbox. This function is only a wrapper for Seravo_Postbox_Factory::add_postbox, but
 * it unifies the Postbox API with the WP core add_meta_box.
 * @param string   $id            Unique id/slug of the postbox.
 * @param string   $title         Display title of the postbox.
 * @param callable $callback      A function that outputs the postbox content.
 * @param string   $screen        Admin screen id where the postbox should be displayed in.
 * @param string   $context       Default admin dashboard context where the postbox should be displayed in.
 * @param mixed[]  $callback_args Array of arguments that will get passed to the callback function.
 * @return void
 */
function seravo_add_raw_postbox( $id, $title, $callback, $screen = 'tools_page', $context = 'normal', $callback_args = array() ) {
  global $seravo_postbox_factory;
  $seravo_postbox_factory->add_postbox($id, $title, $callback, $screen, $context, $callback_args);
}

/**
 * Add a Seravo postbox. This function is only a wrapper for Seravo_Postbox_Factory::add_postbox, but
 * it unifies the Postbox API with the WP core add_meta_box.
 * @param string                  $screen  Admin screen id where the postbox should be displayed in.
 * @param \Seravo\Postbox\Postbox $postbox Seravo Postbox to be added.
 * @return void
 */
function seravo_add_postbox( $screen, Postbox $postbox ) {
  global $seravo_postbox_factory;

  $seravo_postbox_factory->add_postbox(
    $postbox->id,
    $postbox->title,
    function () use ( $postbox ) {
      $postbox->_build();
    },
    $screen,
    $postbox->context,
    array()
  );
}

/**
 * Display a page with currently registered Seravo postboxes.
 * @return void
 */
function seravo_postboxes_page() {
  global $seravo_postbox_factory;
  $seravo_postbox_factory->display_postboxes_page('four_column');
}

/**
 * @return void
 */
function seravo_two_column_postboxes_page() {
  global $seravo_postbox_factory;
  $seravo_postbox_factory->display_postboxes_page('two_column');
}

/**
 * @return void
 */
function seravo_wide_column_postboxes_page() {
  global $seravo_postbox_factory;
  $seravo_postbox_factory->display_postboxes_page('one_column');
}
