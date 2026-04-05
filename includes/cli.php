<?php
/**
 * WP-CLI komande za Bastovanstvo Kalkulator
 *
 * Dostupne komande:
 *   wp bk migrate              — pokreni sve pending migracije
 *   wp bk migrate:rollback     — rollback poslednje (ili N) migracija
 *   wp bk migrate:status       — prikaz statusa svih migracija
 *   wp bk migrate:fresh        — rollback SVIH + pokreni sve iznova (pažnja!)
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) return;

/**
 * Upravljanje bazom podataka Bastovanstvo Kalkulatora.
 */
class BK_CLI extends WP_CLI_Command {

    // ── wp bk migrate ─────────────────────────────────────────────────────────

    /**
     * Pokreni sve pending migracije.
     *
     * ## EXAMPLES
     *
     *   wp bk migrate
     *
     * @subcommand migrate
     */
    public function migrate( $args, $assoc_args ) {
        $pending = $this->get_pending();

        if ( empty( $pending ) ) {
            WP_CLI::success( 'Nema pending migracija — sve je ažurno.' );
            return;
        }

        WP_CLI::log( sprintf( 'Pronađeno %d pending %s:', count( $pending ), count( $pending ) === 1 ? 'migracija' : 'migracija' ) );
        foreach ( $pending as $naziv ) {
            WP_CLI::log( '  · ' . $naziv );
        }

        WP_CLI::log( '' );

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $pokrenute = array();

        foreach ( $pending as $naziv ) {
            $putanja   = BK_MIGRATIONS_DIR . $naziv . '.php';
            $migracija = require $putanja;

            if ( ! is_array( $migracija ) || ! isset( $migracija['up'] ) || ! is_callable( $migracija['up'] ) ) {
                WP_CLI::warning( "Nevalidan format: $naziv — preskočeno." );
                continue;
            }

            WP_CLI::log( "  Pokrećem: $naziv ..." );

            try {
                call_user_func( $migracija['up'] );
                bk_mark_migration_ran( $naziv );
                $pokrenute[] = $naziv;
                WP_CLI::log( WP_CLI::colorize( "  %G✓ OK%n" ) );
            } catch ( Exception $e ) {
                WP_CLI::error( "Migracija $naziv nije prošla: " . $e->getMessage(), false );
                WP_CLI::error( 'Zaustavljeno. Prethodno pokrenute migracije su sačuvane.', true );
                return;
            }
        }

        WP_CLI::log( '' );
        WP_CLI::success( sprintf( 'Pokrenuto %d %s.', count( $pokrenute ), count( $pokrenute ) === 1 ? 'migracija' : 'migracija' ) );
    }

    // ── wp bk migrate:rollback ────────────────────────────────────────────────

    /**
     * Rollback poslednje migracije.
     *
     * ## OPTIONS
     *
     * [--step=<broj>]
     * : Broj migracija za rollback. Default: 1
     *
     * [--all]
     * : Rollback SVIH pokrenutih migracija.
     *
     * ## EXAMPLES
     *
     *   wp bk migrate:rollback
     *   wp bk migrate:rollback --step=3
     *   wp bk migrate:rollback --all
     *
     * @subcommand migrate:rollback
     */
    public function migrate_rollback( $args, $assoc_args ) {
        $pokrenute = bk_get_ran_migrations();

        if ( empty( $pokrenute ) ) {
            WP_CLI::warning( 'Nema pokrenutih migracija za rollback.' );
            return;
        }

        // Odredi koliko rollback-ovati
        if ( isset( $assoc_args['all'] ) ) {
            $koraka = count( $pokrenute );
        } else {
            $koraka = isset( $assoc_args['step'] ) ? max( 1, intval( $assoc_args['step'] ) ) : 1;
        }

        $za_rollback = array_slice( array_reverse( $pokrenute ), 0, $koraka );

        WP_CLI::log( sprintf( 'Rollback %d %s:', count( $za_rollback ), count( $za_rollback ) === 1 ? 'migracije' : 'migracija' ) );
        foreach ( $za_rollback as $naziv ) {
            WP_CLI::log( '  · ' . $naziv );
        }
        WP_CLI::log( '' );

        // Potvrda za --all
        if ( isset( $assoc_args['all'] ) ) {
            WP_CLI::confirm( 'Ovo će rollback-ovati SVE migracije. Nastavi?' );
        }

        foreach ( $za_rollback as $naziv ) {
            $putanja   = BK_MIGRATIONS_DIR . $naziv . '.php';

            if ( ! file_exists( $putanja ) ) {
                WP_CLI::warning( "Fajl nije pronađen: $naziv — označavam kao unran." );
                bk_mark_migration_unran( $naziv );
                continue;
            }

            $migracija = require $putanja;
            WP_CLI::log( "  Rollback: $naziv ..." );

            if ( isset( $migracija['down'] ) && is_callable( $migracija['down'] ) ) {
                try {
                    call_user_func( $migracija['down'] );
                    WP_CLI::log( WP_CLI::colorize( '  %G✓ down() izvršen%n' ) );
                } catch ( Exception $e ) {
                    WP_CLI::error( "Rollback $naziv nije prošao: " . $e->getMessage(), true );
                    return;
                }
            } else {
                WP_CLI::log( WP_CLI::colorize( '  %Ynema down() — samo označavam kao pending%n' ) );
            }

            bk_mark_migration_unran( $naziv );
        }

        WP_CLI::log( '' );
        WP_CLI::success( sprintf( 'Rollback završen za %d %s.', count( $za_rollback ), count( $za_rollback ) === 1 ? 'migraciju' : 'migracija' ) );
    }

    // ── wp bk migrate:status ──────────────────────────────────────────────────

    /**
     * Prikaži status svih migracija.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Izlazni format. Opcije: table, json, csv. Default: table
     *
     * ## EXAMPLES
     *
     *   wp bk migrate:status
     *   wp bk migrate:status --format=json
     *
     * @subcommand migrate:status
     */
    public function migrate_status( $args, $assoc_args ) {
        $status_lista = bk_get_migration_status();
        $format       = $assoc_args['format'] ?? 'table';

        if ( empty( $status_lista ) ) {
            WP_CLI::warning( 'Nema migracionih fajlova u ' . BK_MIGRATIONS_DIR );
            return;
        }

        // Pripremi podatke za tabelu
        $redovi = array_map( function ( $m ) {
            $putanja   = BK_MIGRATIONS_DIR . $m['naziv'] . '.php';
            $migracija = file_exists( $putanja ) ? require $putanja : array();
            return array(
                'naziv'    => $m['naziv'],
                'status'   => $m['status'] === 'ran' ? '✓ ran' : '⏳ pending',
                'has_down' => ( isset( $migracija['down'] ) && is_callable( $migracija['down'] ) ) ? 'da' : 'ne',
            );
        }, $status_lista );

        $ran_cnt     = count( array_filter( $status_lista, fn( $m ) => $m['status'] === 'ran' ) );
        $pending_cnt = count( $status_lista ) - $ran_cnt;

        WP_CLI\Utils\format_items( $format, $redovi, array( 'naziv', 'status', 'has_down' ) );

        WP_CLI::log( '' );
        WP_CLI::log( sprintf(
            'Ukupno: %d | Pokrenute: %d | Pending: %d',
            count( $status_lista ), $ran_cnt, $pending_cnt
        ) );

        if ( $pending_cnt > 0 ) {
            WP_CLI::log( WP_CLI::colorize( "%YSavet: pokreni 'wp bk migrate' da izvršiš pending migracije.%n" ) );
        }
    }

    // ── wp bk migrate:fresh ───────────────────────────────────────────────────

    /**
     * Rollback SVIH migracija, pa pokreni sve iznova.
     * PAŽNJA: Ovo briše sve podatke u tabelama koje migracije kontrolišu!
     *
     * ## EXAMPLES
     *
     *   wp bk migrate:fresh
     *
     * @subcommand migrate:fresh
     */
    public function migrate_fresh( $args, $assoc_args ) {
        WP_CLI::confirm(
            WP_CLI::colorize( '%RPAŽNJA: Ovo će rollback-ovati SVE migracije i pokrenuti ih iznova. Podaci mogu biti izgubljeni!%n Nastavi?' )
        );

        WP_CLI::log( '→ Rollback svih migracija...' );
        $this->migrate_rollback( array(), array( 'all' => true ) );

        WP_CLI::log( '' );
        WP_CLI::log( '→ Pokretanje svih migracija...' );
        $this->migrate( array(), array() );
    }

    // ── Pomoćna metoda ────────────────────────────────────────────────────────

    private function get_pending() {
        $sve       = bk_get_all_migrations();
        $pokrenute = bk_get_ran_migrations();
        return array_values( array_diff( $sve, $pokrenute ) );
    }
}

// Registruj komandu
WP_CLI::add_command( 'bk', 'BK_CLI' );
