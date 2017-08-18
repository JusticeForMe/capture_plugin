<?php

/**
 * Class Capture_DB_Additions.  Tracks certain kinds of changes to the wordpress database between
 * startpoint and checkpoint.  Checkpoint may be called multiple times; later checkpoints override
 * earlier ones.  The results are accessible via the public fields: `$new_tables` are the names
 * of tables that have been added, and $new_values is a map of
 * tablenames -> inserted keys -> their value at checkpoints
 *
 * This class only tracks insertions; it will not detect changes to existing rows, or deletions.
 * It also skips tables that have multiple primary keys, for simplicity.  (All WP tables have
 * single primary keys).
 */

class Capture_DB_Additions {
    public $base_tables;
    public $base_keys;

    public $new_tables;
    public $new_keys;
    public $new_values;

    public function __construct() {
        $this->reset();
    }

    public function reset() {
        $this->base_tables = array();
        $this->base_keys = array();
        $this->new_tables = array();
        $this->new_keys = array();
        $this->new_values = array();
    }

    public function baseline() {
        $this->base_tables = $this->get_primary_key_columns();
        $this->base_keys = array();
        foreach( $this->base_tables as $table => $key ) {
            if ( $key != -1 ) {
                $this->base_keys[$table] = $this->get_primary_keys($table,$key);
            }
        }
    }

    public function checkpoint() {
        global $wpdb;
        // find new tables
        $base_table_names = array_keys($this->base_tables);
        $current_table_names = $this->flatten( );

        // find new values in existing tables
    }

    /**
     * Return an array mapping table names -> the column name of the primary key.
     * @return array
     */
    public function get_primary_key_columns() {
        global $wpdb;
        $primary_keys = array();
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT `TABLE_NAME`, `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = '%s')
              AND (`COLUMN_KEY` = 'PRI');", DB_NAME));
        foreach( $results as $result ) {
            if ( array_key_exists($primary_keys, $result->TABLE_NAME) ) {
                // it's got multiple keys, mark it invalid
                $primary_keys[$result->TABLE_NAME] = -1;
            }
            else {
                $primary_keys[$result->TABLE_NAME] = $result->COLUMN_NAME;
            }
        }
        return $primary_keys;
    }

    /**
     * Return an array of key values for the specified table
     * @param $table
     * @param $key
     * @return array
     */
    public function get_primary_keys($table,$key) {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT `" . $key . "` FROM `" . $table . "`",
            ARRAY_N);
        return $this->flatten($results);
    }

    /**
     * For queries which return a single column, convert the array of rows into an array of values
     * @param $query_result
     * @return array
     */
    private function flatten($query_result) {
        return array_map(function($ar) { return $ar[0]; }, $query_result);
    }
}