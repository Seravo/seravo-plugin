'use strict';

(function($) {
  $(window).load(function() {
      // auto-scroll to bottom of log viewers on page load
      $('.log-table-view').each(function() {
          $(this).scrollTop($('table', this).height())
      });

      $('.page-title-action').click(function(e) {
          e.preventDefault();
          $('#log-dialog').dialog('open');
      });

      $('#log-dialog').dialog({
          title: 'Add New Log',
          dialogClass: 'wp-dialog',
          autoOpen: false,
          draggable: false,
          width: 'auto',
          modal: true,
          resizable: false,
          closeOnEscape : true,
          position: {
          my: "center",
          at: "center",
          of: window
          },
          create: function(){
          $('.ui-dialog-titlebar-close').addClass('ui-button');
          },
          open: function(){
          $('.ui-widget-overlay').bind('click',function(){
              $('#log-dialog').dialog('close');
          })
          }
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
      console.log(payload);
      $.post(window.ajaxurl, payload, function(response) {
          var oldheight = $('table', $this).height();
          $('tbody', $this).prepend(response);
          $this.scrollTop($('table', $this).height() - oldheight);
          $overlay.remove();
      });
      }
  });

})(jQuery);
