<?php

exec('wp-backup 2>&1', $output);
echo json_encode($output);
