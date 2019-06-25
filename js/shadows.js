'use strict';

jQuery(document).ready(function($) {
  $('.shadow-data-actions').click(function(event) {
    event.preventDefault();

    var link = $(event.target)
    var row = $(link.closest('tr'));

    if ( link.hasClass('closed') ) {
      link.removeClass('closed');
      
      var to_production = build_data_action_gui(
        'Move shadow to production',
      '<b>Warning:</b> This will replace everything currently in the /data/wordpress/ directory and the databse of production site with a copy of the chosen shadow. Your site will be backed up on beforehand. <a href="https://help.seravo.com">Read up</a> on how to recover the site from backup.',
        'Move to Production',
        seravo_reset_shadow,
        'joosuakoskinen_867c8d',
        'production',
        'https://staging.pisara.eu',
        '://' + location.hostname
      );
      var to_shadow = build_data_action_gui(
        'Reset shadow from production',
        '<b>Warning:</b> This will replace everything currently in the </i>/data/wordpress/<i> directory and the databse of the shadow with a copy of production site. Make sure to know what you are doing.',
        'Reset Shadow',
        seravo_reset_shadow,
        'production',
        'joosuakoskinen_867c8d',
        'https://pisara.eu',
        'https://staging.pisara.eu',
        false
      ); 
      
      var data_action_row = jQuery(jQuery.parseHTML('<tr class="shadow-data-action-row"></tr>'))
                            .append(jQuery(jQuery.parseHTML('<td colspan="5"></td>'))
                            .append($('<hr>')).append(to_production).append(to_shadow).append($('<hr>')));

      row.after(data_action_row);
      row.after('<tr></tr>');
    } else {
      link.addClass('closed');

      if ( row.next().next().hasClass('shadow-data-action-row') ) {
        row.next().next().remove();
        row.next().remove();
      }
    }
    //seravo_reset_shadow($(this).attr("data-shadow-name"));
  });

  function build_data_action_gui(title, text, button, exec_func, from_instance, to_instance, from_domain, to_domain, sr_enabled = true) {
    from_domain = from_domain.replace('https://', '://').replace('http://', '://');
    to_domain = to_domain.replace('https://', '://').replace('http://', '://');
    var $sr_checkbox = $(jQuery.parseHTML('<input type="checkbox" name="search-replace" disabled ' + (sr_enabled ? 'checked' : '') + '>'));
    var $sr_from = $(jQuery.parseHTML('<input type="text" name="sr-from" value="' + from_domain + '" disabled>'));
    var $sr_to = $(jQuery.parseHTML('<input type="text" name="sr-to" value="' + to_domain + '" disabled>'));
    var $exec_btn = $(jQuery.parseHTML('<input type="submit" value="' + button + '" class="button">'));
    
    $sr_checkbox.click(function(event) {
      if ( $(this).is(":checked") ) {
        $sr_from.prop("disabled", false);
        $sr_to.prop("disabled", false);
      } else {
        $sr_from.prop("disabled", true);
        $sr_to.prop("disabled", true);
      }
    });

    $exec_btn.click(function(event) {
      exec_func(to_instance);
    });

    var $sr = $('<table class="rs-table"></table>')
              .append($sr_checkbox.add($('<span> Execute search-replace</span>')).wrapAll('<tr><th colspan="2"></th></tr>').parent().parent())
              .append($($('<td>From:</td>')).add(($sr_from).wrap('<td></td>').parent()).wrapAll('<tr></tr>').parent())
              .append($($('<td>To:</td>')).add(($sr_to).wrap('<td></td>').parent()).wrapAll('<tr></tr>').parent());

    var $gui = $('<td style="width:50%;"></td>')
                .append('<h3>' + title + '</h3>',
                        '<i>' + from_instance + ' > ' + to_instance + '</i>',
                        '<p style="margin-top: 15px;">' + text + '</p>');

    return $gui.append($sr).append($exec_btn);
  }

  function seravo_reset_shadow(shadow) {
    var is_user_sure = confirm(seravo_shadows_loc.confirm);
    if ( ! is_user_sure) {
      return;
    }
    animate(event.attr(shadow, 'progress'));
    $.post(
      seravo_shadows_loc.ajaxurl,
      { type: 'POST',
        'action': 'seravo_reset_shadow',
        'resetshadow': shadow,
        'nonce': seravo_shadows_loc.ajax_nonce, },
        function( rawData ) {
          var data = JSON.parse(rawData);
          // If the last row of rawData does not begin with SUCCESS:
          if ( data[data.length - 1].search('Success') ) {
            animate('success');
          } else {
            animate('failure');
          }
        }
    );
  }

  function animate( target, status ) {
    if ( status == 'progress' ) {
      target.disabled = true;
    } else if ( status == 'success' ) {
      target.innerHTML = seravo_shadows_loc.success;
    } else if ( status == 'failure' ) {
      target.innerHTML = seravo_shadows_loc.failure;
    } else {
      target.innerHTML = seravo_shadows_loc.error;
    }
  }

});
