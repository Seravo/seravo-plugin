<?php

function wpp_report_disk_usage() {
  exec("du -sh /data/* | sort -hr", $output);
  return $output;
}

echo json_encode(wpp_report_disk_usage());
