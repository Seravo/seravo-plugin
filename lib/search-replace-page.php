<div class="wrap">

 <h1><?php _e('Search-replace tool','seravo'); ?> (beta)</h1>
 <p> <?php _e('With this tool you can run wp search-replace. For your own safety, dry-run has to be ran before the actual search-replace', 'seravo'); ?></p>

 <div class="sr-navbar">
    <?php _e('From:', 'seravo'); ?> <input type="text" id="sr-from" value="">
    <?php _e('To:', 'seravo'); ?> <input type="text" id="sr-to" value="">

    <!-- To add new arbitrary option put it below. Use class optionbox
         Custom options will be overriden upon update -->
  <ul class="optionboxes">
    <li class="sr_option"><?php _e('Skip backups', 'seravo'); ?>
     <input type="checkbox" id="skip_backup" class="optionbox"></li>
    <li class="sr_option"><?php _e('All tables', 'seravo'); ?>
     <input type="checkbox" id="all_tables" class="optionbox"></li>
    <?php if( $GLOBALS['sr_networkvisibility'] ) : ?>
      <li class="sr_option"><?php _e('Network', 'seravo'); ?>
       <input type="checkbox" id="network" class="optionbox"></li>
  <?php endif; ?>
  </ul>

    <button id="sr-drybutton" class="button"> <?php _e('Run dry-run', 'seravo'); ?> </button>
    <button id="sr-button" class="button" disabled> <?php _e('Run wp search-replace', 'seravo'); ?> </button>

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
            jQuery( '#search_replace_command' ).append(col);
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
jQuery( '.button' ).click(function(){
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
