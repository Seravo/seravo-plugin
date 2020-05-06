// phpcs:disable PEAR.Functions.FunctionCallSignature
'use strict';

jQuery(document).ready(function($) {

  var domains_table = {

    query: {
      'orderby': 'domain',
      'order': 'asc',
    },

    display: function () {

      jQuery.get(seravo_domains_loc.ajaxurl, {
          'action': 'seravo_ajax_domains',
          'section': 'get_domains_table',
          'nonce': seravo_domains_loc.ajax_nonce,
          'orderby': domains_table.query.orderby,
          'order': domains_table.query.order,
        },

        function (rawData) {
          if (rawData !== '0' && rawData.length > 0) {
            jQuery("#domains-table-wrapper").html(rawData);
            domains_table.init();
          } else {
            jQuery("#domains-table-spinner").html('<b>' + seravo_domains_loc.domains_load_failed + '</b>');
          }
        }
      ).fail(function () {
        jQuery("#domains-table-spinner").html('<b>' + seravo_domains_loc.domains_load_failed + '</b>');
      });

    },

    init: function () {

      $("#domains-table-spinner .sort-spinner, #domains-table-spinner .action-spinner").remove();

      $('#domains-table-wrapper .manage-column.sortable a, #domains-table-wrapper .manage-column.sorted a').click(function (e) {
        e.preventDefault();

        var orderby = $(e.target).closest('th').attr('id');
        var order = $(e.target).closest('th').hasClass('desc') ? 'asc' : 'desc';

        $(e.target).closest('a').append('<img class="sort-spinner" src="/wp-admin/images/spinner.gif">');
        domains_table.sort(orderby, order);
      });

      $('#domains-table-wrapper span.view a, #domains-table-wrapper span.edit a, #domains-table-wrapper span.primary a').click(function (e) {
        if ( ! $(e.target).hasClass('action-link-disabled') ) {
          e.preventDefault();

          let params = new URLSearchParams(this.search);
          var domain = params.get('domain');
          var action = params.get('action');

          if ( action === 'primary' ) {
            tb_show('Primary domain confirmation', '#TB_inline?width=600&height=120&inlineId=domains-table-primary-modal');
            $('#primary-domain-cancel').click(tb_remove);
            $('#primary-domain-proceed').click(function() {
              domains_table.make_primary(domain);
            })
          } else {
            $('#domains-table-wrapper .action-spinner').remove();
            $(e.target).closest('.row-actions').append('<img class="action-spinner" src="/wp-admin/images/spinner.gif">');
            domains_table.show_action_row(domain, action);
          }
        }
      });

    },

    sort: function (orderby, order) {

      domains_table.query = {
        orderby: orderby,
        order: order,
      };

      domains_table.display();

    },

    show_action_row: function (domain, action) {

      action = action.replace('view', 'get_dns_table');
      action = action.replace('edit', 'edit_dns_table');
      action = action.replace('sniff', 'sniff_dns_table');

      var action_row = $('tr[data-domain="' + domain + '"]').next().next();
      var data_row = $(action_row).find('.action-row-data');

      jQuery.get(seravo_domains_loc.ajaxurl, {
          'action': 'seravo_ajax_domains',
          'section': action,
          'nonce': seravo_domains_loc.ajax_nonce,
          'domain': domain,
        },
        function(data) {
          $('#domains-table-wrapper .action-row').hide();
          $('#domains-table-wrapper .action-row-data').html('');

          if ( data !== 0 && data.length > 0 ) {
            data_row.html(data);
          } else {
            data_row.html('<b>' + seravo_domains_loc.section_failed + '</b>');
          }

          domains_table.init_action_row(domain, action_row);
          action_row.show();
          $('#domains-table-wrapper .action-spinner').remove();
        }
      ).fail(function (error) {
        $('#domains-table-wrapper .action-row').hide();
        $('#domains-table-wrapper .action-row-data').html('');

        data_row.html('<b>' + seravo_domains_loc.section_failed + '</b>');
        action_row.show();
        $('#domains-table-wrapper .action-spinner').remove();
      });

    },

    init_action_row: function (domain, action_row) {

      // Finding ID seems stupid but is for security reasons
      var update_zone_button = $(action_row).find('#update-zone-btn');
      var publish_zone_button = $(action_row).find('#publish-zone-btn');

      update_zone_button.add(publish_zone_button).click(function(e) {
        e.preventDefault();
        domains_table.zone.edit_zone(domain, action_row);
      });

    },

    make_primary: function (domain) {

      $('#primary-domain-proceed, #primary-domain-cancel').prop('disabled', true);
      $('#primary-modal-text').html(
        '<img class="primary-spinner" src="/wp-admin/images/spinner.gif">' +
        seravo_domains_loc.changing_primary
      );

      jQuery.post(seravo_domains_loc.ajaxurl, {
          'action': 'seravo_ajax_domains',
          'section': 'set_primary_domain',
          'nonce': seravo_domains_loc.ajax_nonce,
          'domain': domain,
        },
        function(data) {
          if ( data.length === 0 ) {
            $('#primary-modal-text').html(seravo_domains_loc.primary_failed);
            return;
          }

          var response = JSON.parse(data);
          if ( response['search-replace'] ) {
            location.replace(location.href.replace(location.hostname, domain));
          } else {
            $('#primary-modal-text').html(seravo_domains_loc.primary_no_sr);
          }
        }
      ).fail(function (error) {
        $('#primary-modal-text').html(seravo_domains_loc.primary_failed);
      });

    },

    zone: {

      edit_zone: function(domain, action_row) {

        var compulsory = $(action_row).find("textarea[name='compulsory']");
        var editable = $(action_row).find("textarea[name='zonefile']");
        var response_div = $(action_row).find('#zone-edit-response');
        var spinner_div = $(action_row).find('#zone-update-spinner');
        var update_zone_button = $(action_row).find('#update-zone-btn');
        var publish_zone_button = $(action_row).find('#publish-zone-btn');

        $('.zone-spinner').remove();
        response_div.html('');
        spinner_div.html('<img class="zone-spinner" src="/wp-admin/images/spinner.gif">');

        if ( ! editable.val().length ) {
          return;
        }
        if ( update_zone_button.length ) {
          update_zone_button.attr("disabled", true);
        }

        jQuery.post(seravo_domains_loc.ajaxurl, {
            'action': 'seravo_ajax_domains',
            'section': 'update_zone',
            'domain': domain,
            'compulsory': compulsory.val(),
            'zonefile': editable.val(),
            'nonce': seravo_domains_loc.ajax_nonce,
          }, function (rawData) {
            if ( rawData.length === 0 ) {
              // Eg. AJAX callback not found
              editable.prop('readonly', true);
              publish_zone_button.attr("disabled", true);
              response_div.html('<p><b>' + seravo_domains_loc.zone_update_no_data + '</b></p>');
              return;
            }

            var response = JSON.parse(rawData);
            var response_text = '';
            var response_class = 'success';

            if ( response['status'] && response['status'] !== 200 && response['reason'].length ) {
              response_text = response['reason'];
              response_class = 'error';
              update_zone_button.attr("disabled", false);
            } else if ( publish_zone_button.length ) {
              // Zone was published, show zone edit
              domains_table.show_action_row(domain, 'edit');
              return;
            } else {
              var diff = response['diff'];
              if ( diff != null && diff.length > 0 ) {

                response_text = seravo_domains_loc.zone_update_success;
                response_class = 'success';

                var diff_html = '<div class="zone-success-wrapper">';
                diff.split('\n').forEach(line => {
                  if ( line.startsWith('+') && line.substring(0, 3) !== '+++' ) {
                    diff_html += '<span style="color:#009900">' + line.replace('+', '+&nbsp;') + '</span><br>';
                  } else if ( line.startsWith('-') && line.substring(0, 3) !== '---' ) {
                    diff_html += '<span style="color:#ff3333">' + line.replace('-', '-&nbsp;') + '</span><br>';
                  } else {
                    diff_html += '<span>' + line + '</span><br>';
                  }
                });
                diff_html += '</div>';

                $(editable).replaceWith(diff_html);
              } else {
                response_text = seravo_domains_loc.update_no_changes;
                response_class = 'warning';
                update_zone_button.attr("disabled", false);
              }
            }
            response_text = '<div class="notice notice-' +
              response_class + ' is-dismissible" ' +
              'style="margin:0;padding-right:0;"><p>' + response_text;
            if ( ! $(action_row).find("textarea[name='zonefile']").length ) {
              response_text += '';
              response_text += '<button class="continue-button alignright">' + seravo_domains_loc.continue_edit +
                '<img class="continue-edit-spinner" style="display:none;" src="/wp-admin/images/spinner.gif"></button>';
            }
            response_text += '</div></p>';
            response_div.html(response_text);

            $('.continue-button').click(function () {
              $('.continue-edit-spinner').show();
              domains_table.show_action_row(domain, 'edit');
            });
          }
        ).fail(function (error) {
          editable.prop('readonly', true);
          update_zone_button.attr("disabled", true);
          publish_zone_button.attr("disabled", true);
          response_div.html(
            '<div class="notice notice-error is-dismissible" style="margin:0;">' +
            '<p><b>' + seravo_domains_loc.zone_update_failed + '</b></p>' +
            '</div>'
          );
        }).always(function () {
          $('.zone-spinner').remove();
        });

      },

      fetch_zone: function(domain, action_row) {

        var compulsory = $(action_row).find("textarea[name='compulsory']");
        var editable = $(action_row).find("textarea[name='zonefile']");
        var response_div = $(action_row).find('#zone-edit-response');
        var update_zone_button = $(action_row).find('#update-zone-btn');

        jQuery.post(seravo_domains_loc.ajaxurl, {
            'action': 'seravo_ajax_domains',
            'section': 'fetch_dns',
            'domain': $("input[name='domain']").val(),
            'nonce': seravo_domains_loc.ajax_nonce,
          }, function (rawData) {
            if (rawData === 0) {
              // If eg. AJAX callback not found
              fetch_error(seravo_domains_loc.fetch_no_data);
              return;
            }

            var response = JSON.parse(rawData);

            if (response['reason'] && response['status'] && response['status'] === 400) {
              fetch_error(response['reason']);
              return;
            }
            if (response['error']) {
              fetch_error(response['error']);
              return
            }

            compulsory.val(response['compulsory']['records'].join("\n"));
            editable.val(response['editable']['records'].join("\n"));
            editable.prop('readonly', false);
          }
        ).fail(function (error) {
          fetch_error(seravo_domains_loc.fetch_failed);
        });

        function fetch_error(error) {
          response_div.html('<p style="margin:0;"><b>' + error + '</b></p>');
          // Disable further editing when errors
          editable.prop('readonly', true);
          update_zone_button.attr("disabled", true);
        }

      },

    }

  };

  var forwards_table = {

    query: {
      'orderby': 'domain',
      'order': 'asc',
    },

    display: function () {

      jQuery.get(seravo_domains_loc.ajaxurl, {
          'action': 'seravo_ajax_domains',
          'section': 'get_forwards_table',
          'nonce': seravo_domains_loc.ajax_nonce,
          'orderby': forwards_table.query.orderby,
          'order': forwards_table.query.order,
        },

        function (rawData) {
          if (rawData !== '0' && rawData.length > 0) {
            jQuery("#forwards-table-wrapper").html(rawData);
            forwards_table.init();
          } else {
            jQuery("#forwards-table-spinner").html('<b>' + seravo_domains_loc.domains_load_failed + '</b>');
          }
        }
      ).fail(function () {
        jQuery("#forwards-table-spinner").html('<b>' + seravo_domains_loc.domains_load_failed + '</b>');
      });

    },

    init: function () {

      $('#forwards-table-wrapper .toggle-row').click(function(e) {
        e.preventDefault();
        $(this).closest("tr").toggleClass("is-expanded");
      });

      $('#forwards-table-wrapper .manage-column.sortable a, #forwards-table-wrapper .manage-column.sorted a').click(function (e) {
        e.preventDefault();

        var orderby = $(e.target).closest('th').attr('id');
        var order = $(e.target).closest('th').hasClass('desc') ? 'asc' : 'desc';

        $(e.target).closest('a').append('<img class="sort-spinner" src="/wp-admin/images/spinner.gif">');
        forwards_table.sort(orderby, order);
      });

      $('#forwards-table-wrapper span.view a').click(function (e) {
        e.preventDefault();

        $(e.target).closest('.row-actions').find('.action-spinner').remove();
        $(e.target).closest('.row-actions').append('<img class="action-spinner" src="/wp-admin/images/spinner.gif">');

        forwards_table.fetch($(e.target).closest('tr').data('forwards'));
      });

      $('#forwards-table-wrapper span.edit a').click(function (e) {
        e.preventDefault();

        $(e.target).closest('.row-actions').find('.action-spinner').remove();

        var domain = $(e.target).closest('tr').data('forwards');
        var html = '<table class="forward">' + forwards_table.create_edit_table(domain, '', '', 'Create') + '</table>';
        forwards_table.set_forwards($(e.target).closest('tr').data('forwards'), html);
      });

    },

    sort: function (orderby, order) {

      forwards_table.query = {
        orderby: orderby,
        order: order,
      };

      forwards_table.display();

    },

    fetch: function(domain, edit_source = null) {

      if ( edit_source !== null ) {
        edit_source = edit_source.toString();
      }

      jQuery.get(seravo_domains_loc.ajaxurl, {
          'action': 'seravo_ajax_domains',
          'section': 'fetch_forwards',
          'nonce': seravo_domains_loc.ajax_nonce,
          'domain': domain,
        },

        function (rawData) {
          if (rawData !== '0' && rawData.length > 0) {
            var data = JSON.parse(rawData);

            // Check for errors
            if (  data['status'] !== 200 || ! ( 'forwards' in data ) ) {
              forwards_table.set_forwards(domain, data['reason']);
            } else {
              if ( data['forwards'].length ) {
                var html = '<table class="forward">';

                data['forwards'].forEach(function(forward) {

                  if (edit_source === forward['source']) {

                    var destinations = '';
                    forward['destinations'].forEach(function (destination) {
                      destinations += destination + "\n";
                    });

                    html += forwards_table.create_edit_table(domain, forward['source'], destinations, 'Update');

                  } else {

                    var destinations = '<tr><td>';
                    forward['destinations'].forEach(function (destination) {
                      if (destination.length > 37) {
                        destination = destination.substring(0, 37);
                        destination += '...';
                      }
                      destinations += destination + '<br>';
                    });
                    destinations += '</td></tr>';

                    html += '<tr><td><u><b>' + forward['source'] + '@' + domain + '</b></u> =></td></tr>'
                      + '<tr><td class="forward-actions" data-domain="' + domain + '" data-source="' + forward['source'] + '">'
                      + '<a href="#" class="edit" style="margin-right:10px;">Edit</a> '
                      + '<span><a href="#" class="delete" >Delete</a>'
                      + '<img class="delete-spinner hidden" src="/wp-admin/images/spinner.gif">'
                      + '<span></td></tr>'
                      + destinations;

                  }

                });

                html += '</table>';
                forwards_table.set_forwards(domain, html);
              } else {
                forwards_table.set_forwards(domain, seravo_domains_loc.forwards_none);
              }

            }

          } else {
            forwards_table.set_forwards(domain, '<b>' + seravo_domains_loc.forwards_failed + '</b>');
          }

          $("[data-forwards='" + domain + "']").find('.row-actions').find('.action-spinner').remove();
        }
      ).fail(function () {
        $("[data-forwards='" + domain + "']").find('.row-actions').find('.action-spinner').remove();
      });

    },

    create_edit_table(domain, source, destinations, action) {

      return '<tr><td>' + ( source !== '' ? '<hr>' : '') + '<form name="source-edit-' + domain + '"><table><tr><td><u><b>'
        + '<input type="hidden" name="domain" value="' + domain + '"></input>'
        + '<input type="hidden" name="original-source" value="' + source + '"></input>'
        + '<input type="text" name="source" value="' + source + '" placeholder="' + ( source === '' ? 'source' : '') + '" class="source-edit"></input>'
        + '@' + domain + '</b></u> =></td></tr>'
        + '<tr><td><textarea name="destinations" class="destination-edit" placeholder="'
        + ( destinations === '' ? 'target1@example.com' : '' ) + '">' + destinations + '</textarea>'
        + '<p class="edit-message hidden"></p></td></tr>'
        + '<tr><td><input type="submit" class="button source-edit-btn" value="' + action + '"></input>'
        + '<img class="edit-spinner hidden" src="/wp-admin/images/spinner.gif"></td/></tr>'
        + '</table></form>' + ( source !== '' ? '<hr>' : '') + '</td></tr>';

    },

    set_forwards: function(domain, forwards) {

      $("[data-forwards='" + domain + "']").find("[data-colname='Forwards']").html(forwards);

      $('.forward-actions .edit').click(function(e) {
        e.preventDefault();

        var domain = $(e.target).parent().data('domain');
        var source = $(e.target).parent().data('source');

        forwards_table.fetch(domain, source);
      });

      $('.forward-actions .delete').click(function(e) {
        e.preventDefault();

        var domain = $(e.target).closest('td').data('domain');
        var source = $(e.target).closest('td').data('source');

        $(e.target).siblings('.delete-spinner').removeClass('hidden');

        forwards_table.delete_forwards(domain, source);
      });

      $('form[name="source-edit-' + domain + '"] .source-edit-btn').click(function(e) {
        e.preventDefault();

        var $edit_form = $(e.target).closest('form');
        if ( $edit_form.length ) {
          var domain = $edit_form.find('input[name=domain]').val();
          var source = $edit_form.find('input[name=original-source]').val();
          var new_source = $edit_form.find('input[name=source]').val();
          var destinations = $edit_form.find('textarea[name=destinations]').val();

          destinations = destinations.replace(/^\s*[\r\n]/gm, '');
          $edit_form.find('textarea[name=destinations]').val(destinations);

          forwards_table.edit_forward($edit_form, domain, source, new_source, destinations);
        }
      });

    },

    delete_forwards: function(domain, source) {

      jQuery.post(seravo_domains_loc.ajaxurl, {
          'action': 'seravo_ajax_domains',
          'section': 'edit_forward',
          'nonce': seravo_domains_loc.ajax_nonce,
          'domain': domain,
          'old_source': source,
        },

        function (rawData) {
          if (rawData !== '0' && rawData.length > 0) {
            var data = JSON.parse(rawData);

            if ( data['status'] !== 200 || ! ('message' in data) ) {
              forwards_table.set_forwards(domain, seravo_domains_loc.forwards_edit_fail);
            } else {
              forwards_table.fetch(domain);
            }
          } else {
            forwards_table.set_forwards(domain, seravo_domains_loc.forwards_edit_fail);
          }
        });

    },

    edit_forward: function($edit_form, domain, old_source, new_source, destinations) {

      if ( new_source === '' ) {
        $edit_form.find('.edit-message').removeClass('hidden');
        $edit_form.find('.edit-message').html(seravo_domains_loc.forwards_no_source);
        return;
      } else {
        $edit_form.find('.edit-spinner').removeClass('hidden');
        $edit_form.find('.edit-message').addClass('hidden');
      }

      jQuery.post(seravo_domains_loc.ajaxurl, {
          'action': 'seravo_ajax_domains',
          'section': 'edit_forward',
          'nonce': seravo_domains_loc.ajax_nonce,
          'domain': domain,
          'old_source': old_source,
          'new_source': new_source,
          'destinations': destinations,
        },

        function (rawData) {

          if (rawData !== '0' && rawData.length > 0) {
            var data = JSON.parse(rawData);

            if ( data['status'] !== 200 || ! ('message' in data) ) {
              var error = 'reason' in data ? data['reason'] : seravo_domains_loc.forwards_edit_fail;
              $edit_form.find('.edit-message').removeClass('hidden');
              $edit_form.find('.edit-message').html(error)
            } else {
              if ( old_source === '' ) {
                // Creating new forwards
                $edit_form.find('.edit-message').removeClass('hidden');
                $edit_form.find('.edit-message').html(data['message'])
                // Clear the form
                $edit_form.find('input[name=original-source]').val('');
                $edit_form.find('input[name=source]').val('');
                $edit_form.find('textarea[name=destinations]').val('');
              } else {
                 forwards_table.fetch(domain);
              }
            }
          } else {
            forwards_table.set_forwards(domain, seravo_domains_loc.forwards_edit_fail);
          }

          $edit_form.find('.edit-spinner').addClass('hidden');
        });

    },

  };

  domains_table.display();
  forwards_table.display();

});
