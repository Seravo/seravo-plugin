<div class="wrap">

<h1>Cruft files (beta)</h1>

<h2>Find unnecessary files in the filesystem</h2>
<p>
<div id="cruftfiles_status_loading"><img src="/wp-admin/images/spinner.gif"></div>
<div id="cruftfiles_status"></div>
</p>

<script>
function seravo_ajax_delete_file(filepath, callback) {
  jQuery.post(
    ajaxurl,
    { type: 'POST',
      'action': 'seravo_delete_file',
      'deletefile': filepath },
    function( rawData ) {
      var data = JSON.parse(rawData);
      if ( data.success ) {
        callback();
      }
    });
}

// Generic ajax report loader function
function seravo_load_report(section) {
  jQuery.post(
    ajaxurl,
    { 'action': 'seravo_cruftfiles',
      'section': section },
    function(rawData) {
      if (rawData.length == 0) {
        jQuery('#' + section).html('No data returned for section.');
      }

      jQuery('#' + section + '_loading').fadeOut();
      console.log(rawData);
      var data = JSON.parse(rawData);
      jQuery.each( data, function( i, file){
        if (file != '') {
          jQuery( '#cruftfiles_status' ).append('<div><div class="filepath">' + file + '</div>' + '<button class="deletefile">Delete</button></div>');
        }
      });
      jQuery( '#cruftfiles_status_loading img' ).fadeOut

      jQuery('.deletefile').click(function() {;
        var parent = jQuery(this).parent();
        var filepath = parent.find(">:first-child").html();
        seravo_ajax_delete_file(filepath, function() {
          parent.fadeOut(600, function() {
            jQuery(this).remove();
          });
        });
      });

    }
  ).fail(function() {
    jQuery('#' + section + '_loading').html('Failed to load. Please try again.');
  });
}

// Load on page load
seravo_load_report('cruftfiles_status');

</script>

<p><b>Note! Deleted files cannot be undeleted.</b></p>

</div>
