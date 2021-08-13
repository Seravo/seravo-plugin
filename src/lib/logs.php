<?php

namespace Seravo;

/**
 * Class Logs
 *
 * Logs class has static methods for all log reading and parsing.
 */
class Logs {

  /**
   * Get the log files under /data/log.
   * @return array<string,mixed> Grouped log files with time info.
   */
  public static function get_logs_with_time() {
    $log_files = \glob('/data/log/*.log*');
    if ( $log_files === false ) {
      // Glob failed
      return array();
    }

    // Group log files
    $grouped_log_files = array();
    foreach ( $log_files as $log_file ) {
      // Skip empty files
      if ( \filesize($log_file) === 0 ) {
        continue;
      }

      $log_file = \basename($log_file);
      $base_log = $log_file;

      $filetype_pos = \strpos($log_file, '.log-');
      if ( $filetype_pos !== false ) {
        // This is an old rotated log
        $base_log = Compatibility::substr($log_file, 0, $filetype_pos + 4);
        if ( $base_log === false ) {
          continue;
        }
      }

      if ( ! isset($grouped_log_files[$base_log]) ) {
        $grouped_log_files[$base_log] = array();
      }
      $grouped_log_files[$base_log][] = $log_file;
    }

    $logs_with_time = array();
    foreach ( $grouped_log_files as $group => $files_in_group ) {
      // Sort files (oldest first)
      \usort(
        $files_in_group,
        function( $log1, $log2 ) {
          if ( \strpos($log1, '.log-') === false ) {
            return 1;
          }
          if ( \strpos($log2, '.log-') === false ) {
            return -1;
          }

          return ($log2 < $log1) ? 1 : -1;
        }
      );

      // Get log times
      $logs_with_time[$group] = array();
      foreach ( $files_in_group as $i => $log_file ) {
        $since = null;
        if ( $i > 0 ) {
          $since = $logs_with_time[$group][$i - 1]['until'];
        }

        $filetype_pos = \strpos($log_file, '.log-');
        $until = $filetype_pos === false ? null : Compatibility::substr($log_file, $filetype_pos + 5, 8);

        if ( $until === false ) {
          $until = null;
        }

        $logs_with_time[$group][] = array(
          'file' => $log_file,
          'since' => $since,
          'until' => $until,
        );
      }

      $logs_with_time[$group] = \array_reverse($logs_with_time[$group]);
    }

    return $logs_with_time;
  }

  /**
   * Read file in given $filepath backwards from $offset for maximum of $lines.
   * @param string $filepath Full path to the log file.
   * @param int    $offset   Amount of lines to skip from the end.
   * @param int    $lines    Maximum amount of lines to read.
   * @return array<string,mixed> Array with status and error or log lines.
   */
  public static function read_log_lines_backwards( $filepath, $offset = 0, $lines = 1 ) {
    //$filepath = '/data/log/nginx-access.log-20210705.gz';
    // Check if the file is .gz
    if ( \substr($filepath, -3) === '.gz' ) {
      return self::read_gz_log_lines_backwards($filepath, $offset, $lines);
    }

    // Check that $filepath is valid log path
    $files = \glob('/data/log/*');
    $valid_log_path = $files !== false && \in_array($filepath, $files, true);

    $f = $valid_log_path ? @\fopen($filepath, 'rb') : false;

    $result = array(
      'output' => array(),
      'status' => 'OK_LOG_FILE',
    );

    // Check if the file was found and valid
    if ( $f === false ) {
      $result['status'] = 'NO_LOG_FILE';
      $result['error'] = \__('File not found', 'seravo');
      return $result;
    }

    // Prevent reading huge files (over 256MB)
    $filesize = \filesize($filepath);
    if ( $filesize >= 268435456 ) {
      $result['status'] = 'LARGE_LOG_FILE';
      $result['error'] = \__('File too large', 'seravo');
      return $result;
    }

    // Jump to last character
    if ( \fseek($f, -1, SEEK_END) === -1 ) {
      // fseek failed
      $result['status'] = 'BAD_LOG_FILE';
      $result['error'] = \__('Error reading the file', 'seravo');
      return $result;
    }

    $linebuffer = '';
    // Start with a newline if the last character of the file isn't one
    if ( \fread($f, 1) !== "\n" ) {
      $linebuffer = "\n";
    }

    --$lines;

    // Buffer size is 4096 bytes
    $buffer = 4096;

    while ( $lines > 0 ) {
      // Figure out how far back we should jump
      $seek = \min(\ftell($f), $buffer);

      // If this is the last buffer we're looking at we need to take the first
      // line without leading newline into account
      $last_buffer = (\ftell($f) <= $buffer);

      // File has ended
      if ( $seek <= 0 ) {
        break;
      }

      // Do the jump (backwards, relative to where we are)
      \fseek($f, -$seek, SEEK_CUR);

      // Read a chunk
      $chunk = \fread($f, $seek);
      if ( $chunk === false ) {
        // fread failed
        $result['status'] = 'BAD_LOG_FILE';
        $result['error'] = \__('Error reading the file', 'seravo');
        return $result;
      }

      // Jump back to where we started reading
      \fseek($f, -\mb_strlen($chunk, '8bit'), SEEK_CUR);

      // Prepend chunk to our line buffer
      $linebuffer = $chunk . $linebuffer;

      // See if there are any complete lines in the line buffer
      $complete_lines = array();

      if ( $last_buffer ) {
        // Last line is whatever is in the line buffer before the second line
        $eol = \strpos($linebuffer, "\n");
        if ( $eol !== false ) {
          $complete_line = Compatibility::substr($linebuffer, 0, $eol);
          if ( $complete_line !== false ) {
            $complete_lines[] = \rtrim($complete_line);
          }
        }
      }

      // TODO: Find out what the regex does and comment it
      while ( \preg_match('/\n(.*?\n)/s', $linebuffer, $matched) ) {
        // Get the $1 match
        $match = $matched[1];

        $match_pos = \strpos($linebuffer, $match);
        if ( $match_pos === false ) {
          // Shouldn't happen as we matched it
          $result['status'] = 'BAD_LOG_FILE';
          $result['error'] = \__('Error reading the file', 'seravo');
          return $result;
        }

        // Remove matched line from line buffer
        $linebuffer = \substr_replace($linebuffer, '', $match_pos, \strlen($match));

        // Sanitize and add the line
        $complete_lines[] = \htmlspecialchars(\rtrim($match));
      }

      // Remove any offset lines off the end
      $limit = \count($complete_lines);
      while ( $offset > 0 && $limit > 0 ) {
        \array_pop($complete_lines);
        --$offset;
        --$limit;
      }

      if ( $complete_lines !== array() ) {
        // Decrement lines needed
        $lines -= \count($complete_lines);
        // Prepend complete lines to our output
        $result['output'] = \array_merge($complete_lines, $result['output']);
      }
    }

    // Remove any lines that might have gone over due to the chunk size
    while ( ++$lines < 0 ) {
      \array_shift($result['output']);
    }

    // Reverse the output
    $result['output'] = \array_reverse($result['output']);

    // Close file
    \fclose($f);

    return $result;
  }

  /**
   * Read compressed file in given $filepath backwards from $offset for maximum of $lines.
   * WARNING: This function is quite heavy compared to reading of uncompressed files.
   * @todo Test on a huge file on a heavy site before version release.
   * @param string $filepath Full path to the log file.
   * @param int    $offset   Amount of lines to skip from the end.
   * @param int    $lines    Maximum amount of lines to read.
   * @return array<string,mixed> Array with status and error or log lines.
   */
  public static function read_gz_log_lines_backwards( $filepath, $offset = 0, $lines = 1 ) {
    // Check that $filepath is valid log path
    $files = \glob('/data/log/*');
    $valid_log_path = $files !== false && \in_array($filepath, $files, true);

    $f = $valid_log_path ? @\gzfile($filepath) : false;

    $result = array(
      'output' => array(),
      'status' => 'OK_LOG_FILE',
    );

    // Check if the file was found and valid
    if ( $f === false ) {
      $result['status'] = 'NO_LOG_FILE';
      $result['error'] = \__('File not found', 'seravo');
      return $result;
    }

    // Prevent reading huge files (over 256MB)
    // Note that the uncompressed size is more than that
    $filesize = \filesize($filepath);
    if ( $filesize >= 268435456 ) {
      $result['status'] = 'LARGE_LOG_FILE';
      $result['error'] = \__('File too large', 'seravo');
      return $result;
    }

    // Skip the $offset amount of lines
    for ( $i = $offset; $i > 0; --$i ) {
      $line = \array_pop($f);
      if ( $line === null ) {
        break;
      }
    }

    // Take last $lines amount of lines as result§
    for ( $i = $lines; $i > 0; --$i ) {
      $line = \array_pop($f);
      if ( $line === null ) {
        break;
      }

      $result['output'][] = $line;
    }

    return $result;
  }

  /**
   * Get the amount of php-error.log lines that have been added this week. The
   * first day of the week is retreived from WordPress settings.
   * @param int $max_rows Maximum amount of log lines to read. Not that this may cause incorrect result.
   * @return int|false The amount of php-error.log lines appended this week or false on failure.
   */
  public static function get_week_error_count( $max_rows = 200 ) {
    // Check the first day of week from wp options, and transform to last day of week
    $wp_first_day = \get_option('start_of_week');
    $last_day_int = $wp_first_day === 0 ? 6 : $wp_first_day - 1;
    $days = array(
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
    );
    $last_day_of_week = \strtotime('last ' . $days[$last_day_int]);

    // TODO: Loop trough older files if needed
    $log_read = self::read_log_lines_backwards('/data/log/php-error.log', 0, $max_rows);

    if ( $log_read['status'] !== 'OK_LOG_FILE' ) {
      // Log file is too large, in invalid format or not found
      return false;
    }

    $php_errors = 0;

    // Loop through all the log lines
    foreach ( $log_read['output'] as $line ) {
      // Split the line from spaces and retrieve the error date from line
      $output_array = \explode(' ', $line);
      $date_str = Compatibility::substr($output_array[0], 1, \strlen($output_array[0]));

      if ( $date_str === false ) {
        continue;
      }

      // Just jump over the lines that don't contain dates
      if ( \preg_match('/^(0[1-9]|[1-2]\d|3[0-1])-([a-z]|[A-Z]){3}-\d{4}.*$/', $date_str) === 1 ) {
        // Only count logs from this week
        if ( \strtotime($date_str) <= $last_day_of_week ) {
          break;
        }
      } else {
        // Invalid date or error on preg_match, lets skip this error
        continue;
      }

      ++$php_errors;
    }

    return $php_errors;
  }

  /**
   * Get the last login details from the current user logged into WordPress.
   * Note that this is supposed to be called right after user login and the
   * last login refers to the one login before that.
   * @return array<string,mixed>|false Previous login data or false on failure.
   */
  public static function retrieve_last_login() {
    $log_read = self::read_log_lines_backwards('/data/log/wp-login.log', 0, 200);

    if ( ! isset($log_read['status']) || $log_read['status'] !== 'OK_LOG_FILE' ) {
      // Reading log failed
      return false;
    }

    if ( ! isset($log_read['output']) || $log_read['output'] === array() ) {
      // Log file empty
      return false;
    }

    $user_data = \get_userdata(\wp_get_current_user()->ID);
    if ( $user_data === false ) {
      // Can't get user data for current user
      return false;
    }

    $skipped_latest_login = false;
    foreach ( $log_read['output'] as $line ) {
      $matched = \preg_match('/^(?<ip>[.:0-9a-f]+) - (?<name>[\w\-_.*@ ]+) \[(?<datetime>[\d\/\w: +]+)\] .* (?<status>[A-Z]+$)/', $line, $entry);

      if ( $matched === 1 && $user_data->user_login === $entry['name'] && $entry['status'] == 'SUCCESS' ) {
        // Current entry is a succesful login for current user
        if ( ! $skipped_latest_login ) {
          // Skip the first found entry as it's probably the
          // last login and we want the one login before that.
          $skipped_latest_login = true;
          continue;
        }

        // Fetch login IP and the reverse domain name
        $ip = $entry['ip'];
        $domain = \gethostbyaddr($ip);

        // Fetch login date and time
        $timezone = \get_option('timezone_string');
        if ( $timezone === false || $timezone === '' ) {
          $timezone = 'UTC';
        }

        // Parse the date in to DateTime
        $datetime = \DateTime::createFromFormat('d/M/Y:H:i:s T', $entry['datetime']);
        if ( $datetime === false ) {
          continue;
        }
        $datetime->setTimezone(new \DateTimeZone($timezone));

        return array(
          'date'   => $datetime->format(\get_option('date_format')),
          'time'   => $datetime->format(\get_option('time_format')),
          'ip'     => $ip,
          'domain' => $domain !== false ? $domain : $ip,
          'user'   => $user_data->user_firstname === '' ? $user_data->user_login : $user_data->user_firstname,
        );
      }
    }

    // No match
    return false;
  }

}
