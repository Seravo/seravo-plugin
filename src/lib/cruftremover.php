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

    \exec('du ' . $file . ' -h --time', $output);
    $size = \explode("\t", $output[0]);

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
      if ( \is_dir("{$dir}/{$file}") ) {
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
      \getenv('WP_USER') . '_20*',
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
    $crufts = \array_unique($crufts);
    \set_transient('cruft_files_found', $crufts, 600);

    return \array_map(array( __CLASS__, 'add_file_information' ), $crufts);
  }
}


