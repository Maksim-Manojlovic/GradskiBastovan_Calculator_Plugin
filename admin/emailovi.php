<?php
/**
 * Admin stranica — Podešavanja emaila (sa live preview-om)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function bk_page_emailovi() {

    // ── Čuvanje ───────────────────────────────────────────────────────────────
    if ( isset( $_POST['bk_se'] ) && check_admin_referer( 'bk_en' ) ) {
        update_option( BK_EMAIL_KEY, array(
            'boja'         => sanitize_hex_color( $_POST['em_boja']  ?? '#2d6a2d' ),
            'subject_k'    => sanitize_text_field( $_POST['em_sub']  ?? '' ),
            'uvod_k'       => sanitize_textarea_field( $_POST['em_uvod']  ?? '' ),
            'napomena_k'   => sanitize_textarea_field( $_POST['em_nap']   ?? '' ),
            'cta_tekst'    => sanitize_text_field( $_POST['em_ctat'] ?? '' ),
            'cta_url'      => esc_url_raw( $_POST['em_ctau'] ?? '', [ 'http', 'https', 'tel', 'mailto' ] ),
            'footer_tekst' => sanitize_text_field( $_POST['em_foot'] ?? '' ),
        ) );
        echo '<div class="notice notice-success is-dismissible"><p>✅ Email podešavanja sačuvana!</p></div>';
    }

    $em = bk_get_email();
    ?>
    <div class="wrap bk-w" style="max-width:1080px">
        <h1><span>📧</span> Podešavanja emaila</h1>

        <form method="post">
            <?php wp_nonce_field( 'bk_en' ); ?>
            <div class="bk-eg">

                <!-- Leva kolona: polja -->
                <div>
                    <div class="bk-fg">
                        <h3>🎨 Izgled</h3>
                        <div class="bk-fr">
                            <label>Boja headera i CTA dugmeta</label>
                            <input type="color" name="em_boja" id="em_boja" value="<?php echo esc_attr( $em['boja'] ); ?>">
                        </div>
                    </div>

                    <div class="bk-fg">
                        <h3>📨 Sadržaj emaila korisniku</h3>
                        <div class="bk-fr">
                            <label>Subject (naslov)</label>
                            <input type="text" name="em_sub" id="em_sub" value="<?php echo esc_attr( $em['subject_k'] ); ?>">
                        </div>
                        <div class="bk-fr">
                            <label>Uvodni tekst</label>
                            <textarea name="em_uvod" id="em_uvod"><?php echo esc_textarea( $em['uvod_k'] ); ?></textarea>
                        </div>
                        <div class="bk-fr">
                            <label>Napomena (žuti box ispod)</label>
                            <textarea name="em_nap" id="em_nap"><?php echo esc_textarea( $em['napomena_k'] ); ?></textarea>
                        </div>
                        <div class="bk-fr">
                            <label>Tekst CTA dugmeta</label>
                            <input type="text" name="em_ctat" id="em_ctat" value="<?php echo esc_attr( $em['cta_tekst'] ); ?>">
                        </div>
                        <div class="bk-fr">
                            <label>URL CTA dugmeta (tel: ili https://)</label>
                            <input type="text" name="em_ctau" id="em_ctau" value="<?php echo esc_attr( $em['cta_url'] ); ?>">
                        </div>
                        <div class="bk-fr">
                            <label>Footer tekst</label>
                            <input type="text" name="em_foot" id="em_foot" value="<?php echo esc_attr( $em['footer_tekst'] ); ?>">
                        </div>
                    </div>

                    <?php submit_button( '💾 Sačuvaj podešavanja', 'primary', 'bk_se', false ); ?>
                </div>

                <!-- Desna kolona: live preview -->
                <div class="bk-prev">
                    <h3>👁 Live preview</h3>
                    <div id="em-preview">
                        <div id="pv-hdr" style="padding:24px 28px;text-align:center">
                            <div style="font-size:30px;margin-bottom:6px">🌿</div>
                            <div style="font-size:20px;font-weight:700;color:#fff">Vaša procena cene</div>
                            <div style="font-size:12px;color:rgba(255,255,255,.7);margin-top:3px">Bastovanske usluge · Beograd</div>
                        </div>
                        <div style="padding:20px 28px">
                            <p id="pv-uvod" style="color:#444;font-size:13px;margin:0 0 14px;line-height:1.6"></p>
                            <div id="pv-cena-box" style="border-left:4px solid;padding:14px 18px;margin-bottom:14px;border-radius:0 6px 6px 0">
                                <div id="pv-cena-lbl" style="font-size:11px;font-weight:700;text-transform:uppercase;margin-bottom:2px">Ukupna procena</div>
                                <div id="pv-cena-val" style="font-size:26px;font-weight:800">≈ 4.800 RSD</div>
                                <div style="font-size:11px;color:#888;margin-top:2px">bez PDV-a · okvirna cena</div>
                            </div>
                            <div id="pv-nap" style="background:#fffbf0;border:1px solid #f0d060;border-radius:6px;padding:11px 15px;margin-bottom:14px;font-size:12px;color:#7a6000"></div>
                            <div style="text-align:center;margin-bottom:14px">
                                <a id="pv-cta" href="#" onclick="return false" style="display:inline-block;color:#fff;text-decoration:none;padding:11px 26px;border-radius:6px;font-size:13px;font-weight:700"></a>
                            </div>
                        </div>
                        <div style="background:#f9f9f9;padding:12px 28px;border-top:1px solid #eee;text-align:center">
                            <p id="pv-foot" style="margin:0;font-size:11px;color:#aaa"></p>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        function updatePreview() {
            var boja = document.getElementById('em_boja').value;

            document.getElementById('pv-hdr').style.background       = 'linear-gradient(135deg,' + boja + ',' + boja + 'cc)';
            document.getElementById('pv-cena-box').style.background   = '#f0f7f0';
            document.getElementById('pv-cena-box').style.borderLeftColor = boja;
            document.getElementById('pv-cena-lbl').style.color        = boja;
            document.getElementById('pv-cena-val').style.color        = boja;
            document.getElementById('pv-cta').style.background        = boja;

            document.getElementById('pv-uvod').textContent  = document.getElementById('em_uvod').value;
            document.getElementById('pv-nap').textContent   = '⚠️ ' + document.getElementById('em_nap').value;
            document.getElementById('pv-cta').textContent   = document.getElementById('em_ctat').value;
            document.getElementById('pv-foot').textContent  = document.getElementById('em_foot').value;
        }

        ['em_boja', 'em_uvod', 'em_nap', 'em_ctat', 'em_foot'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', updatePreview);
        });

        updatePreview();
    });
    </script>
    <?php
}
