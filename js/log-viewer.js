// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function($) {
  $(window).on('load', function() {
    // auto-scroll to bottom of log viewers on page load
    $('.log-table-view').each(function() {
      $(this).scrollTop($('table', this).height())
    });
  });

  $('.log-table-view').on('scroll', function(e) {
    var $this = $(this);
    if ( 0 == $this.scrollTop() && 0 == $this.find('.overlay').length ) {
      var $overlay = $('<div class="overlay"><div>');
      $this.append( $overlay );
      // load more lines
      var offset = $('td', this).length;
      var payload = {
        'action': 'fetch_log_rows',
        'logfile': $this.data('logfile'),
        'offset': $('td', this).length,
        'regex': $this.data('regex'),
        'cutoff_bytes': $this.data('logbytes')
      };

      $.post(window.ajaxurl, payload, function(response) {
        var oldheight = $('table', $this).height();
        $('tbody', $this).prepend(response);
        $this.scrollTop($('table', $this).height() - oldheight);
        $overlay.remove();
      });
    }
  });

  // Script for showing clicked row on log-view-active box
  $('.log-table-view').on('click', '.logrow', function() {
    var $row = jQuery(this).closest("td"); // Find the row
    var $text = $row.find(".logrow").text(); // Find the text

    $('.log-view-active').text($text);
  });
});
