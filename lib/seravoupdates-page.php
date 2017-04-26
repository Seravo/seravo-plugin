<?php

	$site = getenv('USER');
	$ch = curl_init('http://localhost:8888/v1/site/' . $site);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"X-Api-Key: " . getenv('SERAVO_API_KEY'),
	));
	
	$response = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$value = json_decode($response, true)['seravo_updates'];
	$checked = "";

	if ($value == true) {
		$checked = 'checked="checked"';
	}

	curl_close($ch);

?>

<div class="wrap">

	<h1>Seravo updates</h1>

	<p>Seravo will keep your WordPress up to date by default. However, you can disable automatic updates if you wish.</p>

	<form name="toggle_seravo_updates" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
		<input type="hidden" name="action" value="toggle_seravo_updates">
		<input id="seravoupdates" name="seravoupdates" type="checkbox" <?php echo $checked; ?> > Allow Seravo to update your site<br><br>
		<input type="submit" value="Save settings">
	</form>
</div>
