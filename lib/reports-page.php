<h1>HTTP Request Reports</h1>

<p>These monthly reports are generated from the site's HTTP access logs. They show every HTTP request of the site, including traffic from both humans and bots. Requests blocked at the firewall level (for example during a DDOS attack) are not logged.</p>
<p>Log files can be accessed also directly on the server at <code>/data/slog/html/goaccess-*.html</code>.</p>

<table class="wp-list-table widefat fixed striped" style="width: 35em;">
  <thead>
    <tr>
      <th style="width: 5em;">Month</th>
      <th style="width: 25em;">HTTP requests</th>
    </tr>
  </thead>
  <tbody>
<?php
$reports = glob("/data/slog/html/goaccess-*.html");

// Create array of months with total request sums
$months = array();

// Track max request value to calculate relative bar widths
$max_requests = 0;

if ( empty($reports) ) {
  echo "No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.";
} else {
  foreach ($reports as $report) {
    $total_requests_string = exec("grep -oE 'total_requests\": ([0-9]+),' $report");
    preg_match('/([0-9]+)/', $total_requests_string, $total_requests_match);
    $total_requests = intval($total_requests_match[1]);

    if ($total_requests > $max_requests) {
      $max_requests = $total_requests;
    }

    array_push( $months, array(
      'date' => substr($report, 25, 7),
      'requests' => $total_requests
    ));
  }
}

// List months in reverse order with newest first
rsort($months);

foreach ($months as $month) {

  $bar_size = $month['requests'] / $max_requests * 300;

  echo "<tr>".
         "<td><a href='?report=". $month['date'] .".html' target='_blank'>". $month['date'] ."</a></td>".
         "<td><div style='background: #44A1CB; color: #fff; padding: 3px; width:". $bar_size ."px;'>". $month['requests'] ."</div></td>".
       "</tr>";
}

?>
  </tbody>
</table>
