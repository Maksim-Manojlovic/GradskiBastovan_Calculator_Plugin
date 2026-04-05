<?php
/**
 * Admin page — Lead Management
 * Two sections: Active Leads + Archived Leads (collapsible)
 * Filters: email search, area, status — applied independently to each section
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function bk_leads_url( $params = array() ) {
    return add_query_arg( array_merge( array( 'page' => 'bk-leads' ), $params ), admin_url( 'admin.php' ) );
}

function bk_page_leads() {
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Access denied.' );

    global $wpdb;
    $table = $wpdb->prefix . 'bk_leadovi';

    // ── Shared filters ────────────────────────────────────────────────────────
    $search   = isset( $_GET['s'] )      ? sanitize_text_field( $_GET['s'] )    : '';
    $f_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] )      : '';
    $f_area   = isset( $_GET['area'] )   ? sanitize_text_field( $_GET['area'] ) : '';
    $a_page   = max( 1, intval( $_GET['apaged']  ?? 1 ) ); // active page
    $r_page   = max( 1, intval( $_GET['rpaged']  ?? 1 ) ); // archived page
    $per_page = 25;

    $status_options = array(
        ''          => 'All statuses',
        'new'       => 'New',
        'contacted' => 'Contacted',
        'closed'    => 'Closed',
        'lost'      => 'Lost',
    );

    // ── Build shared filter conditions ────────────────────────────────────────
    function bk_leads_conditions( $wpdb, $search, $f_status, $f_area ) {
        $c = array(); $v = array();
        if ( $search !== '' )  { $c[] = 'email LIKE %s'; $v[] = '%' . $wpdb->esc_like( $search ) . '%'; }
        $ok = array( 'new', 'contacted', 'closed', 'lost' );
        if ( $f_status !== '' && in_array( $f_status, $ok, true ) ) { $c[] = 'status = %s'; $v[] = $f_status; }
        if ( $f_area   !== '' ) { $c[] = 'opstina = %s'; $v[] = $f_area; }
        return array( $c, $v );
    }

    function bk_leads_query( $wpdb, $table, $archived, $conditions, $values, $limit, $offset ) {
        $all_conds = array_merge(
            array( $archived ? 'archived_at IS NOT NULL' : 'archived_at IS NULL' ),
            $conditions
        );
        $where = 'WHERE ' . implode( ' AND ', $all_conds );
        if ( $values ) {
            return $wpdb->prepare( "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
                ...array_merge( $values, array( $limit, $offset ) ) );
        }
        return $wpdb->prepare( "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset );
    }

    function bk_leads_count( $wpdb, $table, $archived, $conditions, $values ) {
        $all_conds = array_merge(
            array( $archived ? 'archived_at IS NOT NULL' : 'archived_at IS NULL' ),
            $conditions
        );
        $where = 'WHERE ' . implode( ' AND ', $all_conds );
        if ( $values ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table $where", ...$values ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
    }

    list( $conds, $vals ) = bk_leads_conditions( $wpdb, $search, $f_status, $f_area );

    // ── Active leads ──────────────────────────────────────────────────────────
    $a_total  = bk_leads_count( $wpdb, $table, false, $conds, $vals );
    $a_pages  = max( 1, (int) ceil( $a_total / $per_page ) );
    $a_page   = min( $a_page, $a_pages );
    $a_leads  = $wpdb->get_results( bk_leads_query( $wpdb, $table, false, $conds, $vals, $per_page, ( $a_page - 1 ) * $per_page ) );

    // ── Archived leads ────────────────────────────────────────────────────────
    $r_total  = bk_leads_count( $wpdb, $table, true, $conds, $vals );
    $r_pages  = max( 1, (int) ceil( $r_total / $per_page ) );
    $r_page   = min( $r_page, $r_pages );
    $r_leads  = $wpdb->get_results( bk_leads_query( $wpdb, $table, true,  $conds, $vals, $per_page, ( $r_page - 1 ) * $per_page ) );

    // ── All areas for dropdown ────────────────────────────────────────────────
    $all_areas = $wpdb->get_col( "SELECT DISTINCT opstina FROM $table WHERE opstina != '' ORDER BY opstina ASC" );

    $nonce     = wp_create_nonce( 'bk_admin' );
    $csv_base  = add_query_arg( array( 'action' => 'bk_csv', 'nonce' => $nonce, 's' => $search, 'status' => $f_status, 'area' => $f_area ), admin_url( 'admin-ajax.php' ) );
    $has_filter = $search || $f_status || $f_area;
    ?>
    <div class="wrap bk-w" style="max-width:1160px">
        <h1><span>🗂</span> Lead Management</h1>

        <!-- ── Filter bar ── -->
        <form method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
            <input type="hidden" name="page" value="bk-leads">
            <div class="bk-leads-filters">
                <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>"
                       placeholder="Search by email…" class="bk-filter-input" id="bk-search-input">
                <select name="status" class="bk-filter-select" onchange="this.form.submit()">
                    <?php foreach ( $status_options as $val => $label ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $f_status, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="area" class="bk-filter-select" onchange="this.form.submit()">
                    <option value="">All areas</option>
                    <?php foreach ( $all_areas as $area ) : ?>
                    <option value="<?php echo esc_attr( $area ); ?>" <?php selected( $f_area, $area ); ?>><?php echo esc_html( $area ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button">Filter</button>
                <?php if ( $has_filter ) : ?>
                <a href="<?php echo esc_url( bk_leads_url() ); ?>" class="bk-filter-clear">✕ Clear</a>
                <?php endif; ?>
                <div style="margin-left:auto;display:flex;gap:8px">
                    <a href="<?php echo esc_url( add_query_arg( 'archived', '0', $csv_base ) ); ?>" class="bk-ex">⬇ Export active</a>
                    <a href="<?php echo esc_url( add_query_arg( 'archived', '1', $csv_base ) ); ?>" class="bk-ex" style="background:#7a6030">⬇ Export archived</a>
                </div>
            </div>
        </form>

        <?php
        // ── Reusable: render lead table ───────────────────────────────────────
        function bk_render_lead_table( $leads, $status_options, $archived = false ) {
            if ( empty( $leads ) ) {
                echo '<div class="bk-nodata" style="padding:32px 20px"><span>' . ( $archived ? '📦' : '🌱' ) . '</span>' . ( $archived ? 'No archived leads.' : 'No active leads.' ) . '</div>';
                return;
            }
            ?>
            <div class="bk-tb">
                <table>
                    <thead>
                        <tr>
                            <th>#</th><th>Date</th><th>Email</th><th>Services</th>
                            <th>Area</th><th>Size</th><th>Value</th><th>Urgency</th>
                            <?php if ( $archived ) : ?><th>Archived</th><?php endif; ?>
                            <th>Dup.</th><th>Status</th><th>Note</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $leads as $lead ) : ?>
                    <tr data-id="<?php echo $lead->id; ?>" id="bk-row-<?php echo $lead->id; ?>">
                        <td style="color:#aaa;font-size:11px"><?php echo $lead->id; ?></td>
                        <td style="white-space:nowrap;font-size:11px">
                            <?php echo date( 'd.m.Y', strtotime( $lead->created_at ) ); ?><br>
                            <span style="color:#aaa"><?php echo date( 'H:i', strtotime( $lead->created_at ) ); ?></span>
                        </td>
                        <td>
                            <a href="mailto:<?php echo esc_attr( $lead->email ); ?>" style="color:#2271b1;text-decoration:none">
                                <?php echo esc_html( $lead->email ); ?>
                            </a>
                        </td>
                        <td style="font-size:11px;max-width:150px;color:#555"><?php echo esc_html( $lead->usluge ); ?></td>
                        <td style="font-size:12px"><?php echo esc_html( $lead->opstina ); ?></td>
                        <td style="white-space:nowrap;font-size:12px"><?php echo number_format( $lead->povrsina, 0, ',', '.' ); ?> m²</td>
                        <td style="white-space:nowrap">
                            <strong><?php echo number_format( $lead->cena_rsd, 0, ',', '.' ); ?></strong>
                            <span style="font-size:10px;color:#aaa;display:block">RSD</span>
                        </td>
                        <td style="font-size:11px;color:#555"><?php echo esc_html( $lead->hitnost ); ?></td>
                        <?php if ( $archived ) : ?>
                        <td style="white-space:nowrap;font-size:11px;color:#999">
                            <?php echo $lead->archived_at ? date( 'd.m.Y', strtotime( $lead->archived_at ) ) : '—'; ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <?php if ( ! empty( $lead->is_duplicate ) ) : ?>
                            <span class="bk-badge a" title="Duplicate submission within 24h">⚠️</span>
                            <?php else : ?>
                            <span style="color:#ddd">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( ! $archived ) : ?>
                            <select class="bk-ss" data-id="<?php echo $lead->id; ?>">
                                <?php foreach ( array_slice( $status_options, 1 ) as $val => $label ) : ?>
                                <option value="<?php echo $val; ?>" <?php selected( $lead->status, $val ); ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else : ?>
                            <span class="bk-badge <?php echo $lead->status === 'closed' ? 'g' : ( $lead->status === 'contacted' ? 'b' : ( $lead->status === 'lost' ? 'gr' : 'a' ) ); ?>">
                                <?php echo esc_html( $status_options[ $lead->status ] ?? $lead->status ); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="min-width:160px">
                            <?php if ( ! $archived ) : ?>
                            <div style="display:flex;gap:4px;align-items:center">
                                <input type="text" class="bk-ni" data-id="<?php echo $lead->id; ?>"
                                       value="<?php echo esc_attr( $lead->nota ?? '' ); ?>" placeholder="Note…">
                                <button class="bk-sn" data-id="<?php echo $lead->id; ?>" title="Save note">✓</button>
                            </div>
                            <?php else : ?>
                            <span style="font-size:11px;color:#999"><?php echo esc_html( $lead->nota ?? '' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap">
                            <?php if ( ! $archived ) : ?>
                            <button class="bk-btn-archive" data-id="<?php echo $lead->id; ?>"
                                    title="Move to archive" style="background:none;border:none;cursor:pointer;color:#888;font-size:16px;padding:2px 4px">📦</button>
                            <?php else : ?>
                            <button class="bk-btn-restore" data-id="<?php echo $lead->id; ?>"
                                    title="Restore to active" style="background:none;border:none;cursor:pointer;color:#2271b1;font-size:13px;font-weight:700;padding:2px 6px">↩</button>
                            <button class="bk-btn-delete" data-id="<?php echo $lead->id; ?>"
                                    title="Permanently delete" style="background:none;border:none;cursor:pointer;color:#cc1818;font-size:16px;padding:2px 4px">🗑</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        // ── Reusable: pagination ──────────────────────────────────────────────
        function bk_render_pagination( $page, $total_pages, $paged_key, $search, $f_status, $f_area ) {
            if ( $total_pages <= 1 ) return;
            $base = array( 's' => $search, 'status' => $f_status, 'area' => $f_area );
            echo '<div class="bk-pagination">';
            if ( $page > 1 ) {
                echo '<a href="' . esc_url( bk_leads_url( array_merge( $base, array( $paged_key => $page - 1 ) ) ) ) . '" class="bk-page-btn">← Prev</a>';
            }
            for ( $i = max( 1, $page - 2 ); $i <= min( $total_pages, $page + 2 ); $i++ ) {
                $cls = $i === $page ? 'bk-page-btn active' : 'bk-page-btn';
                echo '<a href="' . esc_url( bk_leads_url( array_merge( $base, array( $paged_key => $i ) ) ) ) . '" class="' . $cls . '">' . $i . '</a>';
            }
            if ( $page < $total_pages ) {
                echo '<a href="' . esc_url( bk_leads_url( array_merge( $base, array( $paged_key => $page + 1 ) ) ) ) . '" class="bk-page-btn">Next →</a>';
            }
            echo '</div>';
        }
        ?>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 1: Active Leads                                           -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="bk-leads-section" id="bk-sec-active">
            <div class="bk-leads-section-header">
                <div class="bk-leads-section-title">
                    <span class="bk-leads-dot active"></span>
                    Active Leads
                    <span class="bk-leads-count"><?php echo $a_total; ?></span>
                    <?php if ( $has_filter ) echo '<span style="font-size:11px;color:#888;font-weight:400"> — filtered</span>'; ?>
                </div>
            </div>

            <?php bk_render_lead_table( $a_leads, $status_options, false ); ?>
            <?php bk_render_pagination( $a_page, $a_pages, 'apaged', $search, $f_status, $f_area ); ?>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 2: Archived Leads (collapsible, closed by default)        -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div class="bk-collapse-section <?php echo $r_total > 0 ? '' : 'bk-collapsed'; ?>" id="bk-sec-archived" style="margin-top:8px">
            <div class="bk-collapse-header" onclick="bkToggle('bk-sec-archived')" style="background:#fafaf8">
                <div class="bk-leads-section-title" style="margin:0">
                    <span class="bk-leads-dot archived"></span>
                    Archived Leads
                    <span class="bk-leads-count" style="background:#e0e0e0;color:#666"><?php echo $r_total; ?></span>
                    <?php if ( $has_filter ) echo '<span style="font-size:11px;color:#888;font-weight:400"> — filtered</span>'; ?>
                </div>
                <span class="bk-chevron"><?php echo $r_total > 0 ? '▾' : '▸'; ?></span>
            </div>
            <div class="bk-collapse-body" <?php echo $r_total === 0 ? 'style="display:none"' : ''; ?>>
                <div style="font-size:12px;color:#999;margin-bottom:12px;padding:0 2px">
                    Archived leads are hidden from the active view. You can restore them or permanently delete them here.
                </div>
                <?php bk_render_lead_table( $r_leads, $status_options, true ); ?>
                <?php bk_render_pagination( $r_page, $r_pages, 'rpaged', $search, $f_status, $f_area ); ?>
            </div>
        </div>

    </div><!-- .wrap -->

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var aj = bkAdmin;

        // ── Save status + note (active leads only) ────────────────────────────
        function saveLeadData(id) {
            var row = document.getElementById('bk-row-' + id);
            if (!row) return;
            var fd = new FormData();
            fd.append('action', 'bk_update_lead');
            fd.append('nonce',  aj.nonce);
            fd.append('id',     id);
            var ss = row.querySelector('.bk-ss');
            var ni = row.querySelector('.bk-ni');
            fd.append('status', ss ? ss.value : '');
            fd.append('nota',   ni ? ni.value  : '');

            fetch(aj.url, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        var btn = row.querySelector('.bk-sn');
                        if (btn) { btn.textContent = '✓'; btn.style.background = '#10b981'; setTimeout(() => { btn.style.background = ''; }, 1400); }
                    }
                });
        }

        document.querySelectorAll('.bk-ss').forEach(s => s.addEventListener('change', () => saveLeadData(s.dataset.id)));
        document.querySelectorAll('.bk-sn').forEach(b => b.addEventListener('click',  () => saveLeadData(b.dataset.id)));

        // ── Archive ───────────────────────────────────────────────────────────
        document.querySelectorAll('.bk-btn-archive').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id  = this.dataset.id;
                var row = document.getElementById('bk-row-' + id);
                var fd  = new FormData();
                fd.append('action', 'bk_archive_lead');
                fd.append('nonce',  aj.nonce);
                fd.append('id',     id);

                fetch(aj.url, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            row.style.transition = 'opacity .3s, transform .3s';
                            row.style.opacity    = '0';
                            row.style.transform  = 'translateX(20px)';
                            setTimeout(() => {
                                row.remove();
                                // update active count
                                var cnt = document.querySelector('#bk-sec-active .bk-leads-count');
                                if (cnt) cnt.textContent = Math.max(0, parseInt(cnt.textContent) - 1);
                                // update archived count + open archived section
                                var rcnt = document.querySelector('#bk-sec-archived .bk-leads-count');
                                if (rcnt) rcnt.textContent = parseInt(rcnt.textContent || '0') + 1;
                                var sec = document.getElementById('bk-sec-archived');
                                if (sec && sec.classList.contains('bk-collapsed')) bkToggle('bk-sec-archived');
                            }, 320);
                        }
                    });
            });
        });

        // ── Restore ───────────────────────────────────────────────────────────
        document.querySelectorAll('.bk-btn-restore').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id  = this.dataset.id;
                var row = document.getElementById('bk-row-' + id);
                var fd  = new FormData();
                fd.append('action', 'bk_restore_lead');
                fd.append('nonce',  aj.nonce);
                fd.append('id',     id);

                fetch(aj.url, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            row.style.transition = 'opacity .3s';
                            row.style.opacity    = '0';
                            setTimeout(() => {
                                row.remove();
                                var rcnt = document.querySelector('#bk-sec-archived .bk-leads-count');
                                if (rcnt) rcnt.textContent = Math.max(0, parseInt(rcnt.textContent) - 1);
                                // reload to show in active section
                                window.location.reload();
                            }, 320);
                        }
                    });
            });
        });

        // ── Permanent delete ──────────────────────────────────────────────────
        document.querySelectorAll('.bk-btn-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!confirm('Permanently delete this lead? This cannot be undone.')) return;
                var id  = this.dataset.id;
                var row = document.getElementById('bk-row-' + id);
                var fd  = new FormData();
                fd.append('action', 'bk_delete_lead');
                fd.append('nonce',  aj.nonce);
                fd.append('id',     id);

                fetch(aj.url, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            row.style.transition = 'opacity .3s, background .3s';
                            row.style.background = '#fff0f0';
                            row.style.opacity    = '0';
                            setTimeout(() => {
                                row.remove();
                                var rcnt = document.querySelector('#bk-sec-archived .bk-leads-count');
                                if (rcnt) rcnt.textContent = Math.max(0, parseInt(rcnt.textContent) - 1);
                            }, 320);
                        }
                    });
            });
        });

        // ── Live search (debounced) ───────────────────────────────────────────
        var si = document.getElementById('bk-search-input');
        if (si) {
            var timer;
            si.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => si.form.submit(), 500);
            });
        }
    });
    </script>
    <?php
}
