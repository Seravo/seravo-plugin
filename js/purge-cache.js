'use strict';
jQuery(document).ready(function($) {

  // Add event listener for clicking the purge cache button
  $('#wp-admin-bar-nginx-helper-purge-all .ab-item').click(function(evt) {
    evt.preventDefault();

    // Show spinning animation
    $('.seravo-purge-cache-icon', this).addClass('spin');

    // Ask for server to empty cache via AJAX
    $.post(seravo_purge_cache_loc.ajax_url, {
      action: 'seravo_purge_cache',
      nonce: seravo_purge_cache_loc.seravo_purge_cache_nonce,
    }, function(response) {
      var query_object = {
        'seravo_purge_success': response.success,
      };
      window.location = window.location.pathname + '?' + $.param(query_object);
    });

  });
});
