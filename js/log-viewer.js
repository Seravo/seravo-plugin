'use strict';

jQuery(document).ready(
  function() {
    jQuery('.log-viewer-wrapper').each(
      function() {
        log_viewer.init_log_viewer(this);
      }
    );
  }
);

var log_viewer = {

  /**
   * Initialize log viewer and load the default log.
   * @param {*} log_viewer_elem The '.log-viewer-wrapper' element.
   */
  init_log_viewer: function(log_viewer_elem) {
    // Get the postbox ID for AJAX requests
    var postbox = jQuery(log_viewer_elem).closest('.seravo-postbox').attr('data-postbox-id');

    // Select the default log group
    var group = jQuery(log_viewer_elem).find('.log-menu-entry.selected');
    if ( group.length === 0 ) {
      group = jQuery(log_viewer_elem).find('.log-menu-entry:first');
      if ( group.length === 0 ) {
        return;
      }
    }

    var variation_id = parseInt(jQuery(log_viewer_elem).find('.log-view-date').data('default-variation'));
    var variations = group.data('variations');
    var variation = variations[variation_id];
    var file = variation['file'];

    var keyword = jQuery(log_viewer_elem).find('.log-view-search > input').val();

    // Init row clicking
    jQuery(log_viewer_elem).find('.log-table').on(
      'click',
      'td',
      function() {
        var text = '<b>/data/log/' + file + ':</b> ' + jQuery(this).text();
        jQuery(log_viewer_elem).find('.info-bar-wrapper').html('<p>' + text + '</p>');
      }
    );

    // Init search button
    jQuery(log_viewer_elem).find('.log-view-search > button').click(
      function() {
        keyword = jQuery(log_viewer_elem).find('.log-view-search > input').val();
        seravo.add_url_param('log-keyword', keyword);
        load_log();
      }
    );

    // Init log menu buttons
    jQuery(log_viewer_elem).find('.log-menu-entry').click(
      function() {
        // Change the '.selected' in side menu
        group.removeClass('selected');
        group = jQuery(this);
        group.addClass('selected');

        // Load the variations for new log group and load the latest
        variations = group.data('variations');
        variation_id = 0;
        load_log();
      }
    );

    // Init 'previous date' button
    jQuery(log_viewer_elem).find('.log-date-pick.date-previous').click(
      function() {
      if ( variation_id + 1 > variations.length - 1 ) {
          // No previous variation
          return;
      }
        variation_id++;
        load_log();
      }
    );

    // Init 'next date' button
    jQuery(log_viewer_elem).find('.log-date-pick.date-next').click(
      function() {
      if ( variation_id - 1 < 0 ) {
          // No next variation
          return;
      }
        variation_id--;
        load_log();
      }
    );

    // Init scroll loader
    jQuery('.log-view').on(
      'scroll',
      function() {
        var log_table = jQuery(this).find('.log-table');
        if ( 0 == jQuery(this).scrollTop() && log_table.find('td').length > 0 && ! jQuery(this).closest('.log-view-spinner').is(':visible') ) {
            if ( jQuery(this).data('on-top') !== true ) {
              jQuery(this).data('on-top', true);

              // Load more lines
              var offset = jQuery(this).find('.log-table').find('tr').length;
              log_viewer.load_log_file(postbox, log_viewer_elem, file, offset, keyword);
              }
        } else {
            jQuery('.log-view').data('on-top', false);
        }
      }
    );

    /**
     * Load a new log file.
     */
    function load_log() {
      if ( variations.length > 1 ) {
        if ( variation_id > 0 ) {
          jQuery(log_viewer_elem).find('.log-date-pick.date-next').removeClass('disabled');
        } else {
          jQuery(log_viewer_elem).find('.log-date-pick.date-next').addClass('disabled');
        }
        if ( variation_id < variations.length - 1 ) {
          jQuery(log_viewer_elem).find('.log-date-pick.date-previous').removeClass('disabled');
        } else {
          jQuery(log_viewer_elem).find('.log-date-pick.date-previous').addClass('disabled');
        }
      } else {
        jQuery(log_viewer_elem).find('.log-date-pick.date-previous').addClass('disabled');
        jQuery(log_viewer_elem).find('.log-date-pick.date-next').addClass('disabled');
      }

      variation = variations[variation_id];
      file = variation['file'];

      seravo.add_url_param('logfile', file);

      // Empty selected line
      jQuery(log_viewer_elem).find('.info-bar-wrapper').html('');

      // Scroll down
      log_viewer.scroll_down(log_viewer_elem);

      // Load new file
      log_viewer.set_variation_date(log_viewer_elem, variation);
      log_viewer.load_log_file(postbox, log_viewer_elem, file, -1, keyword);
    }

    // Load default log
    load_log();
  },

  load_log_file: function(postbox, log_viewer_elem, file, offset, keyword) {
    // Show the spinner
    jQuery(log_viewer_elem).find('.log-view-spinner').show();

    // Remove old log if file changed
    var log_table = jQuery(log_viewer_elem).find('.log-table');
    if ( log_table.data('logfile') !== file || offset === -1 ) {
      log_table.data('logfile', file);
      log_table.find('tr').remove();
    }

    /**
     * Called after next 30 log rows have been loaded.
     * @param {*} data Decoded data from AJAX request on success.
     */
    function on_success(data) {
      log_viewer.render_lines(log_viewer_elem, data['output']);
    }

    /**
     * Called if either AJAX request or log reading failed.
     * @param {*} error An error message to be shown.
     */
    function on_error(error) {
      log_viewer.set_error(log_viewer_elem, error);
    }

    // Request next 30 rows
    seravo_ajax_request(
      'get',
      postbox,
      'fetch-logs',
      on_success,
      on_error,
      {
      file: file,
      offset: offset,
      'log-keyword': keyword,
      }
    );
  },

  render_lines: function(log_viewer_elem, lines) {
    var log_table_wrapper = jQuery(log_viewer_elem).find('.log-view');
    var log_table = jQuery(log_viewer_elem).find('.log-table');
    var spinner = jQuery(log_viewer_elem).find('.log-view-spinner');

    // Something went wrong?
    if ( lines === undefined ) {
      spinner.hide();
      return;
    }

    var height = log_table_wrapper[0].scrollHeight;
    if ( log_table.find('tr').length === 0 ) {
      // New log file
      height = 0;
    }

    lines.forEach(
      function(line) {
        log_table.prepend('<tr><td>' + line + '</td></tr>');
      }
    );

    if ( log_table.find('tr').length === 0 ) {
      // Empty log file
      log_viewer.set_error(log_viewer_elem, 'Empty log file');
    }

    log_table.show();
    log_table_wrapper.scrollTop(log_table_wrapper[0].scrollHeight - height);
    spinner.hide();
  },

  /**
   * Show an error instead of the logs.
   * @param {*} log_viewer_elem The '.log-viewer-wrapper' element.
   * @param {*} error           The error to be shown (text/html).
   */
  set_error: function(log_viewer_elem, error) {
    var log_table = jQuery(log_viewer_elem).find('.log-table');
    var spinner = jQuery(log_viewer_elem).find('.log-view-spinner');

    log_table.find('tr').remove();
    log_table.html('<tr><td><b>' + error + '</b></td></tr>');

    log_table.show();
    spinner.hide();
  },

  /**
   * Scroll all the way down on log view table.
   * @param {*} log_viewer_elem The '.log-viewer-wrapper' element.
   */
  scroll_down: function(log_viewer_elem) {
    var log_table_wrapper = jQuery(log_viewer_elem).find('.log-view');
    log_table_wrapper.scrollTop(log_table_wrapper[0].scrollHeight);
  },

  set_variation_date: function(log_viewer_elem, variation) {
    var since = variation['since'];
    if ( since !== null ) {
      var y = since.substr(0, 4);
      var m = since.substr(4, 2);
      var d = since.substr(6, 2);
      since = y + '-' + m + '-' + d;
    } else {
      since = '';
    }

    var until = variation['until'];
    if ( until !== null ) {
      var y = until.substr(0, 4);
      var m = until.substr(4, 2);
      var d = until.substr(6, 2);
      until = y + '-' + m + '-' + d;
    } else {
      until = 'Now';
    }

    var date = since + ' — ' + until;
    jQuery(log_viewer_elem).find('.log-date-input').val(date);
  }

}
