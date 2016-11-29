<?php

function wpp_report_disk_usage() {
  exec("du -sh /data/* | sort -hr", $output);
  return $output;
}

function wpp_report_wp_core_verify() {
  exec("wp core verify-checksums", $output);
  array_unshift($output, '$ wp core verify-checksums');
  return $output;
}

function wpp_report_git_status() {
  exec("git -C /data/wordpress status", $output);

  if ( empty($output) ) {
    return ['Git is not used on this site. To start using it, read our documentation for WordPres developers at <a href="https://seravo.com/docs/">seravo.com/docs</a>.'];
  }

  array_unshift($output, '$ git status');
  return $output;
}

function wpp_report_redis_info() {
  exec("redis-cli info | grep -e keyspace -e db0", $output);
  return $output;
}

function wpp_report_front_cache_status() {
  exec("curl -ILk ". get_site_url(), $output);
  array_unshift($output, '$ curl -IL '. get_site_url());

  if ( preg_match('/X-Proxy-Cache: ([A-Z]+)/', implode("\n", $output), $matches) ) {

    switch ($matches[1]) {
      case 'HIT':
      case 'EXPIRED':
        $result = 'Front cache is working correctly.';
        break;

      case 'MISS':
        $result = 'Front page is not cached due to cookies or expiry headers emitted from the site.';
        break;

      default:
        $result = 'Unable to detect front cache status.';
        break;
    }

  } else {
    $result = 'No front cache available in this WordPress instance.';
  }

  array_unshift($output, $result, '');

  return $output;
}

switch ($_REQUEST['section']) {
  case 'disk_usage':
    echo json_encode(wpp_report_disk_usage());
    break;

  case 'wp_core_verify':
    echo json_encode(wpp_report_wp_core_verify());
    break;

  case 'git_status':
    echo json_encode(wpp_report_git_status());
    break;

  case 'redis_info':
    echo json_encode(wpp_report_redis_info());
    break;

  case 'front_cache_status':
    echo json_encode(wpp_report_front_cache_status());
    break;

  default:
    error_log("ERROR: Section ". $_REQUEST['section'] ." not defined");
    break;
}
