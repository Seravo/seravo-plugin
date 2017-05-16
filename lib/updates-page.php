<div class="wrap">

  <h1>Seravo updates</h1>

<?php

$site = getenv('USER');

$ch = curl_init('http://localhost:8888/v1/site/' . $site);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-Api-Key: ' . getenv('SERAVO_API_KEY') ));
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ( curl_error($ch) || $httpcode != 200 ) {
  error_log('SWD API error ' . $httpcode . ': ' . curl_error($ch));
  die('API call failed. Aborting. The error has been logged.');
}

curl_close($ch);

$site_data = json_decode($response, true);
// print_r($site_data);

?>

<h2>Site status</h2>
<ul>
  <li>Site created: <?php echo $site_data['created']; ?></li>
  <li>Latest update attempt: <?php echo $site_data['containers'][0]['update_attempt']; ?></li>
  <li>Latest successful update: <?php echo $site_data['containers'][0]['update_success']; ?></li>
</ul>


<h2>Opt-out form updates by Seravo</h2>
<?php
if ( $site_data['seravo_updates'] == true ) {
  $checked = 'checked="checked"';
} else {
  $checked = '';
}

// @TODO: Submit a nonce with the form if the amount of fields and inputs grows to protect users from XSS attacks.
?>

  <p>Seravo's upkeep service includes that your WordPress site is kept up-to-date with quick security updates and regular tested updates of both WordPress core and plugins. If you want full control of updates yourself, you can opt-out from Seravo updates.</p>

  <form name="toggle_seravo_updates" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
    <?php wp_nonce_field( 'toggle-seravo-updates-on-or-off' ); ?>
    <input type="hidden" name="action" value="toggle_seravo_updates">
    <input id="seravo_updates" name="seravo_updates" type="checkbox" <?php echo $checked; ?>> Seravo updates enabled<br><br>
    <input type="submit" value="Save settings">
  </form>
</div>
