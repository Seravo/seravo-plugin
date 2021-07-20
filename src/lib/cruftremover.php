<?php

namespace Seravo;

/**
 * Class CruftRemover
 *
 * Helper methods for fetching cruft files, unnecessary themes and plugins.
 */
class CruftRemover {

  /**
   * Fetch the file information.
   * @param string $file Path of the file.
   * @return array<string, mixed> Array containing the file information.
   */
  public static function add_file_information( $file ) {
    if ( $file !== Helpers::sanitize_full_path($file) ) {
      return array(
        'size' => 0,
        'mod_date' => null,
        'filename' => $file,
      );
    }

    exec('du ' . $file . ' -h --time', $output);
    $size = explode("\t", $output[0]);

    return array(
      'size' => $size[0],
      'mod_date' => $size[1],
      'filename' => $file,
    );
  }

  /**
   * Find cruft file based on the name.
   * @param string $name Name of the cruft file to find.
   * @return mixed[] Array containing cruft files found under /data/wordpress & /home/.
   */
  public static function find_cruft_file( $name ) {
    $user = getenv('WP_USER');
    exec('find /data/wordpress -name ' . $name, $data_files);
    exec('find /home/' . $user . ' -maxdepth 1 -name ' . $name, $home_files);
    return array_merge($data_files, $home_files);
  }

  /**
   * Find cruft directory based on the name.
   * @param string $name Name of the cruft directory to find.
   * @return mixed[] Array containing cruft directories found under /data/wordpress & /home/.
   */
  public static function find_cruft_dir( $name ) {
    $user = getenv('WP_USER');
    exec('find /data/wordpress -type d -name ' . $name, $data_dirs);
    exec('find /home/' . $user . ' -maxdepth 1 -type d -name ' . $name, $home_dirs);
    return array_merge($data_dirs, $home_dirs);
  }

  /**
   * Check whether the given directory has only whitelisted content.
   * @param string $dir Path to the directory / directory as a string.
   * @param array<string> $wl_files Whitelisted files.
   * @param array<string> $wl_dirs Whitelisted directories.
   * @return bool True when only whitelisted content.
   */
  public static function only_has_whitelisted_content( $dir, $wl_files, $wl_dirs ) {
    exec('find ' . $dir, $content);
    foreach ( $content as $path ) {
      if ( $path !== $dir ) {
        if ( (! in_array($path, $wl_files)) && (! in_array($path, $wl_dirs)) ) {
          // The file was not whitelisted
          return false;
        }
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
    $handle = popen('wp core verify-checksums 2>&1', 'r');

    if ( $handle === false ) {
      return;
    }

    $temp = stream_get_contents($handle);
    pclose($handle);

    if ( $temp === false ) {
      return;
    }

    // Lines beginning with: "Warning: File should not exist: "
    $temp = explode("\n", $temp);
    foreach ( $temp as $line ) {
      if ( strpos($line, 'Warning: File should not exist: ') !== false ) {
        $line = '/data/wordpress/htdocs/wordpress/' . substr($line, 32);
        $output[] = $line;
      }
    }
    return $output;
  }

  /**
   * Remove directory.
   * @param string $dir Path to the directory / directory as a string
   * @param int $recursive Condition variable for the recursion, 0 for no recursion.
   * @return bool|void True when not called recursively, void otherwise.
   */
  public static function rmdir_recursive( $dir, $recursive ) {
    $scan_dir = scandir($dir);
    if ( $scan_dir === false ) {
      return;
    }

    foreach ( $scan_dir as $file ) {
      if ( '.' === $file || '..' === $file ) {
        continue; // Skip current and upper level directories
      }
      if ( is_dir("{$dir}/{$file}") ) {
        self::rmdir_recursive("{$dir}/{$file}", 1);
      } else {
        unlink("{$dir}/{$file}");
      }
    }
    rmdir($dir);
    if ( $recursive === 0 ) {
      return true;
    }
  }

  /**
   * List the cruft files
   * @return array<string, mixed> Found cruft files.
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
      getenv('WP_USER') . '_20*',
    );
    $list_known_files = array(
      '/data/wordpress/htdocs/wp-content/.htaccess',
      '/data/wordpress/htdocs/wp-content/db.php',
      '/data/wordpress/htdocs/wp-content/object-cache.php.off',
      '/data/wordpress/htdocs/wp-content/wp-login.log',
      '/data/wordpress/htdocs/wp-content/adminer.php',
      '/data/wordpress/htdocs/wp-content/advanced-cache.php',
      '/data/wordpress/htdocs/wp-content/._index.php',
      '/data/wordpress/htdocs/wp-content/siteground-migrator.log',
      '/data/wordpress/htdocs/wp-content/ari-adminer-config.php',
    );
    $list_known_dirs = array(
      '/data/wordpress/htdocs/wp-content/plugins/all-in-one-wp-migration/storage',
      '/data/wordpress/htdocs/wp-content/ai1wm-backups',
      '/data/wordpress/htdocs/wp-content/uploads/backupbuddy_backups',
      '/data/wordpress/htdocs/wp-content/updraft',
      '/data/wordpress/htdocs/wp-content/._plugins',
      '/data/wordpress/htdocs/wp-content/._themes',
      '/data/wordpress/htdocs/wp-content/wflogs',
    );
    $white_list_dirs = array(
      '/data/wordpress/htdocs/wp-content/plugins',
      '/data/wordpress/htdocs/wp-content/mu-plugins',
      '/data/wordpress/htdocs/wp-content/themes',
      '/data/wordpress/node_modules',
    );
    $white_list_files = array(
      '/data/wordpress/vagrant-base.sql',
      '/data/wordpress/htdocs/wp-content/plugins/all-in-one-wp-migration/storage/index.php',
      '/data/wordpress/htdocs/wp-content/plugins/all-in-one-wp-migration/storage/index.html',
      '/data/wordpress/htdocs/wp-content/ai1wm-backups/index.html',
      '/data/wordpress/htdocs/wp-content/ai1wm-backups/index.php',
      '/data/wordpress/htdocs/wp-content/ai1wm-backups/.htaccess',
      '/data/wordpress/htdocs/wp-content/ai1wm-backups/web.config',
    );

    $crufts = array();
    $cruft_core = self::find_cruft_core();

    if ( is_array($cruft_core) ) {
      $crufts = array_merge($crufts, $cruft_core);
    }

    foreach ( $list_files as $filename ) {
      $cruft_found = self::find_cruft_file($filename);
      if ( ! empty($cruft_found) ) {
        $crufts = array_merge($crufts, $cruft_found);
      }
    }

    foreach ( $list_dirs as $dirname ) {
      $cruft_found = self::find_cruft_dir($dirname);
      if ( ! empty($cruft_found) ) {
        $crufts = array_merge($crufts, $cruft_found);
      }
    }

      // This should be performed right after cruftfile search and before wp core
    foreach ( $white_list_dirs as $dirname ) {
      // Some directories are whitelisted and their files should not be deleted
      $keep = array();
      foreach ( $crufts as $filename ) {
        if ( strpos($filename, $dirname) !== false ) {
          $keep[] = $filename;
        }
      }
      $crufts = array_diff($crufts, $keep);
    }

    foreach ( $white_list_files as $filename ) {
      // Some files are whitelisted as it is not necessary to delete them
      $keep = array();
      foreach ( $crufts as $cruftname ) {
        if ( strpos($cruftname, $filename) !== false ) {
            $keep[] = $cruftname;
          }
        }
      $crufts = array_diff($crufts, $keep);
    }

    foreach ( $list_known_files as $file ) {
      exec('ls ' . $file, $cruft_found);

      if ( ! empty($cruft_found) ) {
        $crufts = array_merge($crufts, $cruft_found);
      }
    }

    foreach ( $list_known_dirs as $dirname ) {
      exec('ls -d ' . $dirname, $cruft_found);
      if ( ! empty($cruft_found) ) {
        foreach ( $cruft_found as $key => $cruft_dir ) {
          if ( self::only_has_whitelisted_content($cruft_dir, $white_list_files, $white_list_dirs) ) {
            unset($cruft_found[$key]);
          }
        }
        $crufts = array_merge($crufts, $cruft_found);
      }
    }

    $crufts = array_filter(
      $crufts,
      function( $item ) use ( $crufts ) {
        foreach ( $crufts as $substring ) {
          if ( strpos($item, $substring) === 0 && $item !== $substring ) {
            return false;
          }
        }
        return true;
      }
    );
    $crufts = array_unique($crufts);
    set_transient('cruft_files_found', $crufts, 600);

    return array_map(array( __CLASS__, 'add_file_information' ), $crufts);
  }

  /**
   * List the found cruft plugins.
   * @return array<string, mixed> JSON encoded array containing the found cruft plugins.
   */
  public static function list_cruft_plugins() {
    //https://help.seravo.com/en/knowledgebase/19-themes-and-plugins/docs/51-wordpress-plugins-in-seravo-com
    $plugins_list = array(
      'cache_plugins'    => array(                               //Unneeded cache plugins
        'w3-total-cache',
        'wp-super-cache',
        'wp-file-cache',
        'wp-fastest-cache',
        'litespeed-cache',
        'comet-cache',
      ),
      'security_plugins' => array(                            //False sense of security
        'better-wp-security',                                 //iThemes Security aka Better WP Security
        'wordfence',
        'limit-login-attempts-reloaded',
        'wp-limit-login-attempts',
        'wordfence-assistant',
      ),
      'db_plugins'       => array(                                  //Known to mess up your DB
        'broken-link-checker',                               //Broken Link Checker
        'tweet-blender',                                     //Tweet Blender
      ),
      'backup_plugins'   => array(                             //A list of most used backup-plugins
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
      'poor_security'    => array(                             //Known for poor security
        'wp-phpmyadmin-extension',                          //phpMyAdmin
        'ari-adminer',                                      //Adminer
        'sweetcaptcha-revolutionary-free-captcha-service',  //Sweet Captcha
        'wp-cerber',
        'sucuri-scanner',
        'wp-simple-firewall',
      ),
      'bad_code'         => array(                                  //Hard to differentiate from actual malicious
        'wp-client',
        'wp-filebase-pro',
        'miniorange-oauth-client-premium',
      ),
      'foolish_plugins'  => array(                            //Not malicious but do unwanted things
        'all-in-one-wp-migration',
        'video-capture',
        'simple-subscribe',
      ),
    );
    $found_cruft_plugins_categorized = array();
    $found_cruft_plugins = array();

    exec('wp plugin list --fields=name,title,status --format=json --skip-plugins --skip-themes', $output);
    $output = json_decode($output[0]);

    foreach ( $output as $plugin ) {
      foreach ( $plugins_list as $category => $plugin_list ) {
        if ( in_array($plugin->name, $plugin_list) ) {

          if ( isset($found_cruft_plugins_categorized[$category]) ) {
            array_push($found_cruft_plugins_categorized[$category], $plugin->name);
          } else {
            $found_cruft_plugins_categorized[$category] = array( $plugin->name );
          }
          array_push($found_cruft_plugins, $plugin->name);
        }
      }
    }
    //to check if the system has these
    set_transient('cruft_plugins_found', $found_cruft_plugins, 600);

    return $found_cruft_plugins_categorized;
  }

  /**
   * Remove found cruft files.
   * @param array<string> $cruft_entries The cruft files to remove.
   * @return array<string> Possible failed deletions.
   */
  public static function remove_cruft_files( $cruft_entries ) {
    $results = array();
    $files = $cruft_entries;

    if ( is_string($files) ) {
      $files = array( $files );
    }
    if ( ! empty($files) ) {
      foreach ( $files as $file ) {
        $legit_cruft_files = get_transient('cruft_files_found'); // Check first that given file or directory is legitimate
        if ( in_array($file, $legit_cruft_files, true) ) {
          $unlink_result = is_dir($file) ? self::rmdir_recursive($file, 0) : unlink($file);
          // Log files if removing fails
          if ( $unlink_result === false ) {
            array_push($results, $file);
          }
        }
      }
    }

    return $results;
  }

  /**
   * Remove found cruft plugins.
   * @param array<string> $cruft_entries The cruft plugins to remove.
   * @return array<string> Possible failed deletions.
   */
  public static function remove_cruft_plugins( $cruft_entries ) {
    $plugins = $cruft_entries;
    $remove_failed = array();

    if ( is_string($plugins) ) {
      $plugins = array( $plugins );
    }
    if ( ! empty($plugins) ) {
      foreach ( $plugins as $plugin ) {
        $legit_removeable_plugins = get_transient('cruft_plugins_found');

        foreach ( $legit_removeable_plugins as $legit_plugin ) {
          if ( $legit_plugin == $plugin ) {
            $result = exec('wp plugin deactivate ' . $plugin . ' --skip-plugins --skip-themes && wp plugin delete ' . $plugin . ' --skip-plugins --skip-themes', $output);
            // log if plugin remove fails
            if ( $result === false ) {
              array_push($remove_failed, $plugin);
            }
          }
        }
      }
    }

    return $remove_failed;
  }
}


