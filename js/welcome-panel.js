// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function($) {
  // Test
  jQuery('#seravo-welcome-panel-subscribe-button').submit(function() {
    alert(jQuery('#seravo-welcome-panel-email-input').val());
    return false;
  });
});
