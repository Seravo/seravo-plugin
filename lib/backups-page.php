<div class="wrap">

<h1>Backups</h1>

<p>Backups are made automatically every night and preserved for 30 days. The data can be accessed on the server at <code>/data/backups</code>.</p>

<h2>Current backups: <code>wp-backup-status</code></h2>
<p>
<div id="backup_status_loading"><img src="/wp-admin/images/spinner.gif"></div>
<pre id="backup_status"></pre>
</p>

<h2>Create a new backup</h2>

<p>You can also create backups using the command line tool <code>wp-backup</code>. We recommend getting familiar with the command line option accessible via SSH so that recovering a backup is not dependant on if WP-admin works or not.</p>

<p>
<button id="create_backup_button" class="button">Make a new backup </button>
<div id="create_backup_loading"><img class="hidden" src="/wp-admin/images/spinner.gif"></div>
<pre><div id="create_backup"></div></pre>
</p>

<script>
// Generic ajax report loader function
function seravo_load_report(section) {
  jQuery.post(
    ajaxurl,
    { 'action': 'seravo_backups',
      'section': section },
    function(rawData) {
      if (rawData.length == 0) {
        jQuery('#' + section).html('No data returned for section.');
      }

      jQuery('#' + section + '_loading').fadeOut();
      var data = JSON.parse(rawData);
      jQuery('#' + section).append(data.join("\n"));
    }
  ).fail(function() {
    jQuery('#' + section + '_loading').html('Failed to load. Please try again.');
  });
}

// Load on page load
seravo_load_report('backup_status');

// Load when clicked
jQuery('#create_backup_button').click(function(){
  jQuery('#create_backup_loading img').show();
  jQuery('#create_backup_button').hide();
  seravo_load_report('create_backup');
});

</script>

</div>
