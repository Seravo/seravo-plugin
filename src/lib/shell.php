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
   * @param string $command      The hardcoded command to execute.
   * @param array  $args         Optional extra arguments.
   * @param array  $env          Optional extra env variables (key=>value).
   * @param array  &$output      Array for the output.
   * @param array  &$result_code Command exit code.
   */
  public static function safe_exec( $command, $args = array(), $env = array(), &$output = null, &$result_code = null ) {
    $safe_command = self::sanitize_command($command, $args, $env);

    exec($safe_command, output, result_code);
  }
  /**
   * Function for sanitizing commands to be more safe.
   * Note that this still isn't bulletproof.
   * @param string $command      The hardcoded command to execute.
   * @param array  $args         Optional extra arguments.
   * @param array  $env          Optional extra env variables (key=>value).
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

}
