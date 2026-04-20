<?php
/**
 * Email — helper funkcije za gradnju HTML emailova
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Tabela detalja (zajednička za oba emaila) ─────────────────────────────────

function bk_email_detalji( $usluge, $povrsina, $opstina, $ugovor, $hitnost ) {
    return '
    <table style="width:100%;border-collapse:collapse;margin:14px 0">
        <tr style="background:#f5f5f5">
            <td style="padding:9px 14px;font-weight:600;color:#555;width:40%">Usluge</td>
            <td style="padding:9px 14px">'     . esc_html( $usluge )   . '</td>
        </tr>
        <tr>
            <td style="padding:9px 14px;font-weight:600;color:#555">Površina</td>
            <td style="padding:9px 14px">'     . esc_html( $povrsina ) . ' m²</td>
        </tr>
        <tr style="background:#f5f5f5">
            <td style="padding:9px 14px;font-weight:600;color:#555">Opština</td>
            <td style="padding:9px 14px">'     . esc_html( $opstina )  . '</td>
        </tr>
        <tr>
            <td style="padding:9px 14px;font-weight:600;color:#555">Ugovor</td>
            <td style="padding:9px 14px">'     . esc_html( $ugovor )   . '</td>
        </tr>
        <tr style="background:#f5f5f5">
            <td style="padding:9px 14px;font-weight:600;color:#555">Hitnost</td>
            <td style="padding:9px 14px">'     . esc_html( $hitnost )  . '</td>
        </tr>
    </table>';
}

// ── Razrada stavki (iz JS-a dolaze u formatu "Naziv|Cena") ────────────────────

function bk_email_stavke_html( $stavke_raw, $boja ) {
    $html = '';
    foreach ( explode( "\n", trim( $stavke_raw ) ) as $red ) {
        $delovi = explode( '|', $red );
        if ( count( $delovi ) === 2 ) {
            $html .= '<tr>
                <td style="padding:8px 14px;color:#444">'                                                     . esc_html( trim( $delovi[0] ) ) . '</td>
                <td style="padding:8px 14px;text-align:right;font-weight:600;color:' . esc_attr( $boja ) . '">' . esc_html( trim( $delovi[1] ) ) . '</td>
            </tr>';
        }
    }
    return $html;
}

// ── Email korisniku ───────────────────────────────────────────────────────────

function bk_build_email_korisnik( $podaci ) {
    $em        = bk_get_email();
    $boja      = $em['boja'];
    $site      = get_bloginfo( 'name' );
    $stavke_tr = bk_email_stavke_html( $podaci['stavke'], $boja );
    $detalji   = bk_email_detalji( $podaci['usluge'], $podaci['povrsina'], $podaci['opstina'], $podaci['ugovor'], $podaci['hitnost'] );

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f4f7f4;font-family:Arial,sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:26px 14px;background:#f4f7f4">
    <tr><td align="center">
    <table width="580" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)">

        <tr><td style="background:linear-gradient(135deg,' . esc_attr($boja) . ',' . esc_attr($boja) . 'cc);padding:30px 34px;text-align:center">
            <div style="font-size:34px;margin-bottom:7px">🌿</div>
            <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700">Vaša procena cene</h1>
            <p style="margin:5px 0 0;color:rgba(255,255,255,.75);font-size:13px">Bastovanske usluge · Beograd</p>
        </td></tr>

        <tr><td style="padding:30px 34px">
            <p style="margin:0 0 16px;color:#444;font-size:14px;line-height:1.6">' . esc_html( $em['uvod_k'] ) . '</p>

            <div style="background:#f0f7f0;border-left:4px solid ' . esc_attr($boja) . ';padding:16px 20px;margin-bottom:20px;border-radius:0 8px 8px 0">
                <div style="font-size:11px;color:' . esc_attr($boja) . ';font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">Ukupna procena</div>
                <div style="font-size:30px;font-weight:800;color:' . esc_attr($boja) . '">≈ ' . esc_html( $podaci['cena_str'] ) . '</div>
                <div style="font-size:11px;color:#888;margin-top:3px">bez PDV-a · okvirna cena</div>
            </div>

            <div style="margin-bottom:18px">
                <div style="font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px">Razrada po uslugama</div>
                <table style="width:100%;border-collapse:collapse;border:1px solid #eee">
                    <thead><tr style="background:' . esc_attr($boja) . '">
                        <th style="padding:8px 14px;text-align:left;color:#fff;font-size:12px">Usluga</th>
                        <th style="padding:8px 14px;text-align:right;color:#fff;font-size:12px">Cena</th>
                    </tr></thead>
                    <tbody>' . $stavke_tr . '</tbody>
                </table>
            </div>

            ' . $detalji . '

            <div style="background:#fffbf0;border:1px solid #f0d060;border-radius:8px;padding:13px 17px;margin-bottom:20px;font-size:12px;color:#7a6000">
                ⚠️ ' . esc_html( $em['napomena_k'] ) . '
            </div>

            <div style="text-align:center">
                <a href="' . esc_attr( $em['cta_url'] ) . '" style="display:inline-block;background:' . esc_attr($boja) . ';color:#fff;text-decoration:none;padding:12px 30px;border-radius:8px;font-size:14px;font-weight:700">
                    ' . esc_html( $em['cta_tekst'] ) . '
                </a>
                ' . ( strpos( $em['cta_url'], 'tel:' ) === 0 ? '<p style="margin:10px 0 0;font-size:16px;font-weight:700;color:#333">' . esc_html( substr( $em['cta_url'], 4 ) ) . '</p>' : '' ) . '
            </div>
        </td></tr>

        <tr><td style="background:#f9f9f9;padding:14px 34px;text-align:center;border-top:1px solid #eee">
            <p style="margin:0;font-size:11px;color:#aaa">' . esc_html($site) . ' · ' . esc_html( $em['footer_tekst'] ) . '</p>
        </td></tr>

    </table>
    </td></tr></table>
    </body></html>';
}

// ── Email adminu ──────────────────────────────────────────────────────────────

function bk_build_email_admin( $podaci, $prev_lead = null ) {
    $detalji      = bk_email_detalji( $podaci['usluge'], $podaci['povrsina'], $podaci['opstina'], $podaci['ugovor'], $podaci['hitnost'] );
    $is_duplicate = $prev_lead !== null;

    // Header color + label depending on duplicate status
    $header_bg    = $is_duplicate ? '#7a3800' : '#1a3d1a';
    $header_label = $is_duplicate ? '⚠️ Duplicate lead from calculator' : '📬 New lead from calculator';

    // Duplicate warning block
    $dupe_block = '';
    if ( $is_duplicate ) {
        $prev_date    = date( 'd.m.Y. H:i', strtotime( $prev_lead->created_at ) );
        $leads_url    = admin_url( 'admin.php?page=bk-leads&s=' . rawurlencode( $podaci['email'] ) );
        $dupe_block   = '
            <div style="background:#fff8f0;border:1.5px solid #f0a060;border-radius:8px;padding:14px 18px;margin-bottom:18px">
                <div style="font-size:12px;font-weight:700;color:#7a3800;margin-bottom:6px">⚠️ Duplicate submission detected</div>
                <div style="font-size:13px;color:#555;line-height:1.6">
                    This email already submitted within the last 24 hours
                    (previous lead: <strong>' . esc_html( $prev_date ) . '</strong>).
                </div>
                <div style="margin-top:10px">
                    <a href="' . esc_attr( $leads_url ) . '"
                       style="display:inline-block;background:#7a3800;color:#fff;text-decoration:none;padding:7px 16px;border-radius:6px;font-size:12px;font-weight:700">
                        View all leads for this email →
                    </a>
                </div>
            </div>';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:26px 14px">
    <tr><td align="center">
    <table width="580" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)">

        <tr><td style="background:' . esc_attr( $header_bg ) . ';padding:22px 34px">
            <h1 style="margin:0;color:#fff;font-size:18px;font-weight:700">' . $header_label . '</h1>
            <p style="margin:4px 0 0;color:rgba(255,255,255,.5);font-size:12px">' . date( 'd.m.Y. H:i' ) . '</p>
        </td></tr>

        <tr><td style="padding:26px 34px">
            ' . $dupe_block . '
            <div style="background:#f0f7f0;border-radius:8px;padding:13px 17px;margin-bottom:18px">
                <div style="font-size:11px;color:#2d6a2d;font-weight:700;text-transform:uppercase;margin-bottom:2px">User email</div>
                <div style="font-size:17px;font-weight:700;color:#222">' . esc_html( $podaci['email'] ) . '</div>
            </div>
            ' . ( ! empty( $podaci['telefon'] ) ? '
            <div style="background:#f0f7f0;border-radius:8px;padding:13px 17px;margin-bottom:18px">
                <div style="font-size:11px;color:#2d6a2d;font-weight:700;text-transform:uppercase;margin-bottom:2px">Telefon</div>
                <div style="font-size:17px;font-weight:700;color:#222"><a href="tel:' . esc_attr( $podaci['telefon'] ) . '" style="color:#222;text-decoration:none">' . esc_html( $podaci['telefon'] ) . '</a></div>
            </div>' : '' ) . '
            <div style="background:#f9f9f9;border-radius:8px;padding:13px 17px;margin-bottom:18px">
                <div style="font-size:11px;color:#888;font-weight:700;text-transform:uppercase;margin-bottom:2px">Estimated value</div>
                <div style="font-size:24px;font-weight:800;color:#2d6a2d">≈ ' . esc_html( $podaci['cena_str'] ) . '</div>
            </div>
            ' . $detalji . '
        </td></tr>

    </table>
    </td></tr></table>
    </body></html>';
}
