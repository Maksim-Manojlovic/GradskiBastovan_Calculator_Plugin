<?php
/**
 * Admin stranica — Opštine i putni dodaci
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function bk_page_opstine() {

    // ── Čuvanje ───────────────────────────────────────────────────────────────
    if ( isset( $_POST['bk_so'] ) && check_admin_referer( 'bk_on' ) ) {
        $nazivi    = $_POST['op_n'] ?? [];
        $grupe     = $_POST['op_g'] ?? [];
        $transporti = $_POST['op_d'] ?? [];

        $nove = [];

        for ( $i = 0; $i < count( $nazivi ); $i++ ) {
            $naziv = sanitize_text_field( $nazivi[$i] ?? '' );
            if ( empty( $naziv ) ) continue;

            $nove[] = array(
                'naziv'     => $naziv,
                'grupa'     => sanitize_text_field( $grupe[$i] ?? 'Ostalo' ),
                'transport' => intval( $transporti[$i] ?? 0 ),
            );
        }

        if ( ! empty( $nove ) ) {
            update_option( BK_OPSTINE_KEY, $nove );
            echo '<div class="notice notice-success is-dismissible"><p>✅ Opštine sačuvane!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>⚠️ Greška — nije sačuvano ništa.</p></div>';
        }
    }

    $opstine = bk_get_opstine();
    bk_print_drag_js();
    ?>
    <div class="wrap bk-w">
        <h1><span>📍</span> Opštine i putni dodaci</h1>
        <div class="bk-info">
            Podesi opštine i putne troškove. <strong>Transport u RSD</strong> se dodaje na cenu usluge (min. 4000 RSD + transport).
            Stavke u istoj <strong>grupi</strong> pojavljuju se zajedno u padajućem meniju na sajtu.
            Redosled menjaj prevlačenjem.
        </div>

        <form method="post">
            <?php wp_nonce_field( 'bk_on' ); ?>
            <div id="ol">
                <?php foreach ( $opstine as $o ) : ?>
                <div class="bk-red bk-ro" draggable="true">
                    <div class="bk-drag">⠿</div>
                    <div>
                        <label>Naziv opštine *</label>
                        <input type="text" name="op_n[]" value="<?php echo esc_attr( $o['naziv'] ); ?>" required>
                    </div>
                    <div>
                        <label>Grupa</label>
                        <input type="text" name="op_g[]" value="<?php echo esc_attr( $o['grupa'] ); ?>" placeholder="npr. Mali putni dodatak">
                    </div>
                    <div>
                        <label>Transport (RSD)</label>
                        <input type="number" name="op_d[]" value="<?php echo esc_attr( $o['transport'] ?? $o['dodatak'] ?? 0 ); ?>" min="0" step="100">
                    </div>
                    <button type="button" class="bk-del" title="Obriši">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="bk-akcije">
                <button type="button" class="bk-add" id="dodaj-o">＋ Dodaj opštinu</button>
                <?php submit_button( '💾 Sačuvaj', 'primary', 'bk_so', false ); ?>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        bkDrag('ol');

        document.querySelectorAll('#ol .bk-del').forEach(function (b) {
            bkBindDel(b, 'Mora biti bar jedna opština.');
        });

        document.getElementById('dodaj-o').addEventListener('click', function () {
            var novi = document.querySelector('#ol .bk-red').cloneNode(true);
            novi.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
            document.getElementById('ol').appendChild(novi);
            bkBindDel(novi.querySelector('.bk-del'), 'Mora biti bar jedna opština.');
            novi.querySelector('input').focus();
        });
    });
    </script>
    <?php
}
