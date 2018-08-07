'use strict';
jQuery(document).ready(function($) {
  function seravo_ajax_delete_theme(theme_name, callback) {
    $.post(
      seravo_cruftthemes_loc.ajaxurl,
      { type: 'POST',
        'action': 'seravo_remove_themes',
        'removetheme': theme_name,
        'nonce': seravo_cruftthemes_loc.ajax_nonce, },
      function( rawData ) {
        var data = JSON.parse(rawData);
        if ( data ) {
          callback();
        } else {
          confirm(seravo_cruftthemes_loc.failure);
        }
      });
  }
  // Generic ajax report loader function
  function seravo_theme_load_report(section) {
    $.post(
      seravo_cruftthemes_loc.ajaxurl,
      { 'action': 'seravo_list_cruft_themes',
        'section': section,
        'nonce': seravo_cruftthemes_loc.ajax_nonce, },
      function(rawData) {
        if (rawData.length == 0) {
          $('#' + section).html(seravo_cruftthemes_loc.no_data);
        }
        $('#' + section + '_loading').fadeOut();
        var data = JSON.parse(rawData);
        // title, name, status

        if ( data.length != 0 ) {
          $( '#cruftthemes_status' ).prepend('<p>' + seravo_cruftthemes_loc.cruftthemes + '</p>');
          $( '#cruftthemes_status' ).append('<div><div class="cruft-theme-table"></div></div>');
          //Go through each data. If has a child, print later.
          $.each( data, function( i, theme ){
            if (theme.name != '' && theme.parent == '' ) {
              seravo_print_theme_table( theme );
            }
          });

          $('.crufttheme').each( function(i){
            var children = data.filter(theme => theme.parent == $(this).attr('data-theme-name'));
            if ( children.length != 0) {
              var titles = '';
              var handles = '';
              children.forEach(element => {
                seravo_print_theme_table( element, $(this) );
                titles += ', ' + element.title;
                handles += element.name + ' ';
              });
              $(this).append(
                '<div class="theme-relative-info" style="padding-left: 5px">' + seravo_cruftthemes_loc.isparentto +
                '</div><div class="theme-relatives" data-theme-children="' + handles.slice(0, -1) + '" style="padding-left: 5px">' + titles.slice(1) + '</div>'
              );
              $(this).find(':first-child').addClass('cruft-button-hide').removeClass('crufttheme-delete-button');
            }
          });
        } else {
          $( '#cruftthemes_status' ).prepend('<b>' + seravo_cruftthemes_loc.no_cruftthemes + '</b>');
        }
        $( '#cruftthemes_status_loading img' ).fadeOut
        $('.crufttheme-delete-button').click(function(event) {
          event.preventDefault();
          var is_user_sure = confirm(seravo_cruftthemes_loc.confirm);
          if ( ! is_user_sure ) {
            return;
          }
          var parent_row = $(this).parents(':eq(1)');
          var theme_name = parent_row.attr('data-theme-name');
          seravo_ajax_delete_theme(theme_name, function() {
            parent_row.animate({
              opacity: 0
            },
            600,
            function() {
              var container = parent_row.parents().eq(0);
              // in data we know which theme is this one's parent.
              // we remove the child from the parent.
              var parent = data.filter(theme => theme.name == parent_row.attr('data-theme-name'))[0].parent;
              var childtitle = data.filter(theme => theme.name == parent_row.attr('data-theme-name'))[0].title;
              if ( parent != '' ) {
                if ( $('.' + parent).find('.theme-relatives').attr('data-theme-children').replace(parent_row.attr('data-theme-name'),'') != '' ) {
                  $('.' + parent).find('.theme-relatives').attr('data-theme-children',$('.' + parent).find('.theme-relatives').attr('data-theme-children').replace(parent_row.attr('data-theme-name'),'').replace(/\s+/g,' ').trim());
                  $('.' + parent).find('.theme-relatives').html(removeValue($('.' + parent).find('.theme-relatives').html(), childtitle) );
                  // here we need to remove the appropiate parts from the data-* and string
                  // tr -> td .theme-relatives has data-* and HTML-text
                } else {
                  $('.' + parent).find('.theme-relatives').remove();
                  $('.' + parent).find('.theme-relative-info').remove();
                  $('.' + parent).find(':first-child').removeClass('cruft-button-hide').addClass('crufttheme-delete-button');

                }
              }
              parent_row.remove();
            });
          });
        });
      }
    ).fail(function() {
      $('#' + section + '_loading').html(seravo_cruftthemes_loc.fail);
    });
  }
  // titles for the titles that have been printed. theme for the one which is to be printed.
  function seravo_print_theme_table( theme, target='' ) {

    if ( target == '' ) {
      $( '.cruft-theme-table' ).append('<div class="crufttheme ' + theme.name + '" data-theme-name= "' + theme.name + '"><div class="crufttheme-delete">' +
      '<a href="" class="dashicons dashicons-trash crufttheme-delete-button" ></a></div><div>' + theme.title + '</div></div>');
    } else {
      target.addClass('crufttheme-parent');
      if ( ! target.next().hasClass('crufttheme-child-container') ) {
        target.after( '<div class="crufttheme-child-container ' + theme.parent + '"></div>');
      }
      //if target already has children, append after that

      $('.' + theme.parent + '.crufttheme-child-container').append('<div class="crufttheme-child crufttheme ' + theme.name + '" data-theme-name= "' + theme.name + '"><div class="crufttheme-delete child-delete-button">' +
      '<a href="" class="dashicons dashicons-trash crufttheme-delete-button" ></a></div><div>' + theme.title + '</div></div>');
    }
  }
  function removeValue(list, value) {
    list = list.split(',');
    list.splice(list.indexOf(value), 1);
    return list.join(',');
  }

  // Load on page load
  seravo_theme_load_report('cruftthemes_status');
});
