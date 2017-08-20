<?php

/**
 * @class Capture_DB_Monitor.  Tracks insertions to the wordpress database between
 * startpoint and checkpoint.  The results are accessible via the public fields: `$new_tables` are the names
 * of tables that have been added, and $new_keys is a map of tablenames -> inserted keys.
 * (TODO: maybe checkpoint entire row value too?)
 *
 * This class only tracks insertions; it will not detect changes to existing rows, or deletions.
 * It also skips tables that have zero or multiple primary keys, for simplicity.  (All WP tables have
 * single primary keys).
 */

class Capture_DB_Monitor {
    public $base_tables;
    public $base_keys;
    public $new_tables;
    public $new_keys;

    public function __construct() {
        $this->base_tables = false;
        $this->base_keys = false;
        $this->new_tables = false;
        $this->new_keys = false;
    }

    public function baseline() {
        global $wpdb;
        $this->base_tables = $this->flatten( $wpdb->get_results( "SHOW TABLES", ARRAY_N ));
        $this->base_keys = array();

        foreach( $this->get_primary_key_columns() as $table => $key_column ) {
            if ( $key_column != -1 ) {
                $this->base_keys[$table] = $this->get_primary_keys($table,$key_column);
            }
        }
        set_transient('capture_plugin_base_tables',$this->base_tables, 60*60);
        set_transient( 'capture_plugin_base_keys', $this->base_keys, 60*60);
    }

    public function checkpoint() {
        global $wpdb;
        // get current state (if there is any)
        $this->base_tables = get_transient('capture_plugin_base_tables');
        $this->base_keys = get_transient( 'capture_plugin_base_keys' );

        // if the transients were missing, punt.  We don't actually have the data to compute anything
        if ( $this->base_tables === false || $this->base_keys === false ) return;

        // calculate the delta
        $current_table_names = $this->flatten( $wpdb->get_results( "SHOW TABLES", ARRAY_N ));
        $this->new_tables = array_diff( $current_table_names, $this->base_tables );

        // find new rows in existing tables
        $this->new_keys = array();
        foreach( $this->get_primary_key_columns() as $table => $key_column ) {
            if ( $key_column != -1 ) {
                $checkpoint_keys = $this->get_primary_keys( $table, $key_column );
                $delta =  array_diff( $checkpoint_keys, $this->base_keys[$table] );
                if ( !empty($delta) ) {
                    $this->new_keys[$table] = $delta;
                }
            }
        }
    }

    /**
     * Return an array mapping table names -> the column name of the primary key.
     * @return array
     */
    public function get_primary_key_columns() {
        global $wpdb;
        $table_keys = array();
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT `TABLE_NAME`, `COLUMN_NAME`
            FROM `information_schema`.`COLUMNS`
            WHERE (`TABLE_SCHEMA` = '%s')
              AND (`COLUMN_KEY` = 'PRI');", DB_NAME));
        foreach( $results as $result ) {
            if ( array_key_exists( $result->TABLE_NAME, $table_keys) ) {
                // it's got multiple keys, mark it invalid
                $table_keys[$result->TABLE_NAME] = -1;
            }
            else {
                $table_keys[$result->TABLE_NAME] = $result->COLUMN_NAME;
            }
        }
        return $table_keys;
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


    /**
     * Clean up new_key results, using knowledge of what is in the wordpress tables.
     * We get rid of data we think is less interesting (e.g. transients in the options table) in some cases,
     * and add more-interesting data in other cases (e.g. using the option name instead of id)
     *
     * @param array $tables
     */
    public function cleanup($newkeys) {
        global $wpdb;
        if ( array_key_exists($wpdb->options,$newkeys) ) {
            $ids = $newkeys[$wpdb->options];
            $data = $wpdb->get_results(
                "SELECT option_name, option_value FROM $wpdb->options WHERE option_id in (" .
                implode(', ', $ids ) . ") AND option_name NOT LIKE '%_transient_%'",
                ARRAY_N
            );
            $newkeys[$wpdb->options] = $data;
        }
        if ( array_key_exists($wpdb->postmeta, $newkeys) ) {
            $ids = $newkeys[$wpdb->postmeta];
            $data = $wpdb->get_results(
                "SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_id in (" .
                implode(', ', $ids ) . ")",
                ARRAY_N
            );
            $newkeys[$wpdb->postmeta] = $data;
        }
        if ( array_key_exists($wpdb->posts, $newkeys) ) {
            $ids = $newkeys[$wpdb->posts];
            $data = $wpdb->get_results(
                "SELECT ID, post_type, post_title FROM $wpdb->posts WHERE ID in (" .
                implode( ', ', $ids ) . ") AND NOT post_type = 'revision'"
            );
            $newkeys[$wpdb->posts] = $data;
        }
        return $newkeys;
    }
}