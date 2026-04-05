<?php
/**
 * DB — aktivacija plugina i pokretanje migracija
 *
 * Logika baze je sada u /migrations/ fajlovima.
 * Ovaj fajl samo orkestrira kada se migracije pokreću.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Aktivacija plugina ────────────────────────────────────────────────────────

register_activation_hook(
    BK_DIR . 'bastovanstvo-kalkulator.php',
    'bk_on_activate'
);

function bk_on_activate() {
    // Inicijalni podaci u wp_options (samo ako ne postoje)
    if ( ! get_option( BK_OPTION_KEY ) )  update_option( BK_OPTION_KEY,  bk_default_usluge() );
    if ( ! get_option( BK_OPSTINE_KEY ) ) update_option( BK_OPSTINE_KEY, bk_default_opstine() );
    if ( ! get_option( BK_EMAIL_KEY ) )   update_option( BK_EMAIL_KEY,   bk_default_email() );

    // Pokreni sve pending migracije
    bk_run_migrations();
}

// ── Auto-migracija pri update-u ───────────────────────────────────────────────
// Svaki put kad se WordPress učita, proveravamo ima li novih migracija.
// bk_run_migrations() je idempotentna — ne pokreće već pokrenute.

add_action( 'plugins_loaded', 'bk_run_migrations' );
