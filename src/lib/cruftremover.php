<?php

namespace Seravo;

use \Seravo\Compatibility;

/**
 * Class CruftRemover
 *
 * Helper methods for fetching cruft files, unnecessary themes and plugins.
 * TODO: Rewrite to use get_plugins etc instead of exec.
 */
class CruftRemover {

  /**
   * Fetch the file information.
   * @param string $file Path of the file.
   * @return array<string, mixed> Array containing the file information.
   */
  public static function add_file_information( $file ) {
    $result = array(
      'size' => '0B',
      'mod_date' => null,
      'filename' => $file,
    );

    if ( $file !== Helpers::sanitize_full_path($file) ) {
      return $result;
    }

    $exec = Compatibility::exec('du ' . $file . ' -h --time', $output, $exit_code);
    if ( $exec === false || $exit_code !== 0 ) {
      return $result;
    }

    $size = \explode("\t", $output[0]);
    if ( count($size) < 2 ) {
      return $result;
    }

    return array(
      'size' => $size[0] . 'B',
      'mod_date' => \explode(' ', $size[1], 2)[0],
      'filename' => $file,
    );
  }

  /**
   * Find cruft file based on the name.
   * @param string $name Name of the cruft file to find.
   * @return mixed[] Array containing cruft files found under /data/wordpress & /home/.
   */
  public static function find_cruft_file( $name ) {
    $user = \getenv('WP_USER');
    \exec('find /data/wordpress -name ' . $name, $data_files);
    \exec('find /home/' . $user . ' -maxdepth 1 -name ' . $name, $home_files);
    return \array_merge($data_files, $home_files);
  }

  /**
   * Find cruft directory based on the name.
   * @param string $name Name of the cruft directory to find.
   * @return mixed[] Array containing cruft directories found under /data/wordpress & /home/.
   */
  public static function find_cruft_dir( $name ) {
    $user = \getenv('WP_USER');
    \exec('find /data/wordpress -type d -name ' . $name, $data_dirs);
    \exec('find /home/' . $user . ' -maxdepth 1 -type d -name ' . $name, $home_dirs);
    return \array_merge($data_dirs, $home_dirs);
  }

  /**
   * Check whether the given directory has only whitelisted content.
   * @param string        $dir Path to the directory / directory as a string.
   * @param array<string> $wl_files Whitelisted files.
   * @param array<string> $wl_dirs Whitelisted directories.
   * @return bool True when only whitelisted content.
   */
  public static function only_has_whitelisted_content( $dir, $wl_files, $wl_dirs ) {
    \exec('find ' . $dir, $content);
    foreach ( $content as $path ) {
      if ( $path !== $dir && ((! \in_array($path, $wl_files, true)) && (! \in_array($path, $wl_dirs, true))) ) {
        // The file was not whitelisted
        return false;
      }
    }
    return true;
  }

  /**
   * Verify the wp core checksums.
   * @return string[]|void Output empty array or found cruft core from the verify.
   */
  public static function find_cruft_core() {
    $output = array();
    $handle = \popen('wp core verify-checksums 2>&1', 'r');

    if ( $handle === false ) {
      return;
    }

    $temp = \stream_get_contents($handle);
    \pclose($handle);

    if ( $temp === false ) {
      return;
    }

    // Lines beginning with: "Warning: File should not exist: "
    $temp = \explode("\n", $temp);
    foreach ( $temp as $line ) {
      if ( \strpos($line, 'Warning: File should not exist: ') !== false ) {
        $line = '/data/wordpress/htdocs/wordpress/' . \substr($line, 32);
        $output[] = $line;
      }
    }
    return $output;
  }

  /**
   * Remove directory.
   * @param string $dir       Path to the directory / directory as a string
   * @param int    $recursive Condition variable for the recursion, 0 for no recursion.
   * @return bool|void True when not called recursively, void otherwise.
   */
  public static function rmdir_recursive( $dir, $recursive ) {
    $scan_dir = \scandir($dir);
    if ( $scan_dir === false ) {
      return;
    }

    foreach ( $scan_dir as $file ) {
      if ( '.' === $file || '..' === $file ) {
        continue; // Skip current and upper level directories
      }
      if ( \is_link("{$dir}/{$file}") ) {
        \unlink("{$dir}/{$file}"); // Remove link and prevent directory traversal
      }
      elseif ( \is_dir("{$dir}/{$file}") ) {
        self::rmdir_recursive("{$dir}/{$file}", 1);
      } else {
        \unlink("{$dir}/{$file}");
      }
    }
    \rmdir($dir);
    if ( $recursive === 0 ) {
      return true;
    }
  }

  /**
   * List the cruft files
   * @return array<int, array<string, mixed>> Found cruft files.
   */
  public static function list_cruft_files() {
    // List of known tybes of cruft files
    $list_files = array(
      '*.sql',
      '.hhvm.hhbc',
      '*.wpress',
      'core',
      '*.bak',
      '*deleteme*',
      '*.deactivate',
      '.DS_Store',
      '*.tmp',
      '*.old',
    );
    // List of known cruft directories
    $list_dirs = array(
      'siirto',
      '*palautus*',
      'before*',
      'vanha',
      '*.old',
      '*-old',
      '*-OLD',
      '*-copy',
      '*-2',
      '*.bak',
      'migration',
      '*_BAK',
      '_mu-plugins',
      '*.orig',
      '-backup',
      '*.backup',
      '*deleteme*',
      \getenv('WP_USER') . '_20*',
    );
    $list_known_files = array(
      WP_CONTENT_DIR . '/.htaccess',
      WP_CONTENT_DIR . '/db.php',
      WP_CONTENT_DIR . '/object-cache.php.off',
      WP_CONTENT_DIR . '/wp-login.log',
      WP_CONTENT_DIR . '/adminer.php',
      WP_CONTENT_DIR . '/advanced-cache.php',
      WP_CONTENT_DIR . '/._index.php',
      WP_CONTENT_DIR . '/siteground-migrator.log',
      WP_CONTENT_DIR . '/ari-adminer-config.php',
    );
    $list_known_dirs = array(
      WP_CONTENT_DIR . '/plugins/all-in-one-wp-migration/storage',
      WP_CONTENT_DIR . '/ai1wm-backups',
      WP_CONTENT_DIR . '/uploads/backupbuddy_backups',
      WP_CONTENT_DIR . '/updraft',
      WP_CONTENT_DIR . '/._plugins',
      WP_CONTENT_DIR . '/._themes',
      WP_CONTENT_DIR . '/wflogs',
    );
    $white_list_dirs = array(
      WP_CONTENT_DIR . '/plugins',
      WP_CONTENT_DIR . '/mu-plugins',
      WP_CONTENT_DIR . '/themes',
      '/data/wordpress/node_modules',
    );
    $white_list_files = array(
      '/data/wordpress/vagrant-base.sql',
      WP_CONTENT_DIR . '/plugins/all-in-one-wp-migration/storage/index.php',
      WP_CONTENT_DIR . '/plugins/all-in-one-wp-migration/storage/index.html',
      WP_CONTENT_DIR . '/ai1wm-backups/index.html',
      WP_CONTENT_DIR . '/ai1wm-backups/index.php',
      WP_CONTENT_DIR . '/ai1wm-backups/.htaccess',
      WP_CONTENT_DIR . '/ai1wm-backups/web.config',
    );

    $crufts = array();
    $cruft_core = self::find_cruft_core();

    if ( \is_array($cruft_core) ) {
      $crufts = \array_merge($crufts, $cruft_core);
    }

    foreach ( $list_files as $filename ) {
      $cruft_found = self::find_cruft_file($filename);
      if ( $cruft_found !== array() ) {
        $crufts = \array_merge($crufts, $cruft_found);
      }
    }

    foreach ( $list_dirs as $dirname ) {
      $cruft_found = self::find_cruft_dir($dirname);
      if ( $cruft_found !== array() ) {
        $crufts = \array_merge($crufts, $cruft_found);
      }
    }

      // This should be performed right after cruftfile search and before wp core
    foreach ( $white_list_dirs as $dirname ) {
      // Some directories are whitelisted and their files should not be deleted
      $keep = array();
      foreach ( $crufts as $filename ) {
        if ( \strpos($filename, $dirname) !== false ) {
          $keep[] = $filename;
        }
      }
      $crufts = \array_diff($crufts, $keep);
    }

    foreach ( $white_list_files as $filename ) {
      // Some files are whitelisted as it is not necessary to delete them
      $keep = array();
      foreach ( $crufts as $cruftname ) {
        if ( \strpos($cruftname, $filename) !== false ) {
            $keep[] = $cruftname;
          }
        }
      $crufts = \array_diff($crufts, $keep);
    }

    foreach ( $list_known_files as $file ) {
      \exec('ls ' . $file, $cruft_found);

      if ( $cruft_found !== array() ) {
        $crufts = \array_merge($crufts, $cruft_found);
      }
    }

    foreach ( $list_known_dirs as $dirname ) {
      \exec('ls -d ' . $dirname, $cruft_found);
      if ( $cruft_found !== array() ) {
        foreach ( $cruft_found as $key => $cruft_dir ) {
          if ( self::only_has_whitelisted_content($cruft_dir, $white_list_files, $white_list_dirs) ) {
            unset($cruft_found[$key]);
          }
        }
        $crufts = \array_merge($crufts, $cruft_found);
      }
    }

    $crufts = \array_filter(
      $crufts,
      function( $item ) use ( $crufts ) {
        foreach ( $crufts as $substring ) {
          if ( \strpos($item, $substring) === 0 && $item !== $substring ) {
            return false;
          }
        }
        return true;
      }
    );
    $crufts = \array_values(\array_unique($crufts));
    \set_transient('cruft_files_found', $crufts, 600);

    return \array_map(array( __CLASS__, 'add_file_information' ), $crufts);
  }

  /**
   * List the found cruft plugins.
   * @return mixed[] Array containing the found cruft plugins by category.
   */
  public static function list_cruft_plugins() {
    //https://help.seravo.com/en/knowledgebase/19-themes-and-plugins/docs/51-wordpress-plugins-in-seravo-com
    $plugins_list = array(
      //Unneeded cache plugins
      'cache_plugins'    => array(
        'title' => __('Unnecessary Cache Plugins:', 'seravo'),
        'description' => __('Your website is running on a server that does takes care of caching automatically. Any additional plugins that do caching will not improve the service.', 'seravo'),
        'plugins' => array(
          'w3-total-cache',
          'wp-super-cache',
          'wp-file-cache',
          'wp-fastest-cache',
          'litespeed-cache',
          'comet-cache',
        ),
      ),
      //False sense of security
      'security_plugins' => array(
        'title' => __('Unnecessary Security Plugins:', 'seravo'),
        'description' => __('Your website runs on a server that is designed to provide a high level of security. Any plugins providing additional security measures will likely just slow down your website.', 'seravo'),
        'plugins' => array(
          'better-wp-security',
          'wordfence',
          'limit-login-attempts-reloaded',
          'wp-limit-login-attempts',
          'wordfence-assistant',
        ),
      ),
      // Known to mess up your DB
      'db_plugins' => array(
        'title' => __('Unnecessary Database Manipulation Plugins:', 'seravo'),
        'description' => __('These plugins may cause issues with your database.', 'seravo'),
        'plugins' => array(
          'broken-link-checker',
          'tweet-blender',
        ),
      ),
      // A list of most used backup-plugins
      'backup_plugins' => array(
        'title' => __('Unnecessary Backup Plugins:', 'seravo'),
        'description' => __('Backups of your website are automatically run on the server on a daily basis. Any plugins creating additional backups are redundant and will unnecessesarily fill up your data storage space.', 'seravo'),
        'plugins' => array(
          'updraftplus',
          'backwpup',
          'jetpack',
          'duplicator',
          'backup',
          'all-in-one-wp-migration',
          'dropbox-backup',
          'wp-db-backup',
          'really-simple-ssl',
          'xcloner-backup-and-restore',
        ),
      ),
      // Known for poor security
      'poor_security' => array(
        'title' => __('Unsecure Plugins:', 'seravo'),
        'description' => __('These plugins have known issues with security.', 'seravo'),
        'plugins' => array(
          'wp-phpmyadmin-extension',
          'ari-adminer',
          'sweetcaptcha-revolutionary-free-captcha-service',
          'wp-cerber',
          'sucuri-scanner',
          'wp-simple-firewall',
        ),
      ),
      // Hard to differentiate from actual malicious
      'bad_code' => array(
        'title' => __('Bad Code:', 'seravo'),
        'description' => __('These plugins code are hard to differentiate from actual malicious codes.', 'seravo'),
        'plugins' => array(
          'wp-client',
          'wp-filebase-pro',
          'miniorange-oauth-client-premium',
        ),
      ),
      // Not malicious but do unwanted things
      'foolish_plugins'  => array(
        'title' => __('Foolish Plugins:', 'seravo'),
        'description'=> __('These plugins are known to do foolish things.', 'seravo'),
        'plugins' => array(
          'video-capture',
          'simple-subscribe',
        ),
      ),
    );

    $found_cruft_plugins_categorized = array();
    $found_cruft_plugins = array();

    $result = Compatibility::exec('wp plugin list --fields=name,title,status --format=json --skip-plugins --skip-themes', $output);
    if ( $result === false || ! isset($output[0]) ) {
      return array();
    }

    $output = \json_decode($output[0]);
    if ( $output === null ) {
      return array();
    }

    foreach ( $output as $plugin ) {
      foreach ( $plugins_list as $category => $category_details ) {
        if ( \in_array($plugin->name, $category_details['plugins'], true) ) {

          if ( isset($found_cruft_plugins_categorized[$category]) ) {
            $found_cruft_plugins_categorized[$category][] = $plugin->name;
          } else {
            $found_cruft_plugins_categorized[$category] = array( $plugin->name );
          }
          $found_cruft_plugins[] = $plugin->name;
        }
      }
    }

    // To check if the system has these
    \set_transient('cruft_plugins_found', $found_cruft_plugins, 600);

    $result = array();
    foreach ( $found_cruft_plugins_categorized as $category => $plugins ) {
      $result[] = array(
        'category' => $category,
        'title' => $plugins_list[$category]['title'],
        'description' => $plugins_list[$category]['description'],
        'cruft' => $plugins,
      );
    }

    return $result;
  }

  /**
   * List inactive themes. Themes with installed child themes are ignored.
   * @return string[] Array of cruft themes.
   */
  public static function list_cruft_themes() {
    if ( \is_multisite() ) {
      if ( \wp_is_large_network() ) {
        // Can't get the needed data for large network (1000+ sites)
        \delete_transient('cruft_themes_found');
        return array();
      }

      // Gets all active themes across the sites
      $active_themes = array();
      $sites = \get_sites();
      foreach ( $sites as $site ) {
        \switch_to_blog($site->blog_id);
        $theme = \wp_get_theme();
        if ( ! \in_array($theme->get_stylesheet(), $active_themes, true) ) {
          $active_themes[] = $theme->get_stylesheet();
        }
        \restore_current_blog();
      }
    } else {
      $active_themes = array( \wp_get_theme()->get_stylesheet() );
    }

    // Get an array of WP_Theme -objects
    $all_themes = array();
    foreach ( \wp_get_themes() as $theme ) {
      $all_themes[$theme->get_stylesheet()] = array(
        'name'   => $theme->get_stylesheet(),
        'title'  => $theme->get('Name'),
        'parent' => $theme->get('Template'),
        'active' => (\in_array($theme->get_stylesheet(), $active_themes, true)),
      );
    }

    // Get child themes for all themes
    $children = array();
    foreach ( $all_themes as $theme ) {
      if ( \is_string($theme['parent']) && $theme['parent'] !== '' ) {
        // This theme has a parent
        if ( isset($children[$theme['parent']]) ) {
          $children[$theme['parent']][] = $theme['name'];
        } else {
          $children[$theme['parent']] = array( $theme['name'] );
        }
      }
    }

    // Only return inactive themes without child themes
    $output = array();
    foreach ( $all_themes as $theme ) {
      if ( ! $theme['active'] && ! isset($children[$theme['name']]) ) {
        $output[] = $theme['name'];
      }
    }

    \set_transient('cruft_themes_found', $output, 600);

    return $output;
  }

  /**
   * Remove found cruft files.
   * @param array<string>|string $cruft_entries The cruft files to remove.
   * @return array<string> Possible failed deletions.
   */
  public static function remove_cruft_files( $cruft_entries ) {
    $remove_failed = array();
    $files = $cruft_entries;

    if ( \is_string($files) ) {
      $files = array( $files );
    }

    $legit_cruft_files = \get_transient('cruft_files_found');
    if ( $legit_cruft_files === false || ! is_array($legit_cruft_files) ) {
      // None of the $files were deleted
      return $files;
    }

    foreach ( $files as $file ) {
      if ( \in_array($file, $legit_cruft_files, true) ) {
        // prevent directory traversal in case of links
        if ( is_dir($file) && !is_link($file) ) {
          $unlink_result = self::rmdir_recursive($file, 0);
        }
        else {
          $unlink_result = unlink($file);
        }
        if ( $unlink_result === false ) {
          $remove_failed[] = $file;
        }
      }
    }

    return $remove_failed;
  }

  /**
   * Remove found cruft plugins.
   * @param array<string>|string $cruft_entries The cruft plugins to remove.
   * @return array<string> Possible failed deletions.
   */
  public static function remove_cruft_plugins( $cruft_entries ) {
    $remove_failed = array();
    $plugins = $cruft_entries;

    if ( \is_string($plugins) ) {
      $plugins = array( $plugins );
    }

    $legit_cruft_plugins = \get_transient('cruft_plugins_found');
    if ( $legit_cruft_plugins === false || ! is_array($legit_cruft_plugins) ) {
      // None of the $files were deleted
      return $plugins;
    }

    foreach ( $plugins as $plugin ) {
      if ( \in_array($plugin, $legit_cruft_plugins, true) ) {
        $result = Compatibility::exec('wp plugin deactivate ' . $plugin . ' --skip-plugins --skip-themes && wp plugin delete ' . $plugin . ' --skip-plugins --skip-themes', $output);
        if ( $result === false || strpos($result, 'Success') === false ) {
          $remove_failed[] = $plugin;
        }
      }
    }

    return $remove_failed;
  }

  /**
   * Remove found cruft themes.
   * @param array<string>|string $cruft_entries The cruft themes to remove.
   * @return array<string> Possible failed deletions.
   */
  public static function remove_cruft_themes( $cruft_entries ) {
    $remove_failed = array();
    $themes = $cruft_entries;

    if ( \is_string($themes) ) {
      $themes = array( $themes );
    }

    $legit_cruft_themes = \get_transient('cruft_themes_found');
    if ( $legit_cruft_themes === false || ! is_array($legit_cruft_themes) ) {
      // None of the $themes were deleted
      return $themes;
    }

    foreach ( $themes as $theme ) {
      if ( \in_array($theme, $legit_cruft_themes, true) ) {
        if ( \delete_theme($theme) !== true ) {
          // Removal failed
          $remove_failed[] = $theme;
        }
      }
    }

    return $remove_failed;
  }

}
