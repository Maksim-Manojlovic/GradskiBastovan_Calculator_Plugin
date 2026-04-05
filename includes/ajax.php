<?php
/**
 * AJAX — all handlers (frontend + admin)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Rate limit constants ───────────────────────────────────────────────────────
define( 'BK_RATE_LIMIT',    3    ); // max submits
define( 'BK_RATE_WINDOW',   3600 ); // per seconds (1 hour)
define( 'BK_DUPE_WINDOW',   86400 ); // duplicate window (24 hours)

// ── Frontend: submit calculator ───────────────────────────────────────────────

add_action( 'wp_ajax_bk_posalji_email',        'bk_ajax_posalji_email' );
add_action( 'wp_ajax_nopriv_bk_posalji_email', 'bk_ajax_posalji_email' );

function bk_ajax_posalji_email() {
    check_ajax_referer( 'bk_posalji_email', 'nonce' );

    // ── 1. Rate limiting by IP ────────────────────────────────────────────────
    $ip         = bk_get_client_ip();
    $rate_key   = 'bk_rate_' . md5( $ip );
    $hits       = (int) get_transient( $rate_key );

    if ( $hits >= BK_RATE_LIMIT ) {
        wp_send_json_error( array(
            'code'    => 'rate_limited',
            'message' => 'Too many requests. Please try again in an hour.',
        ) );
        return;
    }

    // Increment counter — set expiry only on first hit so the window doesn't reset
    if ( $hits === 0 ) {
        set_transient( $rate_key, 1, BK_RATE_WINDOW );
    } else {
        // get_transient doesn't expose TTL, so we use a companion key for remaining TTL
        $remaining = (int) get_transient( $rate_key . '_exp' );
        set_transient( $rate_key, $hits + 1, $remaining > 0 ? $remaining : BK_RATE_WINDOW );
    }
    // Companion key tracks original window expiry
    if ( $hits === 0 ) {
        set_transient( $rate_key . '_exp', BK_RATE_WINDOW, BK_RATE_WINDOW );
    }

    // ── 2. Sanitize inputs ────────────────────────────────────────────────────
    $email    = sanitize_email( $_POST['email']    ?? '' );
    $usluge   = sanitize_text_field( $_POST['usluge']   ?? '' );
    $cena_str = sanitize_text_field( $_POST['cena']     ?? '' );
    $povrsina = intval( $_POST['povrsina'] ?? 0 );
    $opstina  = sanitize_text_field( $_POST['opstina']  ?? '' );
    $ugovor   = sanitize_text_field( $_POST['ugovor']   ?? '' );
    $hitnost  = sanitize_text_field( $_POST['hitnost']  ?? '' );
    $stavke   = sanitize_textarea_field( $_POST['stavke'] ?? '' );

    if ( ! is_email( $email ) ) {
        wp_send_json_error( array( 'code' => 'invalid_email', 'message' => 'Invalid email address.' ) );
        return;
    }

    // ── 3. Duplicate detection ────────────────────────────────────────────────
    global $wpdb;
    $table      = $wpdb->prefix . 'bk_leadovi';
    $prev_lead  = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, created_at FROM $table
         WHERE email = %s
           AND created_at >= DATE_SUB(NOW(), INTERVAL %d SECOND)
         ORDER BY created_at DESC
         LIMIT 1",
        $email,
        BK_DUPE_WINDOW
    ) );
    $is_duplicate = $prev_lead ? 1 : 0;

    // ── 4. Save lead ──────────────────────────────────────────────────────────
    $wpdb->insert(
        $table,
        array(
            'email'        => $email,
            'usluge'       => $usluge,
            'cena_rsd'     => intval( preg_replace( '/[^0-9]/', '', $cena_str ) ),
            'opstina'      => $opstina,
            'povrsina'     => $povrsina,
            'ugovor'       => $ugovor,
            'hitnost'      => $hitnost,
            'status'       => 'new',
            'is_duplicate' => $is_duplicate,
        ),
        array( '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%d' )
    );
    $new_lead_id = $wpdb->insert_id;

    // ── 5. Send emails ────────────────────────────────────────────────────────
    $podaci  = compact( 'email', 'usluge', 'cena_str', 'povrsina', 'opstina', 'ugovor', 'hitnost', 'stavke' );
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    $em      = bk_get_email();
    $site    = get_bloginfo( 'name' );

    // Always send to user
    wp_mail(
        $email,
        $em['subject_k'] . ' — ' . $site,
        bk_build_email_korisnik( $podaci ),
        $headers
    );

    // Admin email — with duplicate warning if needed
    $admin_subject = $is_duplicate
        ? '⚠️ Duplicate lead — ' . $email
        : '🌿 New lead — ' . $email;

    wp_mail(
        get_option( 'admin_email' ),
        $admin_subject,
        bk_build_email_admin( $podaci, $is_duplicate ? $prev_lead : null ),
        $headers
    );

    wp_send_json_success( 'OK' );
}

// ── Helper: get real client IP ────────────────────────────────────────────────

function bk_get_client_ip() {
    $keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
    foreach ( $keys as $k ) {
        if ( ! empty( $_SERVER[ $k ] ) ) {
            $ip = trim( explode( ',', $_SERVER[ $k ] )[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
        }
    }
    return '0.0.0.0';
}

// ── Admin: update lead status + note ─────────────────────────────────────────

add_action( 'wp_ajax_bk_update_lead', 'bk_ajax_update_lead' );

function bk_ajax_update_lead() {
    check_ajax_referer( 'bk_admin', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Access denied.' );
        return;
    }

    global $wpdb;
    $id     = intval( $_POST['id']    ?? 0 );
    $status = sanitize_key( $_POST['status'] ?? '' );
    $nota   = sanitize_textarea_field( $_POST['nota'] ?? '' );
    $allowed = array( 'new', 'contacted', 'closed', 'lost' );

    if ( $id && in_array( $status, $allowed, true ) ) {
        $wpdb->update(
            $wpdb->prefix . 'bk_leadovi',
            array( 'status' => $status, 'nota' => $nota ),
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        wp_send_json_success();
    }

    wp_send_json_error( 'Invalid data.' );
}

// ── Admin: archive a lead (soft delete) ──────────────────────────────────────

add_action( 'wp_ajax_bk_archive_lead', 'bk_ajax_archive_lead' );

function bk_ajax_archive_lead() {
    check_ajax_referer( 'bk_admin', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( 'Access denied.' ); return; }

    global $wpdb;
    $id = intval( $_POST['id'] ?? 0 );

    if ( $id ) {
        $wpdb->update(
            $wpdb->prefix . 'bk_leadovi',
            array( 'archived_at' => current_time( 'mysql' ) ),
            array( 'id' => $id ),
            array( '%s' ), array( '%d' )
        );
        wp_send_json_success();
    }
    wp_send_json_error( 'Invalid ID.' );
}

// ── Admin: restore an archived lead ──────────────────────────────────────────

add_action( 'wp_ajax_bk_restore_lead', 'bk_ajax_restore_lead' );

function bk_ajax_restore_lead() {
    check_ajax_referer( 'bk_admin', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) { wp_send_json_error( 'Access denied.' ); return; }

    global $wpdb;
    $id = intval( $_POST['id'] ?? 0 );

    if ( $id ) {
        $wpdb->update(
            $wpdb->prefix . 'bk_leadovi',
            array( 'archived_at' => null ),
            array( 'id' => $id ),
            array( null ), array( '%d' )
        );
        wp_send_json_success();
    }
    wp_send_json_error( 'Invalid ID.' );
}

// ── Admin: permanently delete a lead ─────────────────────────────────────────

add_action( 'wp_ajax_bk_delete_lead', 'bk_ajax_delete_lead' );

function bk_ajax_delete_lead() {
    check_ajax_referer( 'bk_admin', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Access denied.' ); return; }

    global $wpdb;
    $id = intval( $_POST['id'] ?? 0 );

    if ( $id ) {
        $wpdb->delete( $wpdb->prefix . 'bk_leadovi', array( 'id' => $id ), array( '%d' ) );
        wp_send_json_success();
    }
    wp_send_json_error( 'Invalid ID.' );
}

// ── Admin: CSV export — tab-separated, respects all filters + archived tab ───

add_action( 'wp_ajax_bk_csv', 'bk_ajax_csv_export' );

function bk_ajax_csv_export() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
    check_ajax_referer( 'bk_admin', 'nonce' );

    global $wpdb;
    $table = $wpdb->prefix . 'bk_leadovi';

    $search     = isset( $_GET['s'] )        ? sanitize_text_field( $_GET['s'] )    : '';
    $f_status   = isset( $_GET['status'] )   ? sanitize_key( $_GET['status'] )      : '';
    $f_area     = isset( $_GET['area'] )     ? sanitize_text_field( $_GET['area'] ) : '';
    $f_archived = isset( $_GET['archived'] ) && $_GET['archived'] === '1';

    $conditions = array();
    $values     = array();

    // Active vs archived
    $conditions[] = $f_archived ? 'archived_at IS NOT NULL' : 'archived_at IS NULL';

    if ( $search !== '' ) {
        $conditions[] = 'email LIKE %s';
        $values[]     = '%' . $wpdb->esc_like( $search ) . '%';
    }
    $allowed_statuses = array( 'new', 'contacted', 'closed', 'lost' );
    if ( $f_status !== '' && in_array( $f_status, $allowed_statuses, true ) ) {
        $conditions[] = 'status = %s';
        $values[]     = $f_status;
    }
    if ( $f_area !== '' ) {
        $conditions[] = 'opstina = %s';
        $values[]     = $f_area;
    }

    $where = 'WHERE ' . implode( ' AND ', $conditions );
    $sql   = $values
        ? $wpdb->prepare( "SELECT * FROM $table $where ORDER BY created_at DESC", ...$values )
        : "SELECT * FROM $table $where ORDER BY created_at DESC";

    $rows = $wpdb->get_results( $sql, ARRAY_A );

    $filename = ( $f_archived ? 'leads-archived-' : 'leads-' ) . date( 'Y-m-d' ) . '.txt';

    header( 'Content-Type: text/tab-separated-values; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    // Use tab as delimiter — no quoting issues in Excel
    $cols = array( 'ID', 'Email', 'Services', 'Value (RSD)', 'Area', 'Size (m2)',
                   'Contract', 'Urgency', 'Status', 'Duplicate', 'Note', 'Submitted', 'Archived' );
    echo implode( "\t", $cols ) . "\r\n";

    foreach ( $rows as $r ) {
        $row = array(
            $r['id'],
            $r['email'],
            $r['usluge'],
            $r['cena_rsd'],
            $r['opstina'],
            $r['povrsina'],
            $r['ugovor'],
            $r['hitnost'],
            $r['status'],
            ! empty( $r['is_duplicate'] ) ? 'yes' : 'no',
            $r['nota'] ?? '',
            $r['created_at'],
            $r['archived_at'] ?? '',
        );
        // Sanitize tabs/newlines within values so they don't break columns
        $row = array_map( function( $v ) {
            return str_replace( array( "\t", "\r", "\n" ), ' ', (string) $v );
        }, $row );
        echo implode( "\t", $row ) . "\r\n";
    }
    exit;
}
