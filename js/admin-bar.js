'use strict';

jQuery(document).ready(
  function() {
    // Add event listener for clicking the purge cache button
    jQuery('#wp-admin-bar-nginx-helper-purge-all .ab-item').click(seravo_admin_bar.purge_cache);
    // Add event listener for instance switcher shadow buttons
    jQuery('#wp-admin-bar-instance-switcher li.shadow-link > a').click(seravo.shadow.switch_instance);
    // Add event listener for instance switcher exit button
    jQuery('#wp-admin-bar-instance-switcher li.shadow-exit > a').click(seravo.shadow.exit);
  }
);

var seravo_admin_bar = {

  /**
   * Function called on purge-cache button click.
   * @param {Event} event The click event.
   */
  purge_cache: function(event) {
    event.preventDefault();
    // Show spinning animation
    var purge_cache_icon = jQuery('.seravo-purge-cache-icon', this);
    purge_cache_icon.addClass('spin');

    // Ask for server to empty cache via AJAX
    jQuery.post(
      seravo_purge_cache_ajax_url,
      {
      action: 'seravo_purge_cache',
      nonce: seravo_purge_cache_nonce,
      },
      function(response) {
        // Success
        response = jQuery.parseJSON(response);

        if ( 'success' in response ) {
          seravo.add_url_param('seravo-purge-success', response.success);
        } else {
          seravo.add_url_param('seravo-purge-success', false);
        }

        window.location.reload();
      }
    ).fail(
      function(response) {
        // Failure
        purge_cache_icon.removeClass('spin');
      }
    );
  },

}
