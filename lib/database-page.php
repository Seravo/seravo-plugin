<div class="wrap">

<h1><?php _e('Database', 'seravo'); ?> (beta)</h1>

<div class="seravo-section">
<h2><?php _e('Database access', 'seravo'); ?></h2>
  <p><?php printf( __( 'You can find the database credentials by connecting with SSH and running command %s. These credentials can be used to connect to server with SSH tunnel. You can also use web-based Adminer below.', 'seravo' ), '<code>wp-env-list</code>' ); ?></p>
  <p><?php printf( __( 'When you have SSH connection you can use WP-CLI that has powerful database tools for example exports and imports. <a href="%s">Read wp db docs.</a>', 'seravo' ), 'https://developer.wordpress.org/cli/commands/db/' ); ?></p>
</div>

<div class="seravo-section">
<h2><?php _e('Manage database with Adminer', 'seravo'); ?></h2>
<p><?php printf( __( 'Adminer is a simple database management tool like phpMyAdmin. <a href="$s">Learn more about Adminer.</a>', 'seravo' ), 'https://www.adminer.org' ); ?></p>
<p><?php printf( __( 'Find Adminer in production at %1$s and in local development at %2$s.', 'seravo' ), '<code>sitename.com/.seravo/adminer</code>', '<code>adminer.sitename.local</code>' ); ?></p>


<?php

  $adminer_url = '';

  // TODO: test for multisite
  $siteurl = get_site_url();


  if ( 'production' === getenv('WP_ENV') ) {

    // Add trailing slash if missing
    if ( substr($siteurl, -1) !== '/' ) {
      $siteurl .= '/';
    }

    $adminer_url = $siteurl . '.seravo/adminer';

  } else {

    // Add trailing slash if missing
    if ( substr($siteurl, -1) !== '/' ) {
      $siteurl .= '/';
    }

    // Inject subdomain
    $adminer_url = str_replace('//', '//adminer.', $siteurl);

  }

 ?>

<p><a href="<?php echo esc_url($adminer_url); ?>" class="button" target="_blank"><?php _e( 'Open Adminer', 'seravo' ); ?><span aria-hidden="true" class="dashicons dashicons-external"></span></a></p>
</div>

<?php if ( exec( 'which wp' ) && apply_filters('seravo_search_replace', true) ) : ?>
<div class="seravo-section">
<h2><?php _e('Search-replace tool','seravo'); ?></h2>
<p> <?php _e('With this tool you can run wp search-replace. For your own safety, dry-run has to be ran before the actual search-replace', 'seravo'); ?></p>

 <div class="sr-navbar">
    <label for="sr-from"><?php _e('From:', 'seravo'); ?></label> <input type="text" id="sr-from" value="">
    <label for="sr-to"><?php _e('To:', 'seravo'); ?></label> <input type="text" id="sr-to" value="">

    <!-- To add new arbitrary option put it below. Use class optionbox
         Custom options will be overriden upon update -->
  <ul class="optionboxes">
      <li class="sr_option">
        <input type="checkbox" id="skip_backup" class="optionbox">
        <label for="skip_backup"><?php _e('Skip backups', 'seravo'); ?></label>
      </li>
    <?php if ( $GLOBALS['sr_alltables'] ) : ?>
      <li class="sr_option">
         <input type="checkbox" id="all_tables" class="optionbox">
         <label for="all_tables"><?php _e('All tables', 'seravo'); ?></label>
      </li>
    <?php endif; ?>
    <?php if ( $GLOBALS['sr_networkvisibility'] ) : ?>
      <li class="sr_option">
        <input type="checkbox" id="network" class="optionbox">
        <label for="network"><?php _e('Network', 'seravo'); ?></label>
      </li>
    <?php endif; ?>
  </ul>

    <button id="sr-drybutton" class="button sr-button"> <?php _e('Run dry-run', 'seravo'); ?> </button>
    <button id="sr-button" class="button sr-button" disabled> <?php _e('Run wp search-replace', 'seravo'); ?> </button>

 </div>
 <div id="search_replace_loading"><img class="hidden" src="/wp-admin/images/spinner.gif"></div>
 <div id="search_replace_command"></div>
 <table id="search_replace"></table>

<script>

var dryrun_ran=0;
// Modified from the original seravo_load_report to handle tables and tsv's
function seravo_load_sr_report( section, from, to, options ) {
  jQuery.post(
    ajaxurl,
    { 'action': 'seravo_search_replace',
      'section': section,
      'from': from,
      'to': to,
      'options': options },
    function( rawData ) {
      if ( rawData.length === 0 ) {
        jQuery('#' + section).html( 'No data returned for section.' );
      }
      //jQuery('#' + section + '_loading').fadeOut();
      var data = JSON.parse(rawData);

      // Loops through the data array row by row
      jQuery.each( data, function( i, row ){
        var tr = jQuery( '<tr>' );
        // Loops through the row column by column
        jQuery.each( row.split( '\t' ), function( j, col ){
          if (i === 0){
            jQuery( '#search_replace_command' ).append('<code>' + col + '</code>');
          } else if (i === 1){
            jQuery( '<th>' ).html(col).appendTo(tr);
          } else {
            jQuery( '<td>' ).html(col).appendTo(tr);
          }
        })
        jQuery( '#search_replace_loading img' ).fadeOut();
        jQuery( '#search_replace' ).append(tr);
      });
    jQuery('#sr-button').prop('disabled', false);
  }
  ).fail(function() {
    jQuery( '#' + section + '_loading' ).html( 'Failed to load. Please try again.' );
  });
}

// Load when clicked.
jQuery( '.sr-button' ).click(function(){
  jQuery( '#search_replace_loading img' ).fadeIn();
  jQuery( '#search_replace' ).empty();
  jQuery( '#search_replace_command' ).empty();

  var options = {};
  if(jQuery(this).attr('id') === "sr-button"){
    options['dry_run'] = false;
  } else{
    options['dry_run'] = true;
  }
  jQuery.each(jQuery( '.optionbox' ), function( i, option ){
    options[jQuery(option).attr( 'id' )] = jQuery(option).is( ':checked' );
  });
  seravo_load_sr_report( 'search_replace', jQuery( '#sr-from' ).val() , jQuery( '#sr-to' ).val() , options);
});

jQuery( '#all_tables' ).click(function(){
  if( jQuery(this).is( ':checked' ) ){
    jQuery( '#sr-button').prop('disabled', true);
    jQuery( '#skip_backup').prop('checked', false);
  }
});

jQuery( '#sr-from' ).keyup(function( event ){
  jQuery('#sr-button').prop('disabled', true);
  dryrun_ran = 0;
});

jQuery( document ).ready( function(){
  jQuery( '#sr-button').prop('disabled', true);
  jQuery( '#skip_backup').prop('checked', false);
})
</script>
</div>
<?php endif; // end search & replace ?>

<?php if ( exec( 'which wp' ) ) : ?>

<div class="seravo-seciton">
<h2><?php _e( 'Database size', 'seravo' ); ?></h2>
<p>
<div id="seravo_wp_db_info_loading"><img src="/wp-admin/images/spinner.gif"></div>
<pre><div id="seravo_wp_db_info"></div></pre>

</p>

<script>
// Load db info with ajax because it might take a little while
function seravo_load_db_info(section) {
  jQuery.post(
    ajaxurl,
    { 'action': 'seravo_wp_db_info',
      'section': section },
    function(rawData) {
      if (rawData.length == 0) {
        jQuery('#' + section).html('No data returned for section.');
      }

      jQuery('#' + section + '_loading').fadeOut();
      var data = JSON.parse(rawData);
      jQuery('#' + section).append(data);
    }
  ).fail(function() {
    jQuery('#' + section + '_loading').html('Failed to load. Please try again.');
  });
}

// Load on page load
seravo_load_db_info('seravo_wp_db_info');

</script>
</div>

<?php endif; // end database info ?>

</div>
