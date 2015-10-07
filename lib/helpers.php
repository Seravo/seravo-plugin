<?php
/*
 * Description: Helpers for this plugin and other modules
 */

namespace WPPalvelu;

if (!class_exists('Helpers')) {
  class Helpers {

    // Check if this is vagrant or not
    public static function isDevelopment() {
      return (getenv('WP_ENVIRONMENT') && getenv('WP_ENVIRONMENT') == 'development');
    }

    // Check if this is WP-Palvelu production
    public static function isProduction() {
      return (getenv('WP_ENVIRONMENT') && getenv('WP_ENVIRONMENT') == 'production');
    }

    // Check if this is shadow, there shouldn't be difference between this and production
    // But might be useful in the future
    public static function isShadow() {
      return (getenv('WP_ENVIRONMENT') && getenv('WP_ENVIRONMENT') == 'shadow');
    }

    public static function isPublic() {
      return (get_option('blog_public') == true);
    }
  }
}
