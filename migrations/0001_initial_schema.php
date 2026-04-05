<?php
/**
 * Migration 0001 — Initial schema
 *
 * Kreira osnovnu tabelu bk_leadovi sa svim kolonama koje su
 * postojale od v6/v7 plugina (email, usluge, cena, lokacija, status, nota).
 */

return array(

    'up' => function () {
        global $wpdb;

        $tabela  = $wpdb->prefix . 'bk_leadovi';
        $charset = $wpdb->get_charset_collate();

        dbDelta( "CREATE TABLE IF NOT EXISTS $tabela (
            id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
            email      VARCHAR(200)     NOT NULL,
            usluge     TEXT             NOT NULL,
            cena_rsd   INT UNSIGNED     NOT NULL DEFAULT 0,
            opstina    VARCHAR(100)     NOT NULL DEFAULT '',
            povrsina   INT UNSIGNED     NOT NULL DEFAULT 0,
            ugovor     VARCHAR(100)     NOT NULL DEFAULT '',
            hitnost    VARCHAR(100)     NOT NULL DEFAULT '',
            status     VARCHAR(30)      NOT NULL DEFAULT 'new',
            nota       TEXT,
            created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_created (created_at),
            KEY idx_status  (status)
        ) $charset;" );
    },

    'down' => function () {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bk_leadovi" );
    },

);
