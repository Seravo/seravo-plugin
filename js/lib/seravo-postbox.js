// phpcs:disable PEAR.Functions.FunctionCallSignature
/**
 * Contains the Seravo postboxes logic, opening and closing Seravo postboxes, reordering and saving
 * the state and ordering to the database.
 *
 * @summary Contains Seravo postboxes logic
 */
var ajaxWidgets, ajaxPopulateWidgets;
window.wp = window.wp || {};

/**
 * This object contains all function to handle the behaviour of the post boxes. The post boxes are the boxes you see
 * around the content on the edit page.
 */
var seravo_postboxes;

jQuery(document).ready(function($) {
  var $document = $( document );

  seravo_postboxes = {

    /**
     * @summary Handles a click on either the postbox heading or the postbox open/close icon.
     *
     * Opens or closes the postbox. Expects `this` to equal the clicked element.
     * Calls postboxes.pbshow if the postbox has been opened, calls postboxes.pbhide
     * if the postbox has been closed.
     */
    handle_click : function () {
      var $el = $( this ),
        p = $el.parent( '.postbox' ),
        id = p.attr( 'id' ),
        ariaExpandedValue;
      if ( 'dashboard_browser_nag' === id ) {
        return;
      }

      p.toggleClass( 'closed' );

      ariaExpandedValue = ! p.hasClass( 'closed' );

      if ( $el.hasClass( 'handlediv' ) ) {
        // The handle button was clicked.
        $el.attr( 'aria-expanded', ariaExpandedValue );
      } else {
        // The handle heading was clicked.
        $el.closest( '.postbox' ).find( 'button.handlediv' )
          .attr( 'aria-expanded', ariaExpandedValue );
      }

      if ( seravo_postboxes.page !== 'press-this' ) {
        seravo_postboxes.save_state( seravo_postboxes.page );
      }

      if ( id ) {
        if ( ! p.hasClass('closed') && $.isFunction( seravo_postboxes.pbshow ) ) {
          seravo_postboxes.pbshow( id );
        } else if ( p.hasClass('closed') && $.isFunction( seravo_postboxes.pbhide ) ) {
          seravo_postboxes.pbhide( id );
        }
      }
    },

    /**
     * Adds event handlers to all postboxes and screen option on the current page.
     */
    add_postbox_toggles : function (page, args) {
      var $handles = $( '.seravo-postbox .hndle, .seravo-postbox .handlediv' );
      this.page = page;
      this.init( page, args );

      $handles.on( 'click.postboxes', this.handle_click );

      /**
       * @since 2.7.0
       */
      $('.seravo-postbox .hndle a').click( function(e) {
        e.stopPropagation();
      });

    },

    /**
     * @summary Initializes all the postboxes, mainly their sortable behaviour.
     */
    init : function(page, args) {
      var isMobile = $( document.body ).hasClass( 'mobile' ),
        $handleButtons = $( '.seravo-postbox .handlediv' );

      $.extend( this, args || {} );
      $('#wpbody-content').css('overflow','hidden');
      $('.seravo-postbox-holder .meta-box-sortables').sortable({
        placeholder: 'sortable-placeholder',
        connectWith: '.meta-box-sortables',
        items: '.seravo-postbox',
        handle: '.hndle',
        cursor: 'move',
        delay: ( isMobile ? 200 : 0 ),
        distance: 2,
        tolerance: 'pointer',
        forcePlaceholderSize: true,
        helper: function( event, element ) {
          /* `helper: 'clone'` is equivalent to `return element.clone();`
           * Cloning a checked radio and then inserting that clone next to the original
           * radio unchecks the original radio (since only one of the two can be checked).
           * We get around this by renaming the helper's inputs' name attributes so that,
           * when the helper is inserted into the DOM for the sortable, no radios are
           * duplicated, and no original radio gets unchecked.
           */
          return element.clone()
            .find( ':input' )
              .attr( 'name', function( i, currentName ) {
                return 'sort_' + parseInt( Math.random() * 100000, 10 ).toString() + '_' + currentName;
              } )
            .end();
        },
        opacity: 0.65,
        stop: function() {
          seravo_postboxes.save_order(page);
        },
        receive: function(e,ui) {
          if ( 'dashboard_browser_nag' == ui.item[0].id ) {
            $(ui.sender).sortable('cancel');
          }

          seravo_postboxes._mark_area();
          $document.trigger( 'postbox-moved', ui.item );
        }
      });

      if ( isMobile ) {
        $(document.body).bind('orientationchange.postboxes', function(){ seravo_postboxes._pb_change(); });
        this._pb_change();
      }

      this._mark_area();
    },

    /**
     * @summary Saves the state of the postboxes to the server.
     *
     * Saves the state of the postboxes to the server. It sends one list with all the closed postboxes.
     */
    save_state : function(page) {
      var closed = $( '.seravo-postbox' ).filter( '.closed' ).map( function() { return this.getAttribute('data-postbox-id') } ).get().join( ',' );

      $.post(ajaxurl, {
        action: 'seravo-closed-postboxes',
        closed: closed,
        seravo_closed_postboxes_nonce: $('#seravo-closed-postboxes-nonce').val(),
        page: page
      });
    },

    /**
     * @summary Saves the order of the postboxes to the server.
     *
     * Saves the order of the postboxes to the server. Sends a list of all postboxes
     * inside a sortable area to the server.
     */
    save_order : function(page) {
      var postVars, page_columns = $('.columns-prefs input:checked').val() || 0;

      postVars = {
        action: 'seravo-postbox-order',
        seravo_save_postbox_order_nonce: $('#seravo-postbox-order-nonce').val(),
        page_columns: page_columns,
        page: page
      };

      $('.seravo-postbox-holder .meta-box-sortables').each( function() {
        postVars[ 'order[' + this.id.split( '-' )[0] + ']' ] = $( this ).sortable( 'toArray', { attribute: 'data-postbox-id' } ).join( ',' );
      } );

      $.post( ajaxurl, postVars );
    },

    /**
     * @summary Marks empty postbox areas.
     *
     * Adds a message to empty sortable areas on the dashboard page. Also adds a
     * border around the side area on the post edit screen if there are no postboxes
     * present.
     */
    _mark_area : function() {
      var visible = $('div.seravo-postbox:visible').length, side = $('#post-body #side-sortables');

      $( '.seravo-postbox-holder .meta-box-sortables:visible' ).each( function() {
        var t = $(this);

        if ( visible == 1 || t.children('.seravo-postbox:visible').length ) {
          t.removeClass('empty-container');
        } else {
          t.addClass('empty-container');
          t.attr('data-emptyString', seravoPostboxl10n.postBoxEmptyString);
        }
      });

      if ( side.length ) {
        if ( side.children('.seravo-postbox:visible').length ) {
          side.removeClass('empty-container');
        } else if ( $('#postbox-container-1').css('width') == '280px' ) {
          side.addClass('empty-container');
        }
      }
    },

    /**
     * Changes the amount of columns on the post edit page.
     */
    _pb_edit : function(n) {
      var el = $('.seravo-postbox-holder').get(0);

      if ( el ) {
        el.className = el.className.replace(/columns-\d+/, 'columns-' + n);
      }

      /**
       * Fires when the amount of columns on the post edit page has been changed.
       */
      $( document ).trigger( 'postboxes-columnchange' );
    },

    /**
     * @summary Changes the amount of columns the postboxes are in based on the
     *          current orientation of the browser.
     */
    _pb_change : function() {
      var check = $( 'label.columns-prefs-1 input[type="radio"]' );

      switch ( window.orientation ) {
        case 90:
        case -90:
          if ( ! check.length || ! check.is(':checked') ) {
            this._pb_edit(2);
          }
          break;
        case 0:
        case 180:
          if ( $('#poststuff').length ) {
            this._pb_edit(1);
          } else {
            if ( ! check.length || ! check.is(':checked') ) {
              this._pb_edit(2);
            }
          }
          break;
      }
    },

    /* Callbacks */
    pbshow : false,
    pbhide : false
  };

  // These widgets are sometimes populated via ajax
  // @TODO: Use this mecahnism for the lazy loading of widgets. Replace "action" with e.g.
  // "seravo-postbox-content"
  ajaxWidgets = ['dashboard_primary'];

  ajaxPopulateWidgets = function(el) {
    function show(i, id) {
      var p, e = $('#' + id + ' div.inside:visible').find('.widget-loading');
      if ( e.length ) {
        p = e.parent();
        setTimeout( function(){
          p.load( ajaxurl + '?action=dashboard-widgets&widget=' + id + '&pagenow=' + pagenow, '', function() {
            p.hide().slideDown('normal', function(){
              $(this).css('display', '');
            });
          });
        }, i * 500 );
      }
    }

    if ( el ) {
      el = el.toString();
      if ( $.inArray(el, ajaxWidgets) !== -1 ) {
        show(0, el);
      }
    } else {
      $.each( ajaxWidgets, show );
    }
  };
  ajaxPopulateWidgets();

  if ( typeof pagenow !== 'undefined' ) {
    seravo_postboxes.add_postbox_toggles(pagenow, { pbshow: ajaxPopulateWidgets } );
  }

  $( '.seravo-postbox-holder .meta-box-sortables' ).sortable( 'option', 'containment', '#wpwrap' );

} );
