<?php
/*
 * Plugin name: Reports
 * Description: View various reports, e.g. HTTP request staistics from GoAccess
 */

namespace Seravo;

// Deny direct access to this file
if ( ! defined('ABSPATH') ) {
  die('Access denied!');
}

if ( ! class_exists('Logs') ) {
  class Logs {

    private $capability_required;

    public static $instance;

    public static function init() {
      if ( is_null(self::$instance) ) {
        self::$instance = new Logs();
      }
      return self::$instance;
    }

    private function __construct() {
      $this->capability_required = 'activate_plugins';

      // on multisite, only the super-admin can use this plugin
      if ( is_multisite() ) {
        $this->capability_required = 'manage_network';
      }

      add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ));
      add_action('wp_ajax_fetch_log_rows', array( $this, 'ajax_fetch_log_rows' ));
    }

    /**
     * Enqueues styles and scripts for the admin tools page
     *
     * @param mixed $hook
     * @access public
     * @return void
     */
    public function admin_enqueue_styles( $hook ) {
      wp_register_style('log_viewer', plugin_dir_url(__DIR__) . '/style/log-viewer.css', '', Helpers::seravo_plugin_version());
      wp_register_script('log_viewer', plugin_dir_url(__DIR__) . '/js/log-viewer.js', '', Helpers::seravo_plugin_version());

      if ( $hook === 'tools_page_logs_page' ) {
        wp_enqueue_style('log_viewer');
        wp_enqueue_script('log_viewer');
      }
    }

    /**
     * Renders the admin tools page content
     *
     * @see add_submenu_page
     *
     * @access public
     * @return void
     */
    public function render_tools_page() {
      global $current_log;

      $regex = null;
      if ( isset($_GET['regex']) ) {
        $regex = $_GET['regex'];
      }

      // Default log view is the PHP error log as it is the most important one
      $default_logfile = 'php-error.log';

      $max_num_of_rows = 50;
      if ( isset($_GET['max_num_of_rows']) ) {
          $max_num_of_rows = (int) $_GET['max_num_of_rows'];
      }

      // Automatically fetch all logs from /data/log/*.log
      $logs = glob('/data/log/*.log');

      // Check for missing .log files and fetch rotated .log-12345678 file instead
      // using an array of possible log names to compare fetched array against.
      $log_names = array(
        '/data/log/chromedriver.log',
        '/data/log/mail.log',
        '/data/log/nginx-access.log',
        '/data/log/nginx-error.log',
        '/data/log/php-error.log',
        '/data/log/security.log',
        '/data/log/tideways.log',
        '/data/log/update.log',
        '/data/log/wp-login.log',
        '/data/log/wp-theme-security.log',
      );
      // Skip runit.log and bootstrap.log and other logs that are not relevant
      // for customers and only list the ones a UI user might be interested in.

      // Store all missing log names to an array
      $missing_logs = array_diff($log_names, $logs);

      // Fetch rotated logs for each missing log and append them to $logs
      if ( ! empty($missing_logs) ) {
        foreach ( $missing_logs as $log ) {
          $found_log = implode('', preg_grep('/([0-9]){8}$/', glob('{' . $log . '}-*', GLOB_BRACE)));
          if ( ! empty($found_log) ) {
            if ( $log === '/data/log/' . $default_logfile ) {
              $found_log_path = explode('/', $found_log);
              $default_logfile = end($found_log_path);
            }
            array_push($logs, $found_log);
          }
        }
      }

      if ( empty($logs) ) {
          echo '<div class="notice notice-warning" style="padding:1em;margin:1em;">' .
          __('No logs found in <code>/data/log/</code>.', 'seravo') . '</div>';
      }

      // Create an array of the logfiles with basename of log as key
      $logfiles = array();
      foreach ( $logs as $key => $log ) {
        $logfiles[ basename($log) ] = $log;
      }

      // Use supplied log name if given
      if ( isset($_GET['logfile']) ) {
        $current_logfile = $_GET['logfile'];
      } else {
        $current_logfile = $default_logfile;
      }

      // Set logfile based on supplied log name if it's available
      if ( isset($logfiles[ $current_logfile ]) ) {
        $logfile = $logfiles[ $current_logfile ];
      } elseif ( isset($logfiles[ $default_logfile ]) ) {
        $logfile = $logfiles[ $default_logfile ];
      } else {
        $logfile = null;
      }

      ?>
  <div class="wrap">
    <h1><?php _e('Logs', 'seravo'); ?></h1>
    <h2 class="screen-reader-text">Select log file list</h2>
    <ul class="subsubsub">
      <?php foreach ( $logs as $key => $log ) : ?>
      <li><a href="tools.php?page=logs_page&logfile=<?php echo basename($log); ?>&max_num_of_rows=<?php echo $max_num_of_rows; ?>"
            class="<?php echo basename($log) == $current_logfile ? 'current' : ''; ?>">
            <?php echo basename($log); ?>
          </a>
          <?php echo ($key < (count($logs) - 1)) ? ' |' : ''; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <p class="clear"></p>
      <?php $this->render_log_view($logfile, $regex, $max_num_of_rows); ?>
  </div>
      <?php
    }

    /**
     * Renders the log view for a specific $logfile on the tools page
     *
     * @param string $logfile
     * @param string $regex
     * @access public
     * @return void
     */
    public function render_log_view( $logfile, $regex = null, $max_num_of_rows ) {
      global $current_log;
      ?>
  <div class="log-view">
      <?php if ( is_readable($logfile) ) : ?>
    <div class="tablenav top">
      <form class="log-filter" method="get">
        <label class="screen-reader-text" for="regex">Regex:</label>
        <input type="hidden" name="page" value="logs_page">
        <input type="hidden" name="log" value="<?php echo $current_log; ?>">
        <input type="hidden" name="logfile" value="<?php echo basename($logfile); ?>">
        <input type="search" name="regex" value="<?php echo $regex; ?>" placeholder="">
        <input type="submit" class="button" value="<?php _e('Filter', 'seravo'); ?>">
      </form>
    </div>
    <div class="log-table-view"
      data-logfile="<?php echo esc_attr($logfile); ?>"
      data-logbytes="<?php echo esc_attr(filesize($logfile)); ?>"
      data-regex="<?php echo esc_attr($regex); ?>">
      <table class="wp-list-table widefat striped" cellspacing="0">
        <tbody>
          <?php $result = $this->render_rows($logfile, -1, $max_num_of_rows, $regex); ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
      <?php if ( ! is_null($logfile) ) : ?>
        <?php if ( ! is_readable($logfile) ) : ?>
      <div id="message" class="notice notice-error">
        <p>
          <?php
            // translators: $s name of the logfile
            printf(__("File %s does not exist or we don't have permissions to read it.", 'seravo'), $logfile);
          ?>
        </p>
      </div>
      <?php elseif ( ! $result ) : ?>
        <p><?php _e('Log empty', 'seravo'); ?></p>
      <?php else : ?>
        <p><?php _e('Scroll to load more lines from the log.', 'seravo'); ?></p>
        <?php
      endif;
    endif;
?>
    <div class="log-view-active">
    </div>

    <p>
      <?php
      // translators: $s full path of the logfile
      printf(__('Full log files can be found on the server in the path %s.', 'seravo'), '<code>/data/log/</code>');
      ?>
    </p>
  </div>

      <?php
    }


    /**
     * Renders $lines rows of a $logfile ending at $offset from the end of the cutoff marker
     *
     * @param string $logfile
     * @param int $offset
     * @param int $lines
     * @param string $regex
     * @param int $cutoff_bytes
     * @access public
     * @return void
     */
    public function render_rows( $logfile, $offset, $lines, $regex = null, $cutoff_bytes = null ) {

      // escape special regex chars
      $regex = '#' . preg_quote($regex) . '#';

      $rows = self::read_log_lines_backwards($logfile, $offset, $lines, $regex, $cutoff_bytes);

      if ( empty($rows) ) {
        return 0;
      }

      $num_of_rows = 0;
      foreach ( $rows as $row ) {
        ++$num_of_rows;
        ?>
        <tr>
          <td><span class="logrow"><?php echo $row; ?></span></td>
        </tr>
        <?php
      }
      return $num_of_rows;
    }


    /**
     * An ajax endpoint that fetches and renders the log rows for a logfile
     *
     * @access public
     * @return void
     */
    public function ajax_fetch_log_rows() {
      // check permissions
      if ( ! current_user_can($this->capability_required) ) {
        exit;
      }

      if ( isset($_REQUEST['logfile']) ) {
        $logfile = $_REQUEST['logfile'];
      } else {
        exit;
      }

      $offset = 0;
      if ( isset($_REQUEST['offset']) ) {
        $offset = -(1 + (int) $_REQUEST['offset']);
      }

      $regex = null;
      if ( isset($_REQUEST['regex']) ) {
        $regex = $_REQUEST['regex'];
      }

      $cutoff_bytes = null;
      if ( isset($_REQUEST['cutoff_bytes']) ) {
        $cutoff_bytes = (int) $_REQUEST['cutoff_bytes'];
      }

      $this->render_rows($logfile, $offset, 100, $regex, $cutoff_bytes);
      exit;
    }

    /**
     * Reads $lines lines from $filename ending at $offset and returns the lines as array
     *
     * @param string $filepath
     * @param int $offset
     * @param int $lines
     * @param string $regex
     * @param int $cutoff_bytes
     * @static
     * @access public
     * @return array
     */
    public static function read_log_lines_backwards( $filepath, $offset = -1, $lines = 1, $regex = null, $cutoff_bytes = null ) {
      // Open file
      $f = @fopen($filepath, 'rb');

      if ( $f === false ) {
        return false;
      }

      $filesize = filesize($filepath);

      // buffer size is 4096 bytes
      $buffer = 4096;

      if ( is_null($cutoff_bytes) ) {
        // Jump to last character
        fseek($f, -1, SEEK_END);
      } else {
        // Jump to cutoff point
        fseek($f, $cutoff_bytes - 1, SEEK_SET);
      }

      // Start reading
      $output = array();
      $linebuffer = '';

      // start with a newline if the last character of the file isn't one
      if ( fread($f, 1) !== "\n" ) {
        $linebuffer = "\n";
      }

      // the newline in the end accouts for an extra line
      $lines--;

      // While we would like more
      while ( $lines > 0 ) {
        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);

        // if this is the last buffer we're looking at we need to take the first
        // line without leading newline into account
        $last_buffer = (ftell($f) <= $buffer);

        // file has ended
        if ( $seek <= 0 ) {
          break;
        }

        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);

        // Read a chunk
        $chunk = fread($f, $seek);

        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

        // prepend it to our line buffer
        $linebuffer = $chunk . $linebuffer;

        // see if there are any complete lines in the line buffer
        $complete_lines = array();

        if ( $last_buffer ) {
          // last line is whatever is in the line buffer before the second line
          $complete_lines [] = rtrim(substr($linebuffer, 0, strpos($linebuffer, "\n")));
        }

        while ( preg_match('/\n(.*?\n)/s', $linebuffer, $matched) ) {
          // get the $1 match
          $match = $matched[1];

          // remove matched line from line buffer
          $linebuffer = substr_replace($linebuffer, '', strpos($linebuffer, $match), strlen($match));

          // add the line
          $complete_lines [] = rtrim($match);
        }

        // remove any offset lines off the end
        $limit = count($complete_lines);
        while ( $offset < -1 && $limit > 0 ) {
          array_pop($complete_lines);
          $offset++;
          $limit--;
        }

        // apply a regex filter
        if ( ! is_null($regex) ) {
          $complete_lines = preg_grep($regex, $complete_lines);

          // wrap regex match part in <span class="highlight">
          foreach ( $complete_lines as &$line ) {
            $line = preg_replace($regex, '<span class="highlight">$0</span>', $line);
          }
        }

        // decrement lines needed
        $lines -= count($complete_lines);

        // prepend complete lines to our output
        $output = array_merge($complete_lines, $output);
      }

      // remove any lines that might have gone over due to the chunk size
      while ( ++$lines < 0 ) {
        array_shift($output);
      }

      // Close file
      fclose($f);

      return $output;
    }

  }

  Logs::init();

}
