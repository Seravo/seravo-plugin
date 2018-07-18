'use strict';

function removeValue(list, value) {
  list = list.split(',');
  list.splice(list.indexOf(value), 1);
  return list.join(',');
}

class CruftTheme {
  constructor(name, title, active) {
    this.name = name;
    this.title = title;
    this.active = active;
  }
  seravo_print_theme_table( target ) {
    if ( ! this.active ) {
      target.append('<div id="' + this.name + '" class="crufttheme ' + this.name + '" data-theme-name= "' + this.name + '"><div id="button' + this.name + '" class="crufttheme-delete">' +
      '<a href="" class="dashicons dashicons-trash crufttheme-delete-button" ></a></div><div>' + this.title + '</div></div>');
    }
    return( ! this.active );
  }
}

class CruftParent extends CruftTheme {
  constructor(name, title, active, children) {
    super(name, title, active);
    this.children = children;
  }
  seravo_print_theme_table( target ){
    if ( super.seravo_print_theme_table( target ) ) {
      document.querySelector( '#' + this.name ).className += ' crufttheme-parent';
      var titles = '';
      var handles = '';
      this.children.forEach(element => {
        target.append( '<div id="' + element.parent + 'box" class="crufttheme-child-container"></div>');
        element.seravo_print_theme_child( document.querySelector( '#' + this.name + 'box' ) );
        titles += ', ' + element.title;
        handles += element.name + ' ';
      });
      document.querySelector( '#' + this.name ).innerHTML += '<div class="theme-relative-info" style="padding-left: 5px">' + seravo_cruftthemes_loc.isparentto
        + '</div><div class="theme-relatives" data-theme-children="' + handles.slice(0, -1) + '" style="padding-left: 5px">' + titles.slice(1) + '</div>';
      target.find('#button' + this.name).addClass('cruft-button-hide').removeClass('crufttheme-delete-button');
    } else {
      this.children.forEach(element => {
        element.seravo_print_theme_table( target );
      });
    }
  }

}

class CruftChild extends CruftTheme {
  constructor(name, title, active, parent) {
    super(name, title, active);
    this.parent = parent;
  }
  seravo_print_theme_child( target_box ) {
    if ( ! this.parent.active && ! this.active ) {
      target_box.innerHTML += '<div id="' + this.name + '" class="crufttheme-child crufttheme ' + this.name + '" data-theme-name= "' + this.name + '"><div class="crufttheme-delete child-delete-button">' +
      '<a href="" class="dashicons dashicons-trash crufttheme-delete-button" ></a></div><div>' + this.title + '</div></div>';
    }
  }
}

jQuery(document).ready(function($) {
  function seravo_ajax_delete_theme(theme_name, parent_row, callback) {
    $.post(
      seravo_cruftthemes_loc.ajaxurl,
      { type: 'POST',
        'action': 'seravo_remove_themes',
        'removetheme': theme_name,
        'nonce': seravo_cruftthemes_loc.ajax_nonce, },
      function( rawData ) {
        var data = JSON.parse(rawData);
        if ( data ) {
          callback(parent_row, data);
        } else {
          confirm(seravo_cruftthemes_loc.failure);
        }
      });
  }

  function seravo_delete_theme(parent_row, data) {
    parent_row.animate({
      opacity: 0
    }, 600, function() {
      parent_row.remove();
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
        // title, name, parent, active
        var CTTID = 'cruft-theme-table';
        if ( data.length > 1 ) {
          $( '#cruftthemes_status' ).prepend('<p>' + seravo_cruftthemes_loc.cruftthemes + '</p>');
          $( '#cruftthemes_status' ).append('<div><div id="' + CTTID + '" class="cruft-theme-table"></div></div>');
          var parent_themes = new Array();
          var child_themes = new Array();
          // go through each item and categorize them
          $.each( data, function( i, theme ){
            if (theme.name != '' && theme.parent == '' ) {
              parent_themes.push(new CruftTheme(theme.name, theme.title, theme.active));
            } else if (theme.name != '' && theme.parent != '' ) {
              child_themes.push(new CruftChild(theme.name, theme.title, theme.active, theme.parent));
            }
          });

          parent_themes.forEach( function(parent, index, this_array){
            if ( child_themes.filter(theme => theme.parent == parent.name).length != 0) {
              this_array[index] = new CruftParent( parent.name, parent.title, parent.active, child_themes.filter(theme => theme.parent == parent.name) );
            }
          });

          parent_themes.forEach( function(element){
            element.seravo_print_theme_table( $('#' + CTTID ));
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
          seravo_ajax_delete_theme(theme_name, parent_row, seravo_delete_theme);
        });
      }
    ).fail(function() {
      $('#' + section + '_loading').html(seravo_cruftthemes_loc.fail);
    });
  }
  // titles for the titles that have been printed. theme for the one which is to be printed.
  // Load on page load
  seravo_theme_load_report('cruftthemes_status');
});
