<h1>HTTP Request Reports</h1>

<p>These monthly reports are generated from the site's HTTP access logs. They show every HTTP request of the site, including traffic from both humans and bots. Requests blocked at the firewall level (for example during a DDOS attack) are not logged.</p>
<p>Log files can be accessed also directly on the server at <code>/data/slog/html/goaccess-*.html</code>.</p>

<ul class="ul-square">
<?php
$reports = glob("/data/slog/html/goaccess-*.html");
$months = array();

if ( empty($reports) ) {
  echo "No reports found at /data/slog/html/. Reports should be available within a month of the creation of a new site.";
} else {
  foreach ($reports as $report) {
    array_push( $months, substr($report, 25, 7) );
  }
}

// List months in reverse order with newest first
rsort($months);

foreach ($months as $month) {
    echo "<li><a href='?report=$month.html' target='_blank'>$month</a></li>";
}

?>
</ul>
