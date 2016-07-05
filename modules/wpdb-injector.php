<?php
/*
Plugin Name:  WPDB-injector
Description:  Filter out OPTIMIZE directives from database.
              These are not needed since the performance gain is so small.
              We run these from central server instead.
Version:      1.0.0
Author:       Onni Hakala / Seravo Oy
*/

namespace WPPalvelu;

class WPDB_Injector {

  /*
   * Store original $wpdb here
   */
  private $wpdb;

  static function init() {
          global $wpdb;
          if ( ! $wpdb instanceof WPDB_Injector ) {
                  $wpdb = new WPDB_Injector( $wpdb );
          }
    }

    /*
     * Use magic methods to use normal $wpdb methods when possible
     */
    private function __construct( $wpdb ) { $this->wpdb = $wpdb; }
    public function __call(  $method, $args ) { return call_user_func_array( array( $this->wpdb, $method ), $args ); }
    public function __get(   $var ) {           return $this->wpdb->$var; }
    public function __set(   $var, $val ) {     return $this->wpdb->$var = $val; }
    public function __isset( $var ) {           return isset( $this->wpdb->$var ); }
    public function __unset( $var ) {                  unset( $this->wpdb->$var ); }

    /*
     * Override all normal wpdb functions here
     */

    /*
     * Only get_row function seems to be used for OPTIMIZE query
     */
    public function get_row() {
      $args = func_get_args();

      do_action( 'WPDB_Injector_get_row', $args );

      $result = $this->parse_optimize_directives($args[0]);

      if ($result != false) {
        // This was OPTIMIZE QUERY
        do_action( 'WPDB_Injector_optimize_sql', $result );
        error_log("CALLED SQL OPTIMIZE: ".print_r($args,true));
        return $result;
      } else {
        // It was normal query, proceed to wpdb
        return $this->__call( 'get_row', $args ); 
      }
    }



    #############################
    # LIST OF HELPERS FUNCTIONS #
    #############################

    /**
     * Checks for OPTIMIZE queries
     *
     * @param $query - first param of wpdb->method($param)
     *
     * @return mixed - Boolean false or stdObject. stdObject is the same result which $wpdb should produce after OPTIMIZE.
     */
    private function parse_optimize_directives($query) {
      /*
       * Look for OPTIMIZE directive in the beginning of the string
       */
      $regex = "/^[\s]*OPTIMIZE[\s]+TABLE[\s]([^;]*)/";

      preg_match($regex,$query,$matches);

      if (isset($matches[1])) {
        $tables = $matches[1];
        // Get all tables 
        $tables = explode(',',$tables);
        // Trim whitespace
        $table = trim($tables[0]);

        /*
         * Somehow $wpdb only returns result of the optimization on the first table.
         * We do the same here
         */
        $dummy_result = (object) array(
            'Table'=>DB_NAME.".{$table}",
            'Op'=>'optimize',
            'Msg_type'=>'note',
            'Msg_text'=>'Table does not support optimize, doing nothing instead'
        );

        return $dummy_result;
      }

      return false;
    }

  }
  WPDB_Injector::init();