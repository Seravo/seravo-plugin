<div class="wrap">

<h1><?php _e('Reports', 'seravo'); ?></h1>

<h2><?php _e('HTTP request statistics', 'seravo'); ?></h2>

<p><?php _e('These monthly reports are generated from the site\'s HTTP access logs. They show every HTTP request of the site, including traffic from both humans and bots. Requests blocked at the firewall level (for example during a DDOS attack) are not logged. Log files can be accessed also directly on the server at <code>/data/slog/html/goaccess-*.html</code>.', 'seravo'); ?></p>

<table class="wp-list-table widefat fixed striped" style="width: 35em;">
  <thead>
    <tr>
      <th style="width: 5em;"><?php _e('Month', 'seravo'); ?></th>
      <th style="width: 25em;"><?php _e('HTTP requests', 'seravo'); ?></th>
      <th style="width: 8em;"><?php _e('Report', 'seravo'); ?></th>
    </tr>
  </thead>
  <tbody>
<?php
$reports = glob('/data/slog/html/goaccess-*.html');

// Create array of months with total request sums
$months = array();

// Track max request value to calculate relative bar widths
$max_requests = 0;

if ( empty($reports) ) {

  echo '<tr><td colspan=3>' . __('No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.', 'seravo') . '</td></tr>';

} else {
  foreach ( $reports as $report ) {
    $total_requests_string = exec("grep -oE 'total_requests\": ([0-9]+),' $report");
    preg_match('/([0-9]+)/', $total_requests_string, $total_requests_match);
    $total_requests = intval($total_requests_match[1]);

    if ( $total_requests > $max_requests ) {
      $max_requests = $total_requests;
    }

    array_push( $months, array(
        'date' => substr($report, 25, 7),
        'requests' => $total_requests,
    ));
  }
}

// List months in reverse order with newest first
rsort($months);

foreach ( $months as $month ) {

  $bar_size = intval( $month['requests'] / $max_requests * 300 );
  if ( $bar_size < 40 ) {
    $bar_css = 'auto';
  } else {
    $bar_css = $bar_size . 'px';
  }

  echo '<tr>' .
         "<td><a href='?report=" . $month['date'] . ".html' target='_blank'>" . $month['date'] . '</a></td>' .
         "<td><div style='background: #44A1CB; color: #fff; padding: 3px; width: " . $bar_css . "; display: inline-block;'>" . $month['requests'] . '</div></td>' .
         "<td><a href='?report=" . $month['date'] . ".html' target='_blank' class='button'>" . __('View report', 'seravo') . '</a></td>' .
       '</tr>';
}

?>
  </tbody>
</table>


<h2><?php _e('Disk usage', 'seravo'); ?></h2>

<p><?php _e('Total size of <code>/data</code> is', 'seravo'); ?>
  <div id="total_disk_usage_loading"><img src="/wp-admin/images/spinner.gif"></div>
  <pre id="total_disk_usage"></pre>
</p>

<p><?php _e('Biggest directories:', 'seravo'); ?>
  <div id="disk_usage_loading"><img src="/wp-admin/images/spinner.gif"></div>
  <pre id="disk_usage"></pre>
</p>


<h2>Data integrity</h2>

<h3>WordPress core</h3>

<div id="wp_core_verify_loading"><img src="/wp-admin/images/spinner.gif"></div>
<pre id="wp_core_verify"></pre>

<h3>Git</h3>

<div id="git_status_loading"><img src="/wp-admin/images/spinner.gif"></div>
<pre id="git_status"></pre>


<h2>Cache status</h2>

<h3>Redis transient and object cache</h3>

<div id="redis_info_loading"><img src="/wp-admin/images/spinner.gif"></div>
<pre id="redis_info"></pre>

<h3>Nginx HTTP cache</h3>

<div id="front_cache_status_loading"><img src="/wp-admin/images/spinner.gif"></div>
<pre id="front_cache_status"></pre>

<script>
// Generic ajax report loader function
function seravo_load_report(section) {
  jQuery.post(
    ajaxurl,
    { 'action': 'seravo_reports',
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

seravo_load_report('total_disk_usage');
seravo_load_report('disk_usage');
seravo_load_report('wp_core_verify');
seravo_load_report('git_status');
seravo_load_report('redis_info');
seravo_load_report('front_cache_status');
</script>

</div>
