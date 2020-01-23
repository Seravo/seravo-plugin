<?php
// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

function add_file_information( $file ) {
  exec('du ' . $file . ' -h --time', $output);
  $size = explode("\t", $output[0]);

  $data['size'] = $size[0];
  $data['mod_date'] = $size[1];
  $data['filename'] = $file;
  return $data;

}

function find_cruft_file( $name ) {
  $user = getenv('WP_USER');
  exec('find /data/wordpress -name ' . $name, $data_files);
  exec('find /home/' . $user . ' -maxdepth 1 -name ' . $name, $home_files);
  $files = array_merge($data_files, $home_files);
  return $files;
}

function find_cruft_dir( $name ) {
  $user = getenv('WP_USER');
  exec('find /data/wordpress -type d -name ' . $name, $data_dirs);
  exec('find /home/' . $user . ' -maxdepth 1 -type d -name ' . $name, $home_dirs);
  $dirs = array_merge($data_dirs, $home_dirs);
  return $dirs;
}

function find_cruft_core() {
  $output = array();
  $handle = popen('wp core verify-checksums 2>&1', 'r');
  $temp = stream_get_contents($handle);
  pclose($handle);
  // Lines beginning with: "Warning: File should not exist: "
  $temp = explode("\n", $temp);
  foreach ( $temp as $line ) {
    if ( strpos($line, 'Warning: File should not exist: ') !== false ) {
      $line = '/data/wordpress/htdocs/wordpress/' . substr($line, 32);
      array_push($output, $line);
    }
  }
  return $output;
}
function list_known_cruft_file( $name ) {
  exec('ls ' . $name, $output);
  return $output;
}

function list_known_cruft_dir( $name ) {
  exec('ls -d ' . $name, $output);
  return $output;
}

function rmdir_recursive( $dir, $recursive ) {
  foreach ( scandir($dir) as $file ) {
    if ( '.' === $file || '..' === $file ) {
      continue; // Skip current and upper level directories
    }
    if ( is_dir("$dir/$file") ) {
      rmdir_recursive("$dir/$file", 1);
    } else {
      unlink("$dir/$file");
    }
  }
  rmdir($dir);
  if ( $recursive === 0 ) {
    return true; // when not called recursively
  }
}

function seravo_ajax_list_cruft_files() {
  check_ajax_referer('seravo_cruftfiles', 'nonce');
  switch ( $_REQUEST['section'] ) {
    case 'cruftfiles_status':
      // List of known types of cruft files
      $list_files = array(
        '*.sql',
        '.hhvm.hhbc',
        '*.wpress',
        'core',
        '*.bak',
      );
      // List of known cruft directories
      $list_dirs = array(
        'siirto',
        '*palautus*',
        'before*',
        'vanha',
        '*-old',
        '*-copy',
        '*-2',
        '*.bak',
        'migration',
        '*_BAK',
        '_mu-plugins',
        '*.orig',
        '-backup',
        '*.backup',
        getenv('WP_USER') . '_20*',
      );
      $list_known_files = array();
      $list_known_dirs = array(
        '/data/wordpress/htdocs/wp-content/plugins/all-in-one-wp-migration/storage',
        '/data/wordpress/htdocs/wp-content/ai1wm-backups',
        '/data/wordpress/htdocs/wp-content/uploads/backupbuddy_backups',
        '/data/wordpress/htdocs/wp-content/updraft',
      );
      $white_list_dirs = array(
        '/data/wordpress/htdocs/wp-content/plugins',
        '/data/wordpress/htdocs/wp-content/mu-plugins',
        '/data/wordpress/htdocs/wp-content/themes',
        '/data/wordpress/node_modules',
      );
      $white_list_files = array(
        '/data/wordpress/vagrant-base.sql',
      );

      $crufts = array();
      $crufts = array_merge($crufts, find_cruft_core());
      foreach ( $list_files as $filename ) {
        $cruft_found = find_cruft_file($filename);
        if ( ! empty($cruft_found) ) {
          $crufts = array_merge($crufts, $cruft_found);
        }
      }
      foreach ( $list_dirs as $dirname ) {
        $cruft_found = find_cruft_dir($dirname);
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
            array_push($keep, $filename);
          }
        }
        $crufts = array_diff($crufts, $keep);
      }
      foreach ( $white_list_files as $filename ) {
        // Some files are whitelisted as it is not necessary to delete them
        $keep = array();
        foreach ( $crufts as $cruftname ) {
          if ( strpos($cruftname, $filename) !== false ) {
            array_push($keep, $cruftname);
          }
        }
        $crufts = array_diff($crufts, $keep);
      }

      foreach ( $list_known_files as $dirname ) {
        $cruft_found = list_known_cruft_file($dirname);
        if ( ! empty($cruft_found) ) {
          $crufts = array_merge($crufts, $cruft_found);
        }
      }
      foreach ( $list_known_dirs as $dirname ) {
        $cruft_found = list_known_cruft_dir($dirname);
        if ( ! empty($cruft_found) ) {
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

      $crufts = array_map('add_file_information', $crufts);
      echo wp_json_encode($crufts);
      break;

    default:
      error_log('ERROR: Section ' . $_REQUEST['section'] . ' not defined');
      break;
  }

  wp_die();
}

/**
 * $_POST['deletefile'] is either a string denoting only one file
 * or it can contain an array containing strings denoting files.
 */
function seravo_ajax_delete_cruft_files() {
  check_ajax_referer('seravo_cruftfiles', 'nonce');
  if ( isset($_POST['deletefile']) && ! empty($_POST['deletefile']) ) {
    $files = $_POST['deletefile'];
    if ( is_string($files) ) {
      $files = array( $files );
    }
    if ( ! empty($files) ) {
      $result = array();
      $results = array();
      foreach ( $files as $file ) {
        $legit_cruft_files = get_transient('cruft_files_found'); // Check first that given file or directory is legitimate
        if ( in_array($file, $legit_cruft_files, true) ) {
          if ( is_dir($file) ) {
            $unlink_result = rmdir_recursive($file, 0);
          } else {
            $unlink_result = unlink($file);
          }
          // else - Backwards compatible with old UI
          $result['success'] = (bool) $unlink_result;
          $result['filename'] = $file;
          array_push($results, $result);
        }
      }
      echo wp_json_encode($results);
    }
  }
  wp_die();
}
