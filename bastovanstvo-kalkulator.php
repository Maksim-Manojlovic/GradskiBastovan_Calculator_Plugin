<?php
/**
 * Plugin Name: Bastovanstvo Kalkulator
 * Description: Kalkulator cena bastovanskih usluga za Beograd
 * Version:     9.0
 * Author:      Maksim
 * Text Domain: bastovanstvo-kalkulator
 */



if ( ! defined( 'ABSPATH' ) ) exit;

// ── Konstante ────────────────────────────────────────────────────────────────
define( 'BK_VERSION',     '9.0' );
define( 'BK_DIR',         plugin_dir_path( __FILE__ ) );
define( 'BK_URL',         plugin_dir_url( __FILE__ ) );

define( 'BK_OPTION_KEY',  'bk_usluge_lista' );
define( 'BK_OPSTINE_KEY', 'bk_opstine_lista' );
define( 'BK_EMAIL_KEY',   'bk_email_settings' );

// ── Includes ──────────────────────────────────────────────────────────────────
require_once BK_DIR . 'includes/helpers.php';
require_once BK_DIR . 'includes/migrations.php'; // mora pre db.php
require_once BK_DIR . 'includes/db.php';
require_once BK_DIR . 'includes/email.php';
require_once BK_DIR . 'includes/ajax.php';
require_once BK_DIR . 'includes/admin-pages.php';

// Admin stranice
require_once BK_DIR . 'admin/usluge.php';
require_once BK_DIR . 'admin/opstine.php';
require_once BK_DIR . 'admin/emailovi.php';
require_once BK_DIR . 'admin/analitika.php';
require_once BK_DIR . 'admin/leads.php';
require_once BK_DIR . 'includes/enqueue.php';

// ── WP-CLI (samo kad se pokreće iz terminala) ─────────────────────────────────
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once BK_DIR . 'includes/cli.php';
}

// ── Shortcode ─────────────────────────────────────────────────────────────────
require_once BK_DIR . 'includes/shortcode.php';
