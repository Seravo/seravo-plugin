<div class="wrap">

<h1>Backups</h1>

<p>Backups are made automatically every night and preserved for 30 days. The data can be accessed on the server at <code>/data/backups</code>.</p>

<h2>Current backups</h2>

<p>
<pre>
$ wp-backup-status
<?php
  exec('wp-backup-status 2>&1', $output);
  foreach ($output as $line) {
    echo $line;
  }
?>
</pre>
</p>

<h2>Create a new backup</h2>

<p>You can also create backups using the command line tool <code>wp-backup</code>. We recommend getting familiar with the command line option accessible via SSH so that recovering a backup is not dependant on if WP-admin works or not.</p>

<p><button id="run_backup" class="button">Make a new backup <img class="hidden" src="/wp-admin/images/loading.gif"></button></p>
<pre><div id="run_backup_output"></div></pre>

<script>
jQuery('#run_backup').click(function(){
  jQuery('#run_backup img').show();
  jQuery('#run_backup').attr('disabled', 'disabled');

  jQuery.post(
    ajaxurl,
    { 'action': 'seravo_backups' },
    function(rawData) {
      if (rawData.length == 0) {
        jQuery('#run_backup_output').html('Backup was started, but did not complete.');
      }

      jQuery("#run_backup img").fadeOut();
      var data = JSON.parse(rawData);
      jQuery('#run_backup_output').append(data.join("\n"));
    }
  ).fail(function() {
    jQuery("#run_backup img").fadeOut();
    jQuery('#run_backup_output').html('Backup failed to run. Please try again.');
  });
})
</script>

</div>
