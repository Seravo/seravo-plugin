<?php

function seravo_reset_shadow() {
  if ( isset($_POST['resetshadow']) && ! empty($_POST['resetshadow']) ) {
    $shadow = $_POST['resetshadow'];
    $output = array();
    exec('wp-shadow-reset ' . $shadow . ' --force 2>&1', $output);
    echo json_encode($output);
  }
  wp_die();
}
