<?php
/**
 * Migration 0006 — Add telefon column to bk_leadovi
 */

return array(

    'up' => function () {
        global $wpdb;
        $table = $wpdb->prefix . 'bk_leadovi';
        $wpdb->query( "ALTER TABLE $table ADD COLUMN telefon VARCHAR(50) NOT NULL DEFAULT '' AFTER email" );
    },

    'down' => function () {
        global $wpdb;
        $table = $wpdb->prefix . 'bk_leadovi';
        $wpdb->query( "ALTER TABLE $table DROP COLUMN telefon" );
    },

);
