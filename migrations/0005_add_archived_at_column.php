<?php
/**
 * Migration 0005 — Add archived_at column to bk_leadovi
 *
 * NULL  = active lead
 * datetime = archived (soft deleted) at this timestamp
 */

return array(

    'up' => function () {
        global $wpdb;
        $t = $wpdb->prefix . 'bk_leadovi';

        $exists = $wpdb->get_results( "SHOW COLUMNS FROM $t LIKE 'archived_at'" );
        if ( empty( $exists ) ) {
            $wpdb->query( "ALTER TABLE $t ADD COLUMN archived_at DATETIME NULL DEFAULT NULL AFTER nota" );
            $wpdb->query( "ALTER TABLE $t ADD INDEX idx_archived (archived_at)" );
        }
    },

    'down' => function () {
        global $wpdb;
        $t = $wpdb->prefix . 'bk_leadovi';

        $exists = $wpdb->get_results( "SHOW COLUMNS FROM $t LIKE 'archived_at'" );
        if ( ! empty( $exists ) ) {
            $wpdb->query( "ALTER TABLE $t DROP INDEX idx_archived" );
            $wpdb->query( "ALTER TABLE $t DROP COLUMN archived_at" );
        }
    },

);
