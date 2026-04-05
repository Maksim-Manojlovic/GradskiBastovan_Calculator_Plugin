<?php
/**
 * Admin stranica — Usluge (sa poduslugama)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function bk_page_usluge() {

    // ── Čuvanje ───────────────────────────────────────────────────────────────
    if ( isset( $_POST['bk_sn'] ) && check_admin_referer( 'bk_un' ) ) {
        $nazivi_p  = $_POST['bk_naziv_p']  ?? [];
        $emojiji   = $_POST['bk_emoji']    ?? [];
        $slugovi_p = $_POST['bk_slug_p']   ?? [];

        // Podusluge dolaze kao JSON stringovi po primarnoj usluzi
        $podusluge_json = $_POST['bk_podusluge_json'] ?? [];

        $nove = [];
        $seen = [];

        for ( $i = 0; $i < count( $nazivi_p ); $i++ ) {
            $naziv = sanitize_text_field( $nazivi_p[$i] ?? '' );
            if ( empty( $naziv ) ) continue;

            $slug = sanitize_title( ! empty( $slugovi_p[$i] ) ? $slugovi_p[$i] : $naziv );
            if ( empty( $slug ) ) $slug = 'u' . ( $i + 1 );
            $base = $slug; $k = 1;
            while ( in_array( $slug, $seen, true ) ) $slug = $base . '-' . $k++;
            $seen[] = $slug;

            // Podusluge
            $ps_raw    = $podusluge_json[$i] ?? '[]';
            $ps_decoded = json_decode( wp_unslash( $ps_raw ), true );
            $podusluge = [];

            $tip_allowed = [ 'po_m2', 'raspon_m2', 'po_komadu', 'raspon_kom', 'po_dogovoru' ];

            if ( is_array( $ps_decoded ) ) {
                foreach ( $ps_decoded as $ps ) {
                    $ps_naziv = sanitize_text_field( $ps['naziv'] ?? '' );
                    if ( empty( $ps_naziv ) ) continue;

                    $tip = sanitize_key( $ps['tip_cene'] ?? 'po_m2' );
                    if ( ! in_array( $tip, $tip_allowed, true ) ) $tip = 'po_m2';

                    $podusluge[] = [
                        'naziv'         => $ps_naziv,
                        'slug'          => sanitize_title( $ps['slug'] ?? $ps_naziv ),
                        'tip_cene'      => $tip,
                        'cena_min'      => floatval( $ps['cena_min'] ?? 0 ),
                        'cena_max'      => floatval( $ps['cena_max'] ?? 0 ),
                        'jedinica_label'=> sanitize_text_field( $ps['jedinica_label'] ?? 'm²' ),
                        'napomena'      => sanitize_text_field( $ps['napomena'] ?? '' ),
                    ];
                }
            }

            $nove[] = [
                'naziv'     => $naziv,
                'emoji'     => sanitize_text_field( $emojiji[$i] ?? '🌿' ),
                'slug'      => $slug,
                'podusluge' => $podusluge,
            ];
        }

        if ( ! empty( $nove ) ) {
            update_option( BK_OPTION_KEY, $nove );
            echo '<div class="notice notice-success is-dismissible"><p>✅ Usluge sačuvane!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>⚠️ Greška — nije sačuvano ništa.</p></div>';
        }
    }

    $usluge = bk_get_usluge();
    bk_print_drag_js();

    // Sve primarne usluge kao JSON za JS
    $usluge_json = json_encode( $usluge, JSON_UNESCAPED_UNICODE );
    ?>
    <div class="wrap bk-w" style="max-width:1100px">
        <h1><span>🌿</span> Usluge kalkulatora</h1>
        <div class="bk-info">
            Svaka primarna usluga ima <strong>podusluge</strong> sa svojom cenom.
            Klijent bira primarnu uslugu, pa jednu poduslugu i unosi količinu (m², kom, itd.).
            Minimalna cena po kombinaciji je <strong>4.000 RSD</strong>.
        </div>

        <form method="post" id="bk-usluge-form">
            <?php wp_nonce_field( 'bk_un' ); ?>

            <!-- Primarne usluge -->
            <div id="ul">
                <?php foreach ( $usluge as $idx => $u ) :
                    $ps_json = esc_attr( json_encode( $u['podusluge'] ?? [], JSON_UNESCAPED_UNICODE ) );
                ?>
                <div class="bk-red bk-ru-p" draggable="true" data-idx="<?php echo $idx; ?>">
                    <div class="bk-drag">⠿</div>

                    <div style="display:grid;grid-template-columns:78px 1fr auto;gap:10px;align-items:start;flex:1">

                        <!-- Emoji + Naziv primarne usluge -->
                        <div>
                            <label>Emoji</label>
                            <input type="text" name="bk_emoji[]" value="<?php echo esc_attr( $u['emoji'] ); ?>" maxlength="5">
                        </div>
                        <div>
                            <label>Naziv primarne usluge *</label>
                            <input type="text" name="bk_naziv_p[]" value="<?php echo esc_attr( $u['naziv'] ); ?>" required>
                            <input type="hidden" name="bk_slug_p[]" value="<?php echo esc_attr( $u['slug'] ); ?>">
                            <input type="hidden" name="bk_podusluge_json[]" class="bk-ps-json" value="<?php echo $ps_json; ?>">
                        </div>

                        <!-- Dugme za upravljanje poduslugama -->
                        <div style="padding-top:18px">
                            <button type="button" class="button bk-ps-toggle">
                                ▾ Podusluge (<span class="bk-ps-count"><?php echo count( $u['podusluge'] ?? [] ); ?></span>)
                            </button>
                        </div>
                    </div>

                    <button type="button" class="bk-del" title="Obriši primarnu uslugu">✕</button>

                    <!-- Panel podusluga (hidden/shown) -->
                    <div class="bk-ps-panel" style="display:none;grid-column:1/-1;margin-top:10px;border-top:1px dashed #ddd;padding-top:10px">
                        <div class="bk-ps-legend">
                            <span style="background:#e8f5e8;color:#2d6a2d;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">📐 po_m2</span> — fiksna cena/m² &nbsp;
                            <span style="background:#e8f0fb;color:#2271b1;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">📊 raspon_m2</span> — raspon cena/m² &nbsp;
                            <span style="background:#fff8e1;color:#b45309;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">📦 po_komadu</span> — fiksna cena/kom &nbsp;
                            <span style="background:#fce8f3;color:#9d174d;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">📦 raspon_kom</span> — raspon/kom &nbsp;
                            <span style="background:#f0f0f0;color:#555;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700">🤝 po_dogovoru</span> — po dogovoru
                        </div>

                        <div class="bk-ps-lista">
                            <?php foreach ( $u['podusluge'] ?? [] as $ps ) :
                                $tip = $ps['tip_cene'] ?? 'po_m2';
                            ?>
                            <div class="bk-ps-red" data-tip="<?php echo esc_attr($tip); ?>">
                                <?php echo bk_render_ps_row( $ps ); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="bk-add bk-ps-add-btn" style="margin-top:8px">＋ Dodaj poduslugu</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="bk-akcije" style="margin-top:16px">
                <button type="button" class="bk-add" id="dodaj-u">＋ Dodaj primarnu uslugu</button>
                <?php submit_button( '💾 Sačuvaj sve usluge', 'primary', 'bk_sn', false ); ?>
            </div>
        </form>

        <p style="margin-top:24px;color:#999;font-size:12px;">
            Shortcode: <code>[bastovanstvo_kalkulator]</code>
        </p>
    </div>

    <style>
    .bk-ru-p { display:flex; gap:10px; align-items:flex-start; }
    .bk-ru-p .bk-drag { padding-top:18px; }
    .bk-ru-p .bk-del  { margin-top:18px; }
    .bk-ps-panel { background:#f9fdf9; border-radius:6px; padding:14px; }
    .bk-ps-legend { font-size:11px; color:#666; margin-bottom:12px; line-height:2 }
    .bk-ps-red { display:grid; grid-template-columns:1fr 130px 80px 80px 120px 32px; gap:8px; align-items:end; background:#fff; border:1px solid #e8e8e8; border-radius:5px; padding:8px 10px; margin-bottom:7px }
    .bk-ps-red label { font-size:11px; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:.3px; display:block; margin-bottom:3px }
    .bk-ps-red input, .bk-ps-red select { width:100%; box-sizing:border-box; border:1px solid #ddd; border-radius:4px; padding:5px 8px; font-size:13px }
    .bk-ps-cena-min, .bk-ps-cena-max { }
    .bk-ps-tip-badge { display:inline-block; padding:1px 6px; border-radius:3px; font-size:10px; font-weight:700 }
    [data-tip="po_m2"]      { border-left:3px solid #2d9e2d }
    [data-tip="raspon_m2"]  { border-left:3px solid #2271b1 }
    [data-tip="po_komadu"]  { border-left:3px solid #f59e0b }
    [data-tip="raspon_kom"] { border-left:3px solid #9d174d }
    [data-tip="po_dogovoru"]{ border-left:3px solid #888 }
    .bk-ps-del { background:none; border:none; cursor:pointer; color:#cc1818; font-size:18px; padding:0; opacity:.6; align-self:center }
    .bk-ps-del:hover { opacity:1 }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        bkDrag('ul');

        // ── Bind del na primarne usluge ───────────────────────────────────────
        document.querySelectorAll('#ul .bk-del').forEach(function(b) {
            bkBindDel(b, 'Mora biti bar jedna primarna usluga.');
        });

        // ── Toggle panel podusluga ────────────────────────────────────────────
        document.querySelectorAll('.bk-ps-toggle').forEach(bindToggle);
        document.querySelectorAll('.bk-ps-add-btn').forEach(bindAddPs);

        // ── Dodaj novu primarnu uslugu ────────────────────────────────────────
        document.getElementById('dodaj-u').addEventListener('click', function () {
            var tpl = document.querySelector('#ul .bk-red');
            var novi = tpl.cloneNode(true);
            novi.querySelectorAll('input[type=text]').forEach(function(i){ i.value = ''; });
            novi.querySelectorAll('input[type=hidden]').forEach(function(i){ i.value = ''; });
            // Reset podusluge JSON
            novi.querySelector('.bk-ps-json').value = '[]';
            novi.querySelector('.bk-ps-lista').innerHTML = '';
            novi.querySelector('.bk-ps-count').textContent = '0';
            novi.querySelector('.bk-ps-panel').style.display = 'none';
            document.getElementById('ul').appendChild(novi);
            bkBindDel(novi.querySelector('.bk-del'), 'Mora biti bar jedna primarna usluga.');
            bindToggle(novi.querySelector('.bk-ps-toggle'));
            bindAddPs(novi.querySelector('.bk-ps-add-btn'));
            novi.querySelector('input[type=text]').focus();
        });

        // ── Pre submita: serializuj podusluge u JSON ──────────────────────────
        document.getElementById('bk-usluge-form').addEventListener('submit', function() {
            document.querySelectorAll('#ul .bk-red').forEach(function(red) {
                var lista = red.querySelector('.bk-ps-lista');
                var jsonInput = red.querySelector('.bk-ps-json');
                if (!lista || !jsonInput) return;

                var ps = [];
                lista.querySelectorAll('.bk-ps-red').forEach(function(psRed) {
                    ps.push({
                        naziv:         psRed.querySelector('.ps-naziv').value,
                        slug:          psRed.querySelector('.ps-slug').value,
                        tip_cene:      psRed.querySelector('.ps-tip').value,
                        cena_min:      psRed.querySelector('.ps-cmin').value,
                        cena_max:      psRed.querySelector('.ps-cmax') ? psRed.querySelector('.ps-cmax').value : '0',
                        jedinica_label: psRed.querySelector('.ps-jed').value,
                        napomena:      psRed.querySelector('.ps-nap').value,
                    });
                });
                jsonInput.value = JSON.stringify(ps);
            });
        });

        function bindToggle(btn) {
            btn.addEventListener('click', function() {
                var panel = btn.closest('.bk-red').querySelector('.bk-ps-panel');
                var open = panel.style.display !== 'none';
                panel.style.display = open ? 'none' : 'block';
                btn.textContent = (open ? '▾' : '▴') + btn.textContent.substring(1);
            });
        }

        function bindAddPs(btn) {
            btn.addEventListener('click', function() {
                var lista = btn.closest('.bk-ps-panel').querySelector('.bk-ps-lista');
                var noviRed = makeNewPsRow({});
                lista.appendChild(noviRed);
                updatePsCount(btn);
                noviRed.querySelector('.ps-naziv').focus();
            });
        }

        function makeNewPsRow(ps) {
            var div = document.createElement('div');
            div.className = 'bk-ps-red';
            var tip = ps.tip_cene || 'po_m2';
            div.setAttribute('data-tip', tip);
            div.innerHTML = <?php echo json_encode( bk_ps_row_template_html() ); ?>;
            div.querySelector('.ps-naziv').value = ps.naziv || '';
            div.querySelector('.ps-slug').value  = ps.slug  || '';
            div.querySelector('.ps-tip').value   = tip;
            div.querySelector('.ps-cmin').value  = ps.cena_min || '';
            if (div.querySelector('.ps-cmax')) div.querySelector('.ps-cmax').value = ps.cena_max || '';
            div.querySelector('.ps-jed').value   = ps.jedinica_label || 'm²';
            div.querySelector('.ps-nap').value   = ps.napomena || '';
            bindPsTip(div.querySelector('.ps-tip'));
            bindPsDel(div.querySelector('.bk-ps-del'));
            return div;
        }

        function bindPsTip(sel) {
            sel.addEventListener('change', function() {
                var red = sel.closest('.bk-ps-red');
                red.setAttribute('data-tip', sel.value);
                updateCmaxVisibility(red, sel.value);
            });
            updateCmaxVisibility(sel.closest('.bk-ps-red'), sel.value);
        }

        function updateCmaxVisibility(red, tip) {
            var cmaxWrapper = red.querySelector('.bk-ps-cmax-wrap');
            if (!cmaxWrapper) return;
            var show = (tip === 'raspon_m2' || tip === 'raspon_kom');
            cmaxWrapper.style.display = show ? '' : 'none';
            // Grey out cena_min label za "po_dogovoru"
            var cminLabel = red.querySelector('.bk-ps-cmin-label');
            if (cminLabel) {
                cminLabel.textContent = tip === 'po_dogovoru' ? 'Min. cena (auto 4000)' :
                    (tip === 'raspon_m2' || tip === 'raspon_kom') ? 'Cena od' : 'Cena';
            }
            var cminWrap = red.querySelector('.bk-ps-cmin-wrap');
            if (cminWrap) cminWrap.style.display = tip === 'po_dogovoru' ? 'none' : '';
        }

        function bindPsDel(btn) {
            btn.addEventListener('click', function() {
                var lista = btn.closest('.bk-ps-lista');
                btn.closest('.bk-ps-red').remove();
                var addBtn = lista.closest('.bk-ps-panel').querySelector('.bk-ps-add-btn');
                updatePsCount(addBtn);
            });
        }

        function updatePsCount(refEl) {
            var panel = refEl.closest('.bk-ps-panel');
            var cnt = panel.querySelectorAll('.bk-ps-red').length;
            refEl.closest('.bk-red').querySelector('.bk-ps-count').textContent = cnt;
        }

        // Bind existing rows
        document.querySelectorAll('.bk-ps-red').forEach(function(red) {
            var tipSel = red.querySelector('.ps-tip');
            if (tipSel) bindPsTip(tipSel);
            var delBtn = red.querySelector('.bk-ps-del');
            if (delBtn) bindPsDel(delBtn);
        });
    });
    </script>
    <?php
}

// ── Render jednog reda podusluge (PHP strana) ─────────────────────────────────

function bk_render_ps_row( $ps ) {
    $tip    = esc_attr( $ps['tip_cene'] ?? 'po_m2' );
    $cmin   = esc_attr( $ps['cena_min'] ?? '' );
    $cmax   = esc_attr( $ps['cena_max'] ?? '' );
    $jed    = esc_attr( $ps['jedinica_label'] ?? 'm²' );
    $nap    = esc_attr( $ps['napomena'] ?? '' );
    $naziv  = esc_attr( $ps['naziv'] ?? '' );
    $slug   = esc_attr( $ps['slug'] ?? '' );
    $hide_max = ( $tip !== 'raspon_m2' && $tip !== 'raspon_kom' ) ? 'display:none' : '';
    $hide_min = $tip === 'po_dogovoru' ? 'display:none' : '';
    $cmin_label = $tip === 'po_dogovoru' ? 'Min. cena (auto 4000)' :
        ( $tip === 'raspon_m2' || $tip === 'raspon_kom' ? 'Cena od' : 'Cena' );

    return '
        <div>
            <label>Naziv podusluge *</label>
            <input type="text" class="ps-naziv" value="' . $naziv . '" placeholder="npr. Trimer">
            <input type="hidden" class="ps-slug" value="' . $slug . '">
        </div>
        <div>
            <label>Tip cene</label>
            <select class="ps-tip">
                <option value="po_m2"' . selected($tip,'po_m2',false) . '>📐 po m²</option>
                <option value="raspon_m2"' . selected($tip,'raspon_m2',false) . '>📊 raspon/m²</option>
                <option value="po_komadu"' . selected($tip,'po_komadu',false) . '>📦 po kom</option>
                <option value="raspon_kom"' . selected($tip,'raspon_kom',false) . '>📦 raspon/kom</option>
                <option value="po_dogovoru"' . selected($tip,'po_dogovoru',false) . '>🤝 po dogovoru</option>
            </select>
        </div>
        <div class="bk-ps-cmin-wrap" style="' . $hide_min . '">
            <label class="bk-ps-cmin-label">' . $cmin_label . '</label>
            <input type="number" class="ps-cmin" value="' . $cmin . '" min="0" placeholder="RSD">
        </div>
        <div class="bk-ps-cmax-wrap" style="' . $hide_max . '">
            <label>Cena do</label>
            <input type="number" class="ps-cmax" value="' . $cmax . '" min="0" placeholder="RSD">
        </div>
        <div>
            <label>Jedinica</label>
            <input type="text" class="ps-jed" value="' . $jed . '" placeholder="m², kom, m">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="button" class="bk-ps-del" title="Obriši poduslugu">✕</button>
        </div>
        <div style="grid-column:1/-1;margin-top:4px">
            <label>Napomena (prikazuje se klijentu)</label>
            <input type="text" class="ps-nap" value="' . $nap . '" placeholder="npr. Zavisno od visine trave" style="width:100%">
        </div>
    ';
}

// ── Template HTML za novi red podusluge (JS dinamičko dodavanje) ──────────────

function bk_ps_row_template_html() {
    return bk_render_ps_row([
        'naziv'         => '',
        'slug'          => '',
        'tip_cene'      => 'po_m2',
        'cena_min'      => '',
        'cena_max'      => '',
        'jedinica_label'=> 'm²',
        'napomena'      => '',
    ]);
}
