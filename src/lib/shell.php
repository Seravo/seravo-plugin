<?php
namespace Seravo;

/**
 * Class Shell
 *
 * Shell has static functions for executing command safely.
 */
class Shell {

  /**
   * Function for executing command more safely. Note that
   * this still isn't bulletproof.
   * @param string   $command     The hardcoded command to execute.
   * @param string[] $args        Optional extra arguments.
   * @param string[] $env         Optional extra env variables (key => value).
   * @param string[] $output      Array for the output.
   * @param int      $result_code Command exit code.
   * @return void
   */
  public static function safe_exec( $command, $args = array(), $env = array(), &$output = null, &$result_code = null ) {
    $safe_command = self::sanitize_command($command, $args, $env);

    exec($safe_command, $output, $result_code);
  }
  /**
   * Function for sanitizing commands to be more safe.
   * Note that this still isn't bulletproof.
   * @param string          $command The hardcoded command to execute.
   * @param null[]|string[] $args    Optional extra arguments.
   * @param string[]        $env     Optional extra env variables (key=>value).
   * @return string Sanitized command.
   */
  public static function sanitize_command( $command, $args = array(), $env = array() ) {
    $safe_command = '';

    // Set ENV
    foreach ( $env as $key => $var ) {
      $safe_command .= $key . '=' . $var . ' ';
    }

    // Set command
    $safe_command .= $command;

    // Set args
    foreach ( $args as $arg ) {
      if ( $arg === null ) {
        continue;
      }
      $safe_command .= ' ' . escapeshellarg($arg);
    }

    return escapeshellcmd($safe_command);
  }

  /**
   * Run a command on background.
   * @param string $command Command to run.
   * @return mixed|bool Pid of the program.
   */
  public static function backround_command( $command ) {
    $output = array();
    exec('(' . $command . ') > /dev/null & echo $!', $output);

    if ( count($output) >= 1 ) {
      return $output[0];
    }

    return false;
  }

  /**
   * Check if command is running.
   * @param string $pid Pid of the program.
   * @return bool Whether the command is running.
   */
  public static function is_pid_running( $pid ) {
    return file_exists("/proc/{$pid}");
  }

}
