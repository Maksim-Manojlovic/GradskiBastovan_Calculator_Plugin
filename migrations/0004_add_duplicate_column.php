<?php
/**
 * Migration 0004 — Add is_duplicate column to bk_leadovi
 *
 * 0 = regular lead
 * 1 = same email was seen within the last 24 hours
 */

return array(

    'up' => function () {
        global $wpdb;
        $t = $wpdb->prefix . 'bk_leadovi';

        $exists = $wpdb->get_results( "SHOW COLUMNS FROM $t LIKE 'is_duplicate'" );
        if ( empty( $exists ) ) {
            $wpdb->query( "ALTER TABLE $t ADD COLUMN is_duplicate TINYINT(1) NOT NULL DEFAULT 0 AFTER status" );
            $wpdb->query( "ALTER TABLE $t ADD INDEX idx_email (email(100))" );
        }
    },

    'down' => function () {
        global $wpdb;
        $t = $wpdb->prefix . 'bk_leadovi';

        $exists = $wpdb->get_results( "SHOW COLUMNS FROM $t LIKE 'is_duplicate'" );
        if ( ! empty( $exists ) ) {
            $wpdb->query( "ALTER TABLE $t DROP INDEX idx_email" );
            $wpdb->query( "ALTER TABLE $t DROP COLUMN is_duplicate" );
        }
    },

);
