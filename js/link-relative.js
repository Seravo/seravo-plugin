/**
 * This makes the Wordpress TinyMCE editor link adder use relative URLs
 *
 * This is part of the HTTPS Domain Alias plugin by Seravo
 */
(function ($) {
  $(document).on('click', '.query-results li', function(e) {
    // extract the relative pathname
    var urlfix = $('input[type="hidden"]', this).val();
    urlfix = $('<a href="' + urlfix + '">').prop('pathname') + $('<a href="' + urlfix + '">').prop('search');
    // apply fix to the field
    $('#url-field').val(urlfix);
  });
})(jQuery);