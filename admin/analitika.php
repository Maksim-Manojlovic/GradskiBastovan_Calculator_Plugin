<?php
/**
 * Admin page — Analytics
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function bk_page_analitika() {
    global $wpdb;

    $table  = $wpdb->prefix . 'bk_leadovi';
    $period = isset( $_GET['period'] ) ? sanitize_key( $_GET['period'] ) : 'all';

    $where_map = array(
        'all' => '',
        '7d'  => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        '30d' => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        '90d' => "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)",
        'mtd' => "WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())",
    );
    $w = $where_map[ $period ] ?? '';

    // ── KPIs ─────────────────────────────────────────────────────────────────
    $total    = (int) $wpdb->get_var( "SELECT COUNT(*)      FROM $table $w" );
    $avg      = (int) $wpdb->get_var( "SELECT AVG(cena_rsd) FROM $table $w" );
    $sum      = (int) $wpdb->get_var( "SELECT SUM(cena_rsd) FROM $table $w" );
    $today    = (int) $wpdb->get_var( "SELECT COUNT(*)      FROM $table WHERE DATE(created_at) = CURDATE()" );
    $new_c    = (int) $wpdb->get_var( "SELECT COUNT(*)      FROM $table " . ( $w ? $w . " AND status='new'" : "WHERE status='new'" ) );

    $best     = $wpdb->get_row( "SELECT DATE(created_at) as d, COUNT(*) as br FROM $table $w GROUP BY d ORDER BY br DESC LIMIT 1" );
    $best_day = $best ? date( 'd.m.Y', strtotime( $best->d ) ) . ' (' . $best->br . '×)' : '—';

    // ── Chart data ────────────────────────────────────────────────────────────
    $dw      = $period === 'all' ? "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" : $w;
    $daily   = $wpdb->get_results( "SELECT DATE(created_at) as d, COUNT(*) as br FROM $table $dw GROUP BY d ORDER BY d ASC" );
    $monthly = $wpdb->get_results( "SELECT DATE_FORMAT(created_at,'%Y-%m') as m, COUNT(*) as br FROM $table $w GROUP BY m ORDER BY m ASC LIMIT 12" );
    $weekly  = $wpdb->get_results( "SELECT YEARWEEK(created_at,1) as wk, MIN(DATE(created_at)) as st, COUNT(*) as br FROM $table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK) GROUP BY wk ORDER BY wk ASC" );

    $all_leads  = $wpdb->get_results( "SELECT usluge FROM $table $w" );
    $usluge_cnt = array();
    foreach ( $all_leads as $r ) {
        foreach ( array_map( 'trim', explode( ',', $r->usluge ) ) as $u ) {
            if ( $u ) $usluge_cnt[ $u ] = ( $usluge_cnt[ $u ] ?? 0 ) + 1;
        }
    }
    arsort( $usluge_cnt );

    $hours_db  = $wpdb->get_results( "SELECT HOUR(created_at) as h, COUNT(*) as br FROM $table $w GROUP BY h ORDER BY h ASC" );
    $hours_arr = array_fill( 0, 24, 0 );
    foreach ( $hours_db as $s ) $hours_arr[ (int) $s->h ] = (int) $s->br;

    $areas_data = $wpdb->get_results(
        "SELECT opstina, COUNT(*) as br FROM $table " .
        ( $w ? $w . " AND opstina != ''" : "WHERE opstina != ''" ) .
        " GROUP BY opstina ORDER BY br DESC LIMIT 10"
    );

    $recent = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC LIMIT 10" );

    $period_labels  = array( 'all' => 'All time', '7d' => '7 days', '30d' => '30 days', '90d' => '90 days', 'mtd' => 'This month' );
    $status_options = array( 'new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed', 'lost' => 'Lost' );
    $csv_url        = add_query_arg( array( 'action' => 'bk_csv', 'nonce' => wp_create_nonce( 'bk_admin' ) ), admin_url( 'admin-ajax.php' ) );
    $leads_url      = admin_url( 'admin.php?page=bk-leads' );
    ?>
    <div class="wrap bk-w" style="max-width:1140px">
        <h1><span>📊</span> Analytics</h1>

        <!-- Period filter + actions -->
        <div class="bk-fb">
            <span style="font-size:12px;font-weight:700;color:#666">Period:</span>
            <?php foreach ( $period_labels as $v => $l ) : ?>
            <a href="<?php echo admin_url( 'admin.php?page=bk-analitika&period=' . $v ); ?>"
               style="background:<?php echo $period === $v ? '#2d6a2d' : '#f0f0f0'; ?>;color:<?php echo $period === $v ? '#fff' : '#555'; ?>;">
                <?php echo esc_html( $l ); ?>
            </a>
            <?php endforeach; ?>
            <a href="<?php echo esc_url( $leads_url ); ?>" style="background:#f0f0f0;color:#555;">🗂 Lead Management</a>
            <a href="<?php echo esc_url( $csv_url ); ?>" class="bk-ex">⬇ Export CSV</a>
        </div>

        <?php if ( $total === 0 ) : ?>
        <div class="bk-nodata"><span>🌱</span>No leads for this period.</div>
        <?php else : ?>

        <!-- ── Section: KPI cards (open by default) ── -->
        <div class="bk-collapse-section" id="sec-kpi">
            <div class="bk-collapse-header" onclick="bkToggle('sec-kpi')">
                <span>📈 Overview</span>
                <span class="bk-chevron">▾</span>
            </div>
            <div class="bk-collapse-body">
                <div class="bk-kpis">
                    <div class="bk-kpi p">
                        <div class="bk-kl">Total leads</div>
                        <div class="bk-kv"><?php echo $total; ?></div>
                        <div class="bk-ks"><?php echo esc_html( $period_labels[ $period ] ); ?></div>
                    </div>
                    <div class="bk-kpi a">
                        <div class="bk-kl">Today</div>
                        <div class="bk-kv"><?php echo $today; ?></div>
                        <div class="bk-ks">new leads</div>
                    </div>
                    <div class="bk-kpi b">
                        <div class="bk-kl">Unhandled</div>
                        <div class="bk-kv"><?php echo $new_c; ?></div>
                        <div class="bk-ks">awaiting contact</div>
                    </div>
                    <div class="bk-kpi b">
                        <div class="bk-kl">Avg. value</div>
                        <div class="bk-kv"><?php echo number_format( $avg, 0, ',', '.' ); ?></div>
                        <div class="bk-ks">RSD / lead</div>
                    </div>
                    <div class="bk-kpi t">
                        <div class="bk-kl">Total est. value</div>
                        <div class="bk-kv" style="font-size:20px"><?php echo number_format( $sum, 0, ',', '.' ); ?></div>
                        <div class="bk-ks">RSD</div>
                    </div>
                    <div class="bk-kpi g">
                        <div class="bk-kl">Best day</div>
                        <div class="bk-kv" style="font-size:15px;margin-top:3px"><?php echo esc_html( $best_day ); ?></div>
                        <div class="bk-ks">&nbsp;</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Section: Recent leads (open by default) ── -->
        <div class="bk-collapse-section" id="sec-recent">
            <div class="bk-collapse-header" onclick="bkToggle('sec-recent')">
                <span>🕑 Recent Leads</span>
                <span class="bk-chevron">▾</span>
            </div>
            <div class="bk-collapse-body">
                <div style="display:flex;justify-content:flex-end;margin-bottom:8px">
                    <a href="<?php echo esc_url( $leads_url ); ?>" style="font-size:12px;color:#2271b1;text-decoration:none;">
                        View all leads →
                    </a>
                </div>
                <div class="bk-tb">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th><th>Email</th><th>Services</th>
                                <th>Area</th><th>Value</th><th>Status</th><th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $recent as $lead ) : ?>
                        <tr data-id="<?php echo $lead->id; ?>">
                            <td style="white-space:nowrap"><?php echo date( 'd.m.Y H:i', strtotime( $lead->created_at ) ); ?></td>
                            <td><?php echo esc_html( $lead->email ); ?></td>
                            <td style="font-size:11px;max-width:160px"><?php echo esc_html( $lead->usluge ); ?></td>
                            <td><?php echo esc_html( $lead->opstina ); ?></td>
                            <td style="white-space:nowrap"><strong><?php echo number_format( $lead->cena_rsd, 0, ',', '.' ); ?> RSD</strong></td>
                            <td>
                                <select class="bk-ss" data-id="<?php echo $lead->id; ?>">
                                    <?php foreach ( $status_options as $val => $label ) : ?>
                                    <option value="<?php echo $val; ?>" <?php selected( $lead->status, $val ); ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="display:flex;gap:4px;align-items:center">
                                <input type="text" class="bk-ni" data-id="<?php echo $lead->id; ?>"
                                       value="<?php echo esc_attr( $lead->nota ?? '' ); ?>" placeholder="Note...">
                                <button class="bk-sn" data-id="<?php echo $lead->id; ?>">✓</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Section: Charts zone (closed by default) ── -->
        <div class="bk-collapse-section bk-collapsed" id="sec-charts">
            <div class="bk-collapse-header" onclick="bkToggle('sec-charts')">
                <span>📊 Charts &amp; Breakdown</span>
                <span class="bk-chevron">▸</span>
            </div>
            <div class="bk-collapse-body" style="display:none">

                <!-- Row 1: Daily trend (wide) + Services doughnut + By area table -->
                <div class="bk-charts-row1">
                    <div class="bk-cb">
                        <h3>Daily Trend <span style="font-weight:400;color:#aaa;font-size:11px">(last 30 days)</span></h3>
                        <canvas id="ch-d"></canvas>
                    </div>
                    <div class="bk-cb">
                        <h3>Top Services</h3>
                        <canvas id="ch-u" style="max-height:160px"></canvas>
                        <table style="width:100%;border-collapse:collapse;margin-top:10px">
                            <thead><tr>
                                <th style="text-align:left;font-size:11px;color:#888;font-weight:700;padding:4px 6px;border-bottom:1px solid #f0f0f0">Service</th>
                                <th style="text-align:right;font-size:11px;color:#888;font-weight:700;padding:4px 6px;border-bottom:1px solid #f0f0f0">%</th>
                            </tr></thead>
                            <tbody>
                            <?php $tu = array_sum( $usluge_cnt ); foreach ( array_slice( $usluge_cnt, 0, 5, true ) as $naziv => $br ) : ?>
                            <tr>
                                <td style="font-size:12px;padding:4px 6px;border-bottom:1px solid #f8f8f8"><?php echo esc_html( $naziv ); ?></td>
                                <td style="text-align:right;padding:4px 6px;border-bottom:1px solid #f8f8f8">
                                    <span class="bk-badge g"><?php echo $tu > 0 ? round( $br / $tu * 100 ) : 0; ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bk-cb">
                        <h3>By Area</h3>
                        <table style="width:100%;border-collapse:collapse">
                            <thead><tr>
                                <th style="text-align:left;font-size:11px;color:#888;font-weight:700;padding:4px 6px;border-bottom:1px solid #f0f0f0">Municipality</th>
                                <th style="text-align:right;font-size:11px;color:#888;font-weight:700;padding:4px 6px;border-bottom:1px solid #f0f0f0">Leads</th>
                                <th style="text-align:right;font-size:11px;color:#888;font-weight:700;padding:4px 6px;border-bottom:1px solid #f0f0f0">%</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ( $areas_data as $o ) : ?>
                            <tr>
                                <td style="font-size:12px;padding:4px 6px;border-bottom:1px solid #f8f8f8"><?php echo esc_html( $o->opstina ); ?></td>
                                <td style="text-align:right;font-size:12px;font-weight:700;padding:4px 6px;border-bottom:1px solid #f8f8f8"><?php echo $o->br; ?></td>
                                <td style="text-align:right;padding:4px 6px;border-bottom:1px solid #f8f8f8">
                                    <span class="bk-badge g"><?php echo $total > 0 ? round( $o->br / $total * 100 ) : 0; ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Row 2: Monthly + Hourly + Weekly -->
                <div class="bk-c3" style="margin-top:12px;margin-bottom:0">
                    <div class="bk-cb"><h3>Monthly</h3><canvas id="ch-m"></canvas></div>
                    <div class="bk-cb"><h3>By hour</h3><canvas id="ch-s"></canvas></div>
                    <div class="bk-cb"><h3>Weekly</h3><canvas id="ch-w"></canvas></div>
                </div>

            </div>
        </div>

        <?php endif; ?>
    </div>

    <script>
    function bkToggle(id) {
        var sec  = document.getElementById(id);
        var body = sec.querySelector('.bk-collapse-body');
        var chev = sec.querySelector('.bk-chevron');
        var isOpen = !sec.classList.contains('bk-collapsed');

        if (isOpen) {
            body.style.maxHeight = body.scrollHeight + 'px';
            requestAnimationFrame(function () {
                body.style.maxHeight = '0';
                body.style.opacity   = '0';
            });
            body.addEventListener('transitionend', function h() {
                body.removeEventListener('transitionend', h);
                body.style.display = 'none';
                body.style.maxHeight = '';
                body.style.opacity   = '';
            });
            sec.classList.add('bk-collapsed');
            chev.textContent = '▸';
        } else {
            body.style.display  = 'block';
            body.style.maxHeight = '0';
            body.style.opacity   = '0';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    body.style.maxHeight = body.scrollHeight + 'px';
                    body.style.opacity   = '1';
                });
            });
            body.addEventListener('transitionend', function h() {
                body.removeEventListener('transitionend', h);
                body.style.maxHeight = '';
            });
            sec.classList.remove('bk-collapsed');
            chev.textContent = '▾';

            // Lazy-init charts
            if (!window.bkChartsInit) {
                window.bkChartsInit = true;
                initCharts();
            }
        }
    }

    // ── Save status + note ────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        var aj = bkAdmin;

        function saveLeadData(id) {
            var row = document.querySelector('tr[data-id="' + id + '"]');
            var fd  = new FormData();
            fd.append('action', 'bk_update_lead');
            fd.append('nonce',  aj.nonce);
            fd.append('id',     id);
            fd.append('status', row.querySelector('.bk-ss').value);
            fd.append('nota',   row.querySelector('.bk-ni').value);

            fetch(aj.url, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        var btn = row.querySelector('.bk-sn');
                        btn.textContent = '✓';
                        btn.style.background = '#10b981';
                        setTimeout(function () { btn.style.background = ''; }, 1400);
                    }
                });
        }

        document.querySelectorAll('.bk-ss').forEach(function (s) {
            s.addEventListener('change', function () { saveLeadData(this.dataset.id); });
        });
        document.querySelectorAll('.bk-sn').forEach(function (b) {
            b.addEventListener('click', function () { saveLeadData(this.dataset.id); });
        });
    });

    // ── Chart.js (lazy, only when section is opened) ─────────
    function initCharts() {
        if (typeof Chart === 'undefined') return;

        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        Chart.defaults.font.size   = 11;
        Chart.defaults.color       = '#666';

        var grid    = { color: 'rgba(0,0,0,.05)' };
        var tooltip = { backgroundColor: 'rgba(0,0,0,.75)', padding: 8, cornerRadius: 5 };

        <?php
        $dl = []; $dv = [];
        foreach ( $daily as $d ) { $dl[] = date( 'd.m', strtotime( $d->d ) ); $dv[] = (int) $d->br; }

        $ml = []; $mv = [];
        $months = [ '01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'May','06'=>'Jun',
                    '07'=>'Jul','08'=>'Aug','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dec' ];
        foreach ( $monthly as $m ) {
            $parts = explode( '-', $m->m );
            $ml[]  = ( $months[ $parts[1] ] ?? $parts[1] ) . ' ' . $parts[0];
            $mv[]  = (int) $m->br;
        }

        $ul   = array_keys( array_slice( $usluge_cnt, 0, 6, true ) );
        $uv   = array_values( array_slice( $usluge_cnt, 0, 6, true ) );
        $boje = [ 'rgba(45,106,45,.8)', 'rgba(34,113,177,.8)', 'rgba(139,92,246,.8)',
                  'rgba(245,158,11,.8)', 'rgba(16,185,129,.8)', 'rgba(239,68,68,.8)' ];

        $wl = []; $wv = [];
        foreach ( $weekly as $wk ) { $wl[] = date( 'd.m', strtotime( $wk->st ) ); $wv[] = (int) $wk->br; }
        ?>

        if (document.getElementById('ch-d')) {
            new Chart(document.getElementById('ch-d'), {
                type: 'line',
                data: { labels: <?php echo json_encode($dl); ?>, datasets: [{ data: <?php echo json_encode($dv); ?>,
                    borderColor: 'rgba(45,106,45,.9)', backgroundColor: 'rgba(45,106,45,.1)',
                    fill: true, tension: .4, pointRadius: 3 }] },
                options: { responsive: true, plugins: { legend: { display: false }, tooltip: tooltip },
                    scales: { x: { grid: grid }, y: { grid: grid, beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
        }

        if (document.getElementById('ch-u')) {
            new Chart(document.getElementById('ch-u'), {
                type: 'doughnut',
                data: { labels: <?php echo json_encode($ul); ?>, datasets: [{ data: <?php echo json_encode($uv); ?>,
                    backgroundColor: <?php echo json_encode($boje); ?>, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8 } }, tooltip: tooltip } }
            });
        }

        if (document.getElementById('ch-m')) {
            new Chart(document.getElementById('ch-m'), {
                type: 'bar',
                data: { labels: <?php echo json_encode($ml); ?>, datasets: [{ data: <?php echo json_encode($mv); ?>,
                    backgroundColor: 'rgba(34,113,177,.8)', borderRadius: 4 }] },
                options: { responsive: true, plugins: { legend: { display: false }, tooltip: tooltip },
                    scales: { x: { grid: grid }, y: { grid: grid, beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
        }

        if (document.getElementById('ch-s')) {
            new Chart(document.getElementById('ch-s'), {
                type: 'bar',
                data: { labels: <?php echo json_encode( array_map( function($h) { return sprintf('%02d', $h); }, range(0,23) ) ); ?>,
                    datasets: [{ data: <?php echo json_encode( array_values($hours_arr) ); ?>,
                    backgroundColor: 'rgba(245,158,11,.8)', borderRadius: 3 }] },
                options: { responsive: true, plugins: { legend: { display: false }, tooltip: tooltip },
                    scales: { x: { grid: grid, ticks: { maxRotation: 45 } }, y: { grid: grid, beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
        }

        if (document.getElementById('ch-w')) {
            new Chart(document.getElementById('ch-w'), {
                type: 'line',
                data: { labels: <?php echo json_encode($wl); ?>, datasets: [{ data: <?php echo json_encode($wv); ?>,
                    borderColor: 'rgba(139,92,246,.9)', backgroundColor: 'rgba(139,92,246,.1)',
                    fill: true, tension: .4, pointRadius: 4 }] },
                options: { responsive: true, plugins: { legend: { display: false }, tooltip: tooltip },
                    scales: { x: { grid: grid }, y: { grid: grid, beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
        }
    }
    </script>
    <?php
}
