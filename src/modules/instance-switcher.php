<?php

namespace Seravo\Module;

use \Seravo\API;
use \Seravo\Shadow;
use \Seravo\Helpers;
use \Seravo\Compatibility;

/**
 * Class InstanceSwitcher
 *
 * Enable users to switch to any shadow they have available.
 */
final class InstanceSwitcher {
  use Module;

  /**
   * Check whether the module should be loaded or not.
   * @return bool Whether to load.
   */
  protected function should_load() {
    return ! Helpers::is_development();
  }

  /**
   * Initialize the module. Filters and hooks should be added here.
   * @return void
   */
  protected function init() {
    # Show the red banner only in staging/testing instances
    # Don't show the banner in Vagrant or other local development environments
    # or in update shadows where the red banner would just be annoying.
    if ( \getenv('WP_ENV') === 'staging' ) {
      \add_action('admin_footer', array( __CLASS__, 'render_shadow_indicator' ));
      \add_action('wp_footer', array( __CLASS__, 'render_shadow_indicator' ));
      \add_action('login_footer', array( __CLASS__, 'render_shadow_indicator' ));
      \add_action('admin_notices', array( __CLASS__, 'render_shadow_admin_notice' ));

      // If production domain was given, store it as cookie.
      if ( isset($_GET['seravo_production']) ) {
        $production_domain = $_GET['seravo_production'];
        if ( $production_domain !== 'clear' ) {
          \setcookie('seravo_production', $production_domain, \time() + 43200, '/');
        } else {
          \setcookie('seravo_production', '', 0, '/');
        }
      }
    }

    \add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 999);
    \add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 999);
    \add_action('login_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 999);

    // Check user permission and show instance switcher
    $capability = \apply_filters('seravo_' . self::get_name() . '_capability', 'edit_posts');
    if ( \current_user_can($capability) ) {
      \add_action('admin_bar_menu', array( __CLASS__, 'add_switcher' ), 999);
    }
  }

  /**
   * Load JavaScript and stylesheets for the switcher and the banner.
   * @return void
   */
  public static function enqueue_scripts() {
    if ( \is_user_logged_in() ) {
      // JS and CSS for the switcher
      \wp_enqueue_style('seravo-admin-bar-css');
      \wp_enqueue_script('seravo-admin-bar-js');
    }

    if ( Helpers::is_staging() ) {
      // JS for the banner
      \wp_enqueue_script('seravo-common-js');
    }
  }

  /**
   * Add a instance switcher menu in the WP Admin Bar.
   * @param \WP_Admin_Bar $wp_admin_bar Instance of the admin bar.
   * @return void
   */
  public static function add_switcher( $wp_admin_bar ) {
    // Bail out if there is no WP Admin bar
    if ( ! \function_exists('is_admin_bar_showing') || ! \is_admin_bar_showing() ) {
      return;
    }

    $id = 'instance-switcher';
    $menuclass = '';

    $wp_env = \getenv('WP_ENV');
    if ( $wp_env === false ) {
      // Not Seravo environment
      return;
    }

    // Color the instance switcher red if not in production
    if ( $wp_env !== 'production' ) {
      $menuclass = 'instance-switcher-warning';
    }

    $current_title = \strtoupper($wp_env);
    $current_url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    if ( \strpos($current_url, '?') > -1 ) {
      $current_url .= '&';
    } else {
      $current_url .= '?';
    }

    // Create the parent menu here
    $wp_admin_bar->add_menu(
      array(
        'id'    => $id,
        'title' => '<span class="ab-icon seravo-instance-switcher-icon"></span>' .
          '<span class="ab-label seravo-instance-switcher-text">' . __('Now in', 'seravo') . ': ' . $current_title . '</span>',
        'href'  => isset($_COOKIE['seravo_shadow']) ? $current_url . 'seravo_shadow=' . $_COOKIE['seravo_shadow'] : '#',
        'meta'  => array(
          'class' => $menuclass,
        ),
      )
    );

    $instances = Shadow::load_shadow_list();

    if ( $instances !== false ) {
      // add menu entries for each shadow
      foreach ( $instances as $instance ) {

        $title = '';
        if ( isset($instance['env']['WP_ENV']) ) {
          $title = \strtoupper($instance['env']['WP_ENV']);
        }

        if ( \strlen($instance['info']) > 0 ) {
          $title .= ' (' . $instance['info'] . ')';
        }

        if ( ! isset($instance['domains']) ) {
          $instance['domains'] = array();
        }

        $primary_domain = '';
        foreach ( $instance['domains'] as $domain ) {
          if ( $domain['primary'] === $instance['name'] ) {
            $primary_domain = $domain['domain'];
            break;
          }
        }

        if ( $primary_domain !== null ) {
          $instance_id = Compatibility::substr($instance['name'], -6);

          if ( $instance_id !== false ) {
            $href = $primary_domain === '' ? '#' . $instance_id : 'https://' . $primary_domain;
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
    }

    // If in a shadow, always show exit link
    if ( $wp_env !== 'production' ) {
      $domain = Shadow::get_production_domain();
      $exit_href = $domain === '' ? '#exit' : 'https://' . $domain;

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
        'meta'   => array( 'target' => '_blank' ),
      )
    );
  }

  /**
   * Render front facing big fat red banner. This should
   * always be shown in shadow so the user doesn't forget.
   * @return void
   */
  public static function render_shadow_indicator() {
    $wp_env = \getenv('WP_ENV');
    if ( $wp_env === false ) {
      // Not Seravo environment
      return;
    }

    // In case WP_ENV_COMMENT is empty
    $shadow_title = \strtoupper($wp_env);
    if ( \getenv('WP_ENV_COMMENT') !== false && \getenv('WP_ENV_COMMENT') !== '' ) {
      $shadow_title = \getenv('WP_ENV_COMMENT');
    }

    ?>
    <style>
      #shadow-indicator {
        font-family: Arial, sans-serif;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        color: #fff;
        background: #cc0000;
        z-index: 3000;
        font-size: 16px;
        line-height: 1;
        text-align: center;
        padding: 5px
      }

      #shadow-indicator a.clearlink {
        text-decoration: underline;
        color: #fff;
      }
    </style>

    <div id="shadow-indicator">
      <?php
      $domain = Shadow::get_production_domain();
      $exit_href = $domain === '' ? '#exit' : 'https://' . $domain;
      // translators: $s Identifier for the shadow instance in use
      \printf(__('Your current shadow instance is "%s".', 'seravo'), $shadow_title);
      \printf(' <a class="clearlink shadow-exit" href="%s">%s</a> ', $exit_href, __('Exit', 'seravo'));
      ?>
    </div>
    <?php
  }

  /**
   * Let plugins or themes display admin notice when inside a shadow.
   * @return void
   */
  public static function render_shadow_admin_notice() {
    $current_screen = \get_current_screen();
    $admin_notice_content = \apply_filters('seravo_staging_admin_notice', '', $current_screen);
    if ( $admin_notice_content !== '' ) {
      echo $admin_notice_content;
    }
  }

}
