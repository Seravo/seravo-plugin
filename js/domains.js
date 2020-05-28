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

      $(".sort-spinner, .action-spinner").remove();

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

  }

  domains_table.display();

});
