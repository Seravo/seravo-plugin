<?php
/*
 * Plugin name: WP-palvelu Relative urls
 * Plugin URI: https://github.com/Seravo/wp-palvelu-plugin
 * Description: Makes urls in post content relative
 * Author: Onni Hakala / Seravo Oy
 * Version: 1.0
 */

namespace WPPalvelu;

if (!class_exists(__NAMESPACE__.'\\RelativeUrls')) {
  class RelativeUrls {

    // Siteurl cache
    private static $siteurl;

    /*
     * Loads Plugin features
     */
    public static function load() {

      // Populate siteurl for later usage
      self::$siteurl = get_site_url();

      /**
      * Small optimisation:
      * WP_CONTENT_URL is used to move wp-content away from WordPress core
      * If wp-content is moved away wp-content urls are automatically relative
      * And we don't need to do anything
      * Also don't do this if https-domain-alias in in use because overlapping functionality
      */
      if (!defined('HTTPS_DOMAIN_ALIAS_FRONTEND_URL') && defined('WP_CONTENT_URL') && substr(WP_CONTENT_URL, 0, 1) != "/") {
        // Makes post content url relative
        add_filter( 'image_send_to_editor', array(__CLASS__, 'image_url_filter'), 10, 9 );
        add_filter( 'media_send_to_editor', array(__CLASS__, 'media_url_filter'), 10, 3 );

        // Change urls in wp-admin
        //add_action( 'admin_enqueue_scripts', array(__CLASS__, 'enqueue_link_adder_js_fix'), 10, 1 );
      }

      // When using feeds like rss the content should have absolute urls
      // These are quite easy to generate afterwards inside html content
      add_filter( 'the_content_feed', array(__CLASS__, 'content_return_absolute_url_filter'), 10, 1 );

      /**
       * Check post content on save for absolute links
       * To activate this you need to filter: add_filter('wpp_make_content_relative',__return_true);
       */
      if(apply_filters('wpp_make_post_content_relative',false)) {
        add_filter( 'content_save_pre', array(__CLASS__, 'content_url_filter'), 10, 1);
      }
    }

    /**
     * Media gallery images should be handled with relative urls
     */
    public static function image_url_filter( $html, $id, $caption, $title, $align, $url, $size, $alt ) {
      return self::relativize_content_all( $url, $html );
    }
    public static function media_url_filter( $html, $id, $att ) {
      return self::relativize_content_all( $att['url'], $html );
    }

    /**
     * This adds a small javascript fix for the TinyMCE link adder dialog
     */
    public static function enqueue_link_adder_js_fix( $Hook ) {
      if ( 'post.php' === $Hook || 'post-new.php' === $Hook ) {
        // we only need to use this fix in post.php
        wp_enqueue_script( 'link-relative', plugin_dir_url( __FILE__ ) . '../js/link-relative.js' );
      }
    }

    /**
     * Post content should have relative urls for importing db between development and production
     */
    public static function content_url_filter( $content ) {

      /*
       * Integrate with https://github.com/seravo/https-domain-alias
       * When using https-domain-alias the real siteurl is stored in HTTPS_DOMAIN_ALIAS_FRONTEND_URL
       */
      if (defined('HTTPS_DOMAIN_ALIAS_FRONTEND_URL')) {
        $content = self::relativize_content_attributes( HTTPS_DOMAIN_ALIAS_FRONTEND_URL, $content );
      }

      return self::relativize_content_attributes( self::$siteurl, $content );
    }

    /**
     * Post content should have relative urls for importing db between development and production
     */
    public static function content_return_absolute_url_filter( $content ) {

      // This might be issue in really big sites so save results to transient using hash
      $letter_count = count($content);
      $hash = crc32($content);
      $transient_key = "wpp_feed_".$letter_count."_".$hash;

      // Use transient to store the results
      if ( (isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] === 'no-cache') || false === ( $content = get_transient( $key ) ) )   {

        // Again integrate with https://github.com/seravo/https-domain-alias
        $url = (defined('HTTPS_DOMAIN_ALIAS_FRONTEND_URL') ? HTTPS_DOMAIN_ALIAS_FRONTEND_URL : self::$siteurl );

        // Regex replace all relative urls
        $content = self::unrelativize_content( $url, $content );

        // Save to transient
        set_transient( $transient_key, $content, 15 * MINUTE_IN_SECONDS );

      }

      return $content;
    }

    /**
     * Helper: This converts any url to slash-relative
     *
     * NOTE: this applies to external links as well, so be careful with this!
     */
    public static function relativize_content_all( $url, $html ) {
      // links may be scheme agnostic (starting with //)
      // in this case we want to temporarily add a scheme for parse_url to work
      if ( substr( $url, 0, 2 ) === "//" ) {
        $url = 'http:' . $url; // -> is now a full-form url.
        // scheme doesn't matter since it's removed anyways during the next step
      }

      // If url is already relative, do nothing
      if ( substr( $url, 0, 4 ) != "http" ) return $html;

      // Otherwise take the scheme and host part away from the start of the url
      $p = parse_url( $url );
      $root = $p['scheme'] . "://" . $p['host'];
      $html = str_ireplace( $root, '', $html );

      return $html;
    }

    /**
     * Helper: This converts any (href or src) attribute to slash-relative
     *
     * NOTE: this applies to external links as well, so be careful with this!
     */
    public static function relativize_content_attributes( $url, $html ) {
      // If urls already start from root, just return it
      if ( $url[0] == "/" ) return $html;
      // strpos is so fast that so don't bother if check fails
      if (strpos($html,$url) !== false) {


        // Parse url
        $parsed_url = parse_url($url);

        // escape dots in hostname
        $regex_hostname = preg_quote($parsed_url['host'],'/');

        // Search href|src attributes which point to this site
        // for example: href='https://www.example.com/frontpage/'
        $pattern = "/\s(href|src)=(.{0,1})([\\\"\']+)(http[s]{0,1}\:\/\/){0,1}(www\.){0,1}".$regex_hostname."[\/]*/";

        // Replace all absolute urls
        $html = preg_replace($pattern,"$1=$2$3/",$html);
      }
      return $html;
    }

    /**
     * Helper: This converts any relative link back to absolute link
     *
     * NOTE: This is needed for rss and other feeds to work correctly
     */
    public static function unrelativize_content( $siteurl, $html ) {

      // Search and replace relative links like href='/..' and src="/.."
      $pattern = "/(href|src)=([\"\']+)\//";
      return preg_replace($pattern,"$1=$2{$siteurl}/",$html);
    }

  }

  RelativeUrls::load();
}
