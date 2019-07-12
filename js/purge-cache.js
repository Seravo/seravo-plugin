// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';
jQuery(document).ready(function($) {

  // Add event listener for clicking the purge cache button
  $('#wp-admin-bar-nginx-helper-purge-all .ab-item').click(function(evt) {
    evt.preventDefault();

    // Show spinning animation
    var purge_cache_icon = $('.seravo-purge-cache-icon', this);
    purge_cache_icon.addClass('spin');

    // Ask for server to empty cache via AJAX
    $.post(seravo_purge_cache_loc.ajax_url, {
      action: 'seravo_purge_cache',
      nonce: seravo_purge_cache_loc.seravo_purge_cache_nonce,
    }, function(response) {
      var query_params = {};
      // Query string without the initial question mark "?"
      var query_string = location.search.substring(1);
      // Regex for matching query string key=value pairs
      var query_regex = /([^&=]+)=([^&]*)/g;
      var match;

      // Create a map with the already existing query string parameters
      while (match = query_regex.exec(query_string)) {
        query_params[decodeURIComponent(match[1])] = decodeURIComponent(match[2]);
      }

      // Add or update the Seravo purge cache success parameter
      query_params['seravo_purge_success'] = response.success;
      location.search = $.param(query_params);

    }).fail(function(response) {
      purge_cache_icon.removeClass('spin');
    });
  });
});
