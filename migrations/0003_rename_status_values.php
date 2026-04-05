<?php
/**
 * Migration 0003 — Rename status values to English
 *
 * novi        → new
 * kontaktiran → contacted
 * zatvoren    → closed
 * izgubljen   → lost
 */

return array(

    'up' => function () {
        global $wpdb;
        $t = $wpdb->prefix . 'bk_leadovi';

        $map = array(
            'novi'        => 'new',
            'kontaktiran' => 'contacted',
            'zatvoren'    => 'closed',
            'izgubljen'   => 'lost',
        );

        foreach ( $map as $old => $new ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE $t SET status = %s WHERE status = %s",
                $new, $old
            ) );
        }

        // Update default za nove redove
        $wpdb->query( "ALTER TABLE $t MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'new'" );
    },

    'down' => function () {
        global $wpdb;
        $t = $wpdb->prefix . 'bk_leadovi';

        $map = array(
            'new'       => 'novi',
            'contacted' => 'kontaktiran',
            'closed'    => 'zatvoren',
            'lost'      => 'izgubljen',
        );

        foreach ( $map as $old => $new ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE $t SET status = %s WHERE status = %s",
                $new, $old
            ) );
        }

        $wpdb->query( "ALTER TABLE $t MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'novi'" );
    },

);
