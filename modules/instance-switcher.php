<?php
/*
 * Plugin name: Instance Switcher
 * Description: Enable users to switch to any shadow they have available
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('InstanceSwitcher') ) {
  class InstanceSwitcher {

    public static function load() {

      # Show the red banner only in staging/testing instances
      # Don't show the banner in Vagrant or other local development environments
      # or in update shadows where the red banner would just be annoying.
      if ( getenv('WP_ENV') && getenv('WP_ENV') === 'staging' ) {
        add_action('admin_footer', array( 'Seravo\InstanceSwitcher', 'render_shadow_indicator' ));
        add_action('wp_footer', array( 'Seravo\InstanceSwitcher', 'render_shadow_indicator' ));
        add_action('login_footer', array( 'Seravo\InstanceSwitcher', 'render_shadow_indicator' ));
        add_action('admin_notices', array( 'Seravo\InstanceSwitcher', 'render_shadow_admin_notice' ));

        if ( isset($_GET['seravo_production']) ) {
            $production_domain = $_GET['seravo_production'];
          if ( $production_domain !== 'clear' ) {
            setcookie('seravo_production', $production_domain, time() + 43200, '/');
          } else {
            setcookie('seravo_production', '', 0, '/');
          }
        }
      }

      // styles and scripts for the switcher and the banner
      add_action('admin_enqueue_scripts', array( 'Seravo\InstanceSwitcher', 'assets' ), 999);
      add_action('wp_enqueue_scripts', array( 'Seravo\InstanceSwitcher', 'assets' ), 999);
      add_action('login_enqueue_scripts', array( 'Seravo\InstanceSwitcher', 'assets' ), 999);

      // Check permission
      if ( ! current_user_can(self::custom_capability()) ) {
        return;
      }

      // admin ajax action
      add_action('wp_ajax_instance_switcher_change_container', array( 'Seravo\InstanceSwitcher', 'change_wp_container' ));
      add_action('wp_ajax_nopriv_instance_switcher_change_container', array( 'Seravo\InstanceSwitcher', 'change_wp_container' ));

      // add the instance switcher menu
      add_action('admin_bar_menu', array( 'Seravo\InstanceSwitcher', 'add_switcher' ), 999);

    }

    /**
    * Make capability filterable
    */
    public static function custom_capability() {
      return apply_filters('seravo_instance_switcher_capability', 'edit_posts');
    }

    /**
    * Load JavaScript and stylesheets for the switcher and the banner
    */
    public static function assets() {
      if ( is_user_logged_in() || Helpers::is_staging() ) {
        wp_enqueue_script('seravo_instance_switcher', plugins_url('../js/instance-switcher.js', __FILE__), array( 'jquery' ), Helpers::seravo_plugin_version(), false);
        wp_enqueue_style('seravo_instance_switcher', plugins_url('../style/instance-switcher.css', __FILE__), null, Helpers::seravo_plugin_version(), 'all');
      }
    }

    /**
    * Automatically load list of shadow instances from Seravo API (if available)
    */
    public static function load_shadow_list() {

      // If not in production, the Seravo API is not accessible and it is not
      // even possible know what shadows exists, so just return an empty list.
      if ( getenv('WP_ENV') !== 'production' ) {
        return false;
      }

      $shadow_list = get_transient('shadow_list');
      if ( ($shadow_list) === false ) {
        $api_query = '/shadows';
        $shadow_list = API::get_site_data($api_query);
        if ( is_wp_error($shadow_list) ) {
          return false; // Exit with empty result and let later flow handle it
          // Don't break page load here or everything would be broken.
        }
        set_transient('shadow_list', $shadow_list, 10 * MINUTE_IN_SECONDS);
      }

      return $shadow_list;
    }

    /**
    * Create the menu itself
    */
    public static function add_switcher( $wp_admin_bar ) {

      // Bail out if there is no WP Admin bar
      if ( ! function_exists('is_admin_bar_showing') || ! is_admin_bar_showing() ) {
        return;
      }

      $id = 'instance-switcher';
      $menuclass = '';

      # Color the instance switcher red if not in production
      if ( getenv('WP_ENV') && getenv('WP_ENV') !== 'production' ) {
        $menuclass = 'instance-switcher-warning';
      }

      $current_title = strtoupper(getenv('WP_ENV'));
      $current_url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
      if ( strpos($current_url, '?') > -1 ) {
        $current_url .= '&';
      } else {
        $current_url .= '?';
      }

      // create the parent menu here
      $wp_admin_bar->add_menu(
        array(
          'id'    => $id,
          'title' => '<span class="ab-icon seravo-instance-switcher-icon"></span>' .
                    '<span class="ab-label seravo-instance-switcher-text">' . __('Now in', 'seravo') . ': ' . $current_title . '</span>',
          'href'  => ! empty($_COOKIE['seravo_shadow']) ? $current_url . 'seravo_shadow=' . $_COOKIE['seravo_shadow'] : '#',
          'meta'  => array(
            'class' => $menuclass,
          ),
        )
      );

      $instances = self::load_shadow_list();

      if ( $instances ) {
        // add menu entries for each shadow
        foreach ( $instances as $key => $instance ) {
          $title = strtoupper($instance['env']);

          if ( strlen($instance['info']) > 0 ) {
            $title .= ' (' . $instance['info'] . ')';
          }

          $primary_domain = '';
            foreach ( $instance['domains'] as $domain ) {
            if ( $domain['primary'] === $instance['name'] ) {
              $primary_domain = $domain['domain'];
              break;
            }
          }

          if ( $primary_domain !== null ) {
            $href = ! empty($primary_domain) ? 'https://' . $primary_domain : '#' . substr($instance['name'], -6);

            $wp_admin_bar->add_menu(
              array(
                'parent' => $id,
                'title'  => $title,
                'id'     => $instance['name'],
                'href'   => $href,
                'meta'   => array(
                  'class' => 'shadow-link',
                ),
              )
            );

          }
        }
      }

      // If in a shadow, always show exit link
      if ( getenv('WP_ENV') && getenv('WP_ENV') !== 'production' ) {
        $domain = self::get_production_domain();
        $exit_href = ! empty($domain) ? 'https://' . $domain : '#exit';

        $wp_admin_bar->add_menu(
          array(
            'parent' => $id,
            'title'  => __('Exit Shadow', 'seravo'),
            'id'     => 'exit-shadow',
            'href'   => $exit_href,
            'meta'   => array(
              'class' => 'shadow-exit',
            ),
          )
        );
      }

      // Last item is always docs link
      $wp_admin_bar->add_menu(
        array(
          'parent' => $id,
          'title'  => __('Shadows explained at Seravo.com/docs', 'seravo'),
          'id'     => 'shadow-info',
          'href'   => 'https://seravo.com/docs/deployment/shadows/',
        )
      );

    }

    /**
    * Front facing big fat red banner
    */
    public static function render_shadow_indicator() {
      $shadow_title = strtoupper(getenv('WP_ENV'));
      if ( getenv('WP_ENV_COMMENT') && ! empty(getenv('WP_ENV_COMMENT')) ) {
        $shadow_title = $shadow_title . ' (' . getenv('WP_ENV_COMMENT') . ')';
      }
      ?>
      <style>#shadow-indicator { font-family: Arial, sans-serif; position: fixed; bottom: 0; left: 0; right: 0; width: 100%; color: #fff; background: #cc0000; z-index: 3000; font-size:16px; line-height: 1; text-align: center; padding: 5px } #shadow-indicator a.clearlink { text-decoration: underline; color: #fff; }</style>
      <div id="shadow-indicator">
        <?php
        $domain = self::get_production_domain();
        $exit_href = ! empty($domain) ? 'https://' . $domain : '#exit';
        // translators: $s Identifier for the shadow instance in use
        printf(__('Your current shadow instance is %s.', 'seravo'), $shadow_title);
        printf(' <a class="clearlink shadow-exit" href="%s">%s</a> ', $exit_href, __('Exit', 'seravo'));
        ?>
      </div>
      <?php
    }

    /**
    * Let plugins or themes display admin notice when inside a shadow
    */
    public static function render_shadow_admin_notice( $current_screen ) {
      $current_screen = get_current_screen();
      $admin_notice_content = apply_filters('seravo_instance_switcher_admin_notice', '', $current_screen);
      if ( ! empty($admin_notice_content) ) {
        echo $admin_notice_content;
      }
    }

    public static function get_production_domain() {
      if ( ! empty($_COOKIE['seravo_shadow']) ) {
        // Seravo_shadow cookie indicates cookie based access, no separate domain
        return '';
      } elseif ( ! empty($_GET['seravo_production']) && $_GET['seravo_production'] !== 'clear' ) {
        // With seravo_production param, shadow uses domain based access
        // Tested before cookie as it may contain newer data
        return $_GET['seravo_production'];
      } elseif ( ! empty($_COOKIE['seravo_production']) ) {
        // With seravo_production cookie, shadow uses domain based access
        return $_COOKIE['seravo_production'];
      } elseif ( $_SERVER['SERVER_NAME'] !== getenv('DEFAULT_DOMAIN') && substr_count($_SERVER['SERVER_NAME'], '.') >= 2 ) {
        // If domain consists of 3 or more parts, remove the downmost
        // Notice that this DOES NOT necessarily work for multilevel TLD (eg. co.uk)
        // Slash at end means that only hostname should be used (no path/query etc)
        // It should be used when redirecting might be needed
        return explode('.', $_SERVER['SERVER_NAME'], 2)[1] . '/';
      }
      // If none of the others work, trust in redirecting
      return getenv('DEFAULT_DOMAIN') . '/';
    }

  }

  InstanceSwitcher::load();
}
