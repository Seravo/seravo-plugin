<?php
/**
 * Plugin name: Crawl delay
 * Description: Slow down crawlers to alleviate site load on heavy crawler access
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('CrawlDelay') ) {
  class CrawlDelay {

    public static function load() {

      add_filter( 'robots_txt', array( __CLASS__, 'crawl_delay' ), 10, 2 );

    }

    public static function crawl_delay( $output, $public ) {
      if ( '0' === $public ) {
        // bail early if blog is not public
        return $output;
      }

	  $output = "";

      $output .= "User-agent: Googlebot\n";
	  $output .= "Crawl-Delay: 1\n";

	  $output .= "User-agent: AhrefsBot\n";
      $output .= "Crawl-Delay: 1\n";

      $output .= "User-agent: SemrushBot\n";
	  $output .= "Crawl-Delay: 1\n";

      $output .= "User-agent: DotBot\n";
	  $output .= "Crawl-Delay: 1\n";

      return $output;
    }

  }

  CrawlerDelay::load();
}
