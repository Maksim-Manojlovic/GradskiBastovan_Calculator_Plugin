<?php
/**
 * Admin pages — registracija menija, assets, shared JS/CSS
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Admin meni ────────────────────────────────────────────────────────────────

add_action( 'admin_menu', function () {
    add_menu_page(
        'Kalkulator', 'Kalkulator', 'edit_posts',
        'bk-usluge', 'bk_page_usluge',
        'dashicons-lawn', 58
    );
    add_submenu_page( 'bk-usluge', 'Usluge',    'Usluge',    'manage_options', 'bk-usluge',    'bk_page_usluge'    );
    add_submenu_page( 'bk-usluge', 'Opštine',   'Opštine',   'manage_options', 'bk-opstine',   'bk_page_opstine'   );
    add_submenu_page( 'bk-usluge', 'Emailovi',  'Emailovi',  'manage_options', 'bk-emailovi',  'bk_page_emailovi'  );
    add_submenu_page( 'bk-usluge', 'Analytics',       'Analytics',       'edit_posts', 'bk-analitika', 'bk_page_analitika' );
    add_submenu_page( 'bk-usluge', 'Lead Management', 'Lead Management', 'edit_posts', 'bk-leads',     'bk_page_leads'     );
    // Migrations available via WP-CLI: wp bk migrate
} );

// ── Admin CSS ─────────────────────────────────────────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    $nase_stranice = array(
        'toplevel_page_bk-usluge',
        'kalkulator_page_bk-opstine',
        'kalkulator_page_bk-emailovi',
        'kalkulator_page_bk-analitika',
        'kalkulator_page_bk-leads',
    );

    if ( ! in_array( $hook, $nase_stranice, true ) ) return;

    // Inline admin CSS
    echo bk_admin_css();

    // Chart.js + bkAdmin nonce for analytics + leads
    if ( in_array( $hook, array( 'kalkulator_page_bk-analitika', 'kalkulator_page_bk-leads' ), true ) ) {
        wp_enqueue_script(
            'bk-chartjs',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
            array(), '4.4.1', true
        );
        wp_localize_script( 'bk-chartjs', 'bkAdmin', array(
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'bk_admin' ),
        ) );
    }
} );

// ── Frontend assets ───────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'bk-style',
        BK_URL . 'assets/css/kalkulator.css',
        array(), BK_VERSION
    );
    wp_enqueue_script(
        'bk-script',
        BK_URL . 'assets/js/kalkulator.js',
        array(), BK_VERSION, true
    );
    wp_localize_script( 'bk-script', 'bkAjax', array(
        'url'   => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'bk_posalji_email' ),
    ) );
} );

// ── Shared drag & drop JS (štampa se inline na admin stranicama) ──────────────

function bk_print_drag_js() {
    echo '<script>
function bkDrag(id) {
    var lista = document.getElementById(id);
    if (!lista) return;
    var dragged = null;

    lista.addEventListener("dragstart", function(e) {
        dragged = e.target.closest(".bk-red");
        if (dragged) { dragged.classList.add("dragging"); e.dataTransfer.effectAllowed = "move"; }
    });
    lista.addEventListener("dragend", function() {
        if (dragged) dragged.classList.remove("dragging");
        lista.querySelectorAll(".bk-red").forEach(function(r) { r.classList.remove("drag-over"); });
    });
    lista.addEventListener("dragover", function(e) {
        e.preventDefault();
        var t = e.target.closest(".bk-red");
        if (t && t !== dragged) {
            lista.querySelectorAll(".bk-red").forEach(function(r) { r.classList.remove("drag-over"); });
            t.classList.add("drag-over");
        }
    });
    lista.addEventListener("drop", function(e) {
        e.preventDefault();
        var t = e.target.closest(".bk-red");
        if (t && t !== dragged) {
            var items = Array.from(lista.querySelectorAll(".bk-red"));
            lista.insertBefore(dragged, items.indexOf(dragged) < items.indexOf(t) ? t.nextSibling : t);
            t.classList.remove("drag-over");
        }
    });
}

function bkBindDel(btn, minMsg) {
    btn.addEventListener("click", function() {
        var lista = btn.closest("[id]");
        while (lista && !lista.querySelector(".bk-red")) lista = lista.parentElement;
        if (!lista) return;
        if (lista.querySelectorAll(".bk-red").length <= 1) {
            alert(minMsg || "Mora biti bar jedan red.");
            return;
        }
        this.closest(".bk-red").remove();
    });
}
</script>';
}

// ── Inline admin CSS ──────────────────────────────────────────────────────────

function bk_admin_css() {
    return '<style>
    /* ── Layout ── */
    .bk-w { max-width: 980px }
    .bk-w h1 { display: flex; align-items: center; gap: 10px; margin-bottom: 18px }
    .bk-info { background: #f0f6fc; border-left: 4px solid #2271b1; padding: 11px 15px; margin-bottom: 18px; border-radius: 0 4px 4px 0; font-size: 13px; color: #444 }

    /* ── Drag & drop redovi ── */
    .bk-red { display: grid; gap: 10px; align-items: center; background: #fff; border: 1px solid #dcdcde; border-radius: 6px; padding: 11px 13px; margin-bottom: 9px; box-shadow: 0 1px 2px rgba(0,0,0,.04) }
    .bk-ru  { grid-template-columns: 38px 1fr 78px 100px 100px 38px }
    .bk-ro  { grid-template-columns: 38px 1fr 1fr 80px 38px }
    .bk-red:hover { border-color: #b5b5b5 }
    .bk-red label { display: block; font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 3px }
    .bk-red input, .bk-red select { width: 100%; box-sizing: border-box }
    .bk-drag { cursor: grab; color: #bbb; font-size: 20px; text-align: center; padding-top: 17px; user-select: none }
    .bk-drag:hover { color: #777 }
    .bk-del { background: none; border: none; cursor: pointer; color: #cc1818; font-size: 20px; padding: 0; margin-top: 15px; opacity: .7 }
    .bk-del:hover { opacity: 1 }
    .bk-add { display: inline-flex; align-items: center; gap: 6px; background: #f0f6fc; border: 1px dashed #2271b1; color: #2271b1; padding: 7px 16px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500 }
    .bk-add:hover { background: #e0ecf9 }
    .bk-akcije { display: flex; gap: 12px; align-items: center; margin-top: 14px }
    .bk-red.dragging { opacity: .4; border-style: dashed }
    .bk-red.drag-over { border-color: #2271b1; background: #f0f6fc }

    /* ── Email editor ── */
    .bk-eg { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; align-items: start }
    .bk-fg { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 18px 22px; margin-bottom: 14px }
    .bk-fg h3 { margin: 0 0 14px; font-size: 14px; color: #333; font-weight: 700 }
    .bk-fr { margin-bottom: 12px }
    .bk-fr label { display: block; font-size: 11px; font-weight: 700; color: #666; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .3px }
    .bk-fr input, .bk-fr textarea { width: 100%; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px; padding: 7px 10px; font-size: 13px }
    .bk-fr textarea { height: 72px; resize: vertical }
    .bk-prev { background: #f4f7f4; border: 1px solid #dde8dd; border-radius: 8px; padding: 18px; position: sticky; top: 30px }
    .bk-prev h3 { margin: 0 0 12px; font-size: 14px; color: #333; font-weight: 700 }
    #em-preview { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); font-family: Arial, sans-serif; font-size: 13px }

    /* ── Analitika ── */
    .bk-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); gap: 12px; margin-bottom: 24px }
    .bk-kpi  { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,.05) }
    .bk-kpi.g { border-left: 4px solid #2d9e2d } .bk-kpi.b { border-left: 4px solid #2271b1 }
    .bk-kpi.p { border-left: 4px solid #8b5cf6 } .bk-kpi.a { border-left: 4px solid #f59e0b }
    .bk-kpi.t { border-left: 4px solid #10b981 }
    .bk-kl { font-size: 11px; color: #888; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px }
    .bk-kv { font-size: 26px; font-weight: 800; color: #1a3d1a; line-height: 1 }
    .bk-ks { font-size: 11px; color: #aaa; margin-top: 4px }
    .bk-c2 { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px }
    .bk-c3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px }
    .bk-cb { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.05) }
    .bk-cb h3 { margin: 0 0 12px; font-size: 13px; color: #444; font-weight: 700 }
    .bk-cb canvas { max-height: 210px }
    .bk-tb { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; overflow-x: auto; overflow-y: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.05); margin-bottom: 16px }
    .bk-tb h3 { margin: 0; padding: 12px 16px; font-size: 13px; font-weight: 700; border-bottom: 1px solid #f0f0f0 }
    .bk-tb table { width: 100%; border-collapse: collapse; min-width: 900px }
    .bk-tb th { background: #f8f8f8; padding: 8px 13px; text-align: left; font-size: 11px; color: #888; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap }
    .bk-tb td { padding: 8px 13px; font-size: 12px; color: #333; border-top: 1px solid #f5f5f5 }
    .bk-tb tr:hover td { background: #fafff8 }
    .bk-badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 11px; font-weight: 700 }
    .bk-badge.g  { background: #e8f5e8; color: #2d6a2d } .bk-badge.b  { background: #e8f0fb; color: #2271b1 }
    .bk-badge.a  { background: #fff8e1; color: #b45309 } .bk-badge.gr { background: #f0f0f0; color: #666 }
    .bk-fb { display: flex; flex-wrap: wrap; gap: 7px; align-items: center; margin-bottom: 18px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 9px 13px }
    .bk-fb a { padding: 4px 11px; border-radius: 4px; font-size: 12px; text-decoration: none; font-weight: 600 }
    .bk-ex { display: inline-flex; align-items: center; gap: 5px; background: #1a3d1a; color: #fff !important; padding: 6px 14px; border-radius: 5px; font-size: 12px; text-decoration: none !important; font-weight: 600; margin-left: auto }
    .bk-ex:hover { background: #2d6a2d }
    .bk-nodata { text-align: center; padding: 48px 20px; color: #bbb }
    .bk-nodata span { display: block; font-size: 40px; margin-bottom: 10px }
    .bk-ss { font-size: 12px; border: 1px solid #ddd; border-radius: 4px; padding: 3px 6px; background: #fff }
    .bk-ni { font-size: 12px; border: 1px solid #ddd; border-radius: 4px; padding: 3px 6px; width: 160px }
    .bk-sn { font-size: 11px; background: #2d6a2d; color: #fff; border: none; border-radius: 3px; padding: 3px 8px; cursor: pointer }
    .bk-sn:hover { background: #1a3d1a }
    </style>';
}

// ── Collapsible sections + Lead Management CSS (appended to bk_admin_css output) ──
// NOTE: This is injected via a separate action so it doesn't require editing bk_admin_css()
add_action( 'admin_head', function () {
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'bk-' ) === false ) return;
    ?>
    <style>
    /* ── Collapsible sections ── */
    .bk-collapse-section { background:#fff; border:1px solid #e0e0e0; border-radius:10px; margin-bottom:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05) }
    .bk-collapse-header  { display:flex; justify-content:space-between; align-items:center; padding:12px 18px; cursor:pointer; font-size:13px; font-weight:700; color:#333; user-select:none }
    .bk-collapse-header:hover { background:#fafff8 }
    .bk-chevron { font-size:14px; color:#888; transition:transform .2s }
    .bk-collapse-body { padding:16px 18px 18px; transition:max-height .3s ease, opacity .3s ease; overflow:hidden }

    /* ── Charts zone row 1: Daily (2fr) + Services (1fr) + By area (1fr) ── */
    .bk-charts-row1 { display:grid; grid-template-columns:2fr 1fr 1fr; gap:12px; margin-bottom:0 }
    @media (max-width:900px) { .bk-charts-row1 { grid-template-columns:1fr 1fr } }
    @media (max-width:600px) { .bk-charts-row1 { grid-template-columns:1fr } }

    /* ── Lead section headers ── */
    .bk-leads-section { background:#fff; border:1px solid #e0e0e0; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.05) }
    .bk-leads-section-header { padding:12px 18px; border-bottom:1px solid #f0f0f0 }
    .bk-leads-section-title { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#333 }
    .bk-leads-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0 }
    .bk-leads-dot.active   { background:#2d9e2d; box-shadow:0 0 0 3px rgba(45,158,45,.15) }
    .bk-leads-dot.archived { background:#aaa }
    .bk-leads-count { display:inline-flex; align-items:center; justify-content:center; background:#2d6a2d; color:#fff; font-size:11px; font-weight:700; border-radius:20px; padding:1px 8px; min-width:22px }
    .bk-leads-section .bk-tb { border:none; border-radius:0; box-shadow:none; margin-bottom:0 }
    .bk-leads-section .bk-pagination { padding:10px 16px }

    /* ── Sticky actions column ── */
    .bk-tb table th:last-child,
    .bk-tb table td:last-child { position:sticky; right:0; background:#fff; z-index:1; box-shadow:-2px 0 6px rgba(0,0,0,.06) }
    .bk-tb table th:last-child { background:#f8f8f8 }
    .bk-tb table tr:hover td:last-child { background:#fafff8 }

    /* ── Archived section overrides ── */
    #bk-sec-archived .bk-collapse-body { padding:14px 0 0 }
    #bk-sec-archived .bk-tb table tbody tr { opacity:.85 }
    #bk-sec-archived .bk-tb table tbody tr:hover { opacity:1 }
    .bk-leads-filters { display:flex; flex-wrap:wrap; gap:8px; align-items:center; background:#fff; border:1px solid #e0e0e0; border-radius:8px; padding:10px 14px; margin-bottom:12px }
    .bk-filter-input  { border:1px solid #ddd; border-radius:4px; padding:5px 10px; font-size:13px; min-width:220px }
    .bk-filter-select { border:1px solid #ddd; border-radius:4px; padding:5px 8px; font-size:13px; background:#fff }
    .bk-filter-clear  { font-size:12px; color:#cc1818; text-decoration:none; font-weight:600; padding:4px 8px; border-radius:4px }
    .bk-filter-clear:hover { background:#fff0f0 }
    .bk-leads-meta { font-size:12px; color:#888; margin-bottom:10px; padding:0 2px }

    /* ── Pagination ── */
    .bk-pagination { display:flex; gap:6px; align-items:center; margin-top:14px; flex-wrap:wrap }
    .bk-page-btn   { display:inline-block; padding:5px 11px; border:1px solid #ddd; border-radius:4px; font-size:12px; color:#444; text-decoration:none; background:#fff }
    .bk-page-btn:hover  { border-color:#2d6a2d; color:#2d6a2d }
    .bk-page-btn.active { background:#2d6a2d; color:#fff; border-color:#2d6a2d; font-weight:700 }
    </style>
    <?php
} );
