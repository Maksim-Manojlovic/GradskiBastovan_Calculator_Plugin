<?php
/**
 * Migration Runner
 *
 * Kako funkcioniše:
 *  - Migracije su PHP fajlovi u /migrations/ sa formatom: 0001_naziv.php
 *  - Svaki fajl vraća array sa 'up' (obavezno) i 'down' (opciono) callable
 *  - Lista pokrenutih migracija čuva se u wp_options pod 'bk_ran_migrations'
 *  - Pri aktivaciji i plugins_loaded — pokreću se samo pending migracije
 *  - Redosled je uvek numerički po prefiksu (0001, 0002, ...)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BK_MIGRATIONS_DIR', BK_DIR . 'migrations/' );
define( 'BK_MIGRATIONS_KEY', 'bk_ran_migrations' );

// ── Glavni runner ─────────────────────────────────────────────────────────────

/**
 * Pokreni sve pending migracije.
 * Vraća array sa imenima pokrenutih migracija (prazno ako ništa nije pending).
 *
 * @return string[]
 */
function bk_run_migrations() {
    $sve      = bk_get_all_migrations();
    $pokrenute = bk_get_ran_migrations();
    $pending   = array_diff( $sve, $pokrenute );

    if ( empty( $pending ) ) {
        return array();
    }

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $upravo_pokrenute = array();

    foreach ( $pending as $naziv ) {
        $putanja = BK_MIGRATIONS_DIR . $naziv . '.php';

        if ( ! file_exists( $putanja ) ) {
            continue;
        }

        $migracija = require $putanja;

        if ( ! is_array( $migracija ) || ! isset( $migracija['up'] ) || ! is_callable( $migracija['up'] ) ) {
            // Loš format — preskoči, ali loguj
            error_log( "[BK Migration] Nevalidan format: $naziv" );
            continue;
        }

        try {
            call_user_func( $migracija['up'] );
            bk_mark_migration_ran( $naziv );
            $upravo_pokrenute[] = $naziv;
        } catch ( Exception $e ) {
            error_log( "[BK Migration] GREŠKA u $naziv: " . $e->getMessage() );
            // Zaustavi — ne pokreći sledeće ako ova nije prošla
            break;
        }
    }

    return $upravo_pokrenute;
}

/**
 * Rollback poslednje N migracija.
 *
 * @param int $koraka  Broj migracija za rollback (default: 1)
 * @return string[]    Vraća imena rollback-ovanih migracija
 */
function bk_rollback_migrations( $koraka = 1 ) {
    $pokrenute = bk_get_ran_migrations();

    if ( empty( $pokrenute ) ) {
        return array();
    }

    // Rollback ide unazad — od poslednje ka prvoj
    $za_rollback      = array_slice( array_reverse( $pokrenute ), 0, $koraka );
    $rollback_done    = array();

    foreach ( $za_rollback as $naziv ) {
        $putanja = BK_MIGRATIONS_DIR . $naziv . '.php';

        if ( ! file_exists( $putanja ) ) {
            continue;
        }

        $migracija = require $putanja;

        if ( isset( $migracija['down'] ) && is_callable( $migracija['down'] ) ) {
            try {
                call_user_func( $migracija['down'] );
            } catch ( Exception $e ) {
                error_log( "[BK Migration] Rollback GREŠKA u $naziv: " . $e->getMessage() );
                break;
            }
        }

        bk_mark_migration_unran( $naziv );
        $rollback_done[] = $naziv;
    }

    return $rollback_done;
}

// ── Pomoćne funkcije ──────────────────────────────────────────────────────────

/**
 * Vrati listu svih migracionih fajlova (sortirano numerički).
 *
 * @return string[]  Npr. ['0001_initial_schema', '0002_add_status_column']
 */
function bk_get_all_migrations() {
    if ( ! is_dir( BK_MIGRATIONS_DIR ) ) {
        return array();
    }

    $fajlovi = glob( BK_MIGRATIONS_DIR . '[0-9][0-9][0-9][0-9]_*.php' );

    if ( empty( $fajlovi ) ) {
        return array();
    }

    $nazivi = array_map( function ( $putanja ) {
        return basename( $putanja, '.php' );
    }, $fajlovi );

    sort( $nazivi ); // Numerički redosled
    return $nazivi;
}

/**
 * Vrati listu već pokrenutih migracija.
 *
 * @return string[]
 */
function bk_get_ran_migrations() {
    return (array) get_option( BK_MIGRATIONS_KEY, array() );
}

/**
 * Zapamti migraciju kao pokrenuta.
 *
 * @param string $naziv
 */
function bk_mark_migration_ran( $naziv ) {
    $lista   = bk_get_ran_migrations();
    $lista[] = $naziv;
    update_option( BK_MIGRATIONS_KEY, array_unique( $lista ) );
}

/**
 * Ukloni migraciju iz liste pokrenutih (za rollback).
 *
 * @param string $naziv
 */
function bk_mark_migration_unran( $naziv ) {
    $lista = bk_get_ran_migrations();
    $lista = array_values( array_diff( $lista, array( $naziv ) ) );
    update_option( BK_MIGRATIONS_KEY, $lista );
}

/**
 * Vrati status svih migracija — korisno za admin prikaz.
 *
 * @return array[]  [['naziv' => '...', 'status' => 'ran'|'pending'], ...]
 */
function bk_get_migration_status() {
    $sve       = bk_get_all_migrations();
    $pokrenute = bk_get_ran_migrations();
    $status    = array();

    foreach ( $sve as $naziv ) {
        $status[] = array(
            'naziv'  => $naziv,
            'status' => in_array( $naziv, $pokrenute, true ) ? 'ran' : 'pending',
        );
    }

    return $status;
}
