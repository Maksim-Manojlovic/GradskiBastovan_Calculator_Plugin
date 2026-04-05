<?php
/**
 * Admin stranica — Migracije
 * Prikaz statusa svih migracija i mogućnost ručnog pokretanja.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function bk_page_migracije() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Nemate dozvolu.' );
    }

    $poruka = '';

    // ── Ručno pokretanje pending migracija ────────────────────────────────────
    if ( isset( $_POST['bk_run_migrations'] ) && check_admin_referer( 'bk_migrations_nonce' ) ) {
        $pokrenute = bk_run_migrations();

        if ( empty( $pokrenute ) ) {
            $poruka = array( 'tip' => 'info', 'tekst' => 'Nema pending migracija — sve je ažurno.' );
        } else {
            $lista  = implode( ', ', array_map( function( $n ) { return '<code>' . esc_html( $n ) . '</code>'; }, $pokrenute ) );
            $poruka = array( 'tip' => 'success', 'tekst' => 'Pokrenute migracije: ' . $lista );
        }
    }

    // ── Rollback poslednje migracije ──────────────────────────────────────────
    if ( isset( $_POST['bk_rollback'] ) && check_admin_referer( 'bk_migrations_nonce' ) ) {
        $rollback = bk_rollback_migrations( 1 );

        if ( empty( $rollback ) ) {
            $poruka = array( 'tip' => 'warning', 'tekst' => 'Nema migracija za rollback.' );
        } else {
            $poruka = array( 'tip' => 'success', 'tekst' => 'Rollback: <code>' . esc_html( $rollback[0] ) . '</code>' );
        }
    }

    $status_lista = bk_get_migration_status();
    $pending_cnt  = count( array_filter( $status_lista, fn( $m ) => $m['status'] === 'pending' ) );
    $ran_cnt      = count( array_filter( $status_lista, fn( $m ) => $m['status'] === 'ran' ) );
    ?>
    <div class="wrap bk-w">
        <h1><span>🗄️</span> Migracije baze podataka</h1>

        <?php if ( $poruka ) : ?>
        <div class="notice notice-<?php echo $poruka['tip'] === 'success' ? 'success' : ( $poruka['tip'] === 'warning' ? 'warning' : 'info' ); ?> is-dismissible">
            <p><?php echo $poruka['tekst']; ?></p>
        </div>
        <?php endif; ?>

        <!-- Status summary -->
        <div style="display:flex;gap:14px;margin-bottom:22px">
            <div class="bk-kpi g" style="min-width:140px">
                <div class="bk-kl">Pokrenute</div>
                <div class="bk-kv"><?php echo $ran_cnt; ?></div>
                <div class="bk-ks">migracija</div>
            </div>
            <div class="bk-kpi <?php echo $pending_cnt > 0 ? 'a' : 'g'; ?>" style="min-width:140px">
                <div class="bk-kl">Pending</div>
                <div class="bk-kv"><?php echo $pending_cnt; ?></div>
                <div class="bk-ks"><?php echo $pending_cnt > 0 ? 'čeka pokretanje' : 'sve ažurno'; ?></div>
            </div>
            <div class="bk-kpi b" style="min-width:140px">
                <div class="bk-kl">Ukupno</div>
                <div class="bk-kv"><?php echo count( $status_lista ); ?></div>
                <div class="bk-ks">migracionih fajlova</div>
            </div>
        </div>

        <!-- Akcije -->
        <form method="post" style="display:flex;gap:10px;margin-bottom:22px">
            <?php wp_nonce_field( 'bk_migrations_nonce' ); ?>
            <button type="submit" name="bk_run_migrations"
                    class="button button-primary"
                    <?php echo $pending_cnt === 0 ? 'disabled' : ''; ?>>
                ▶ Pokreni pending migracije <?php echo $pending_cnt > 0 ? "($pending_cnt)" : ''; ?>
            </button>
            <button type="submit" name="bk_rollback"
                    class="button"
                    <?php echo $ran_cnt === 0 ? 'disabled' : ''; ?>
                    onclick="return confirm('Rollback poslednje migracije?')">
                ↩ Rollback poslednje
            </button>
        </form>

        <!-- Lista migracija -->
        <?php if ( empty( $status_lista ) ) : ?>
        <div class="bk-nodata"><span>📂</span>Nema migracionih fajlova u /migrations/ folderu.</div>
        <?php else : ?>
        <div class="bk-tb">
            <h3>Lista migracija</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Naziv migracije</th>
                        <th>Has down()</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $status_lista as $i => $m ) :
                    $putanja   = BK_MIGRATIONS_DIR . $m['naziv'] . '.php';
                    $migracija = file_exists( $putanja ) ? require $putanja : array();
                    $has_down  = isset( $migracija['down'] ) && is_callable( $migracija['down'] );
                ?>
                <tr>
                    <td style="color:#aaa;font-size:11px"><?php echo $i + 1; ?></td>
                    <td>
                        <code style="font-size:12px"><?php echo esc_html( $m['naziv'] ); ?>.php</code>
                    </td>
                    <td>
                        <?php echo $has_down
                            ? '<span class="bk-badge g">✓ da</span>'
                            : '<span class="bk-badge gr">— ne</span>'; ?>
                    </td>
                    <td>
                        <?php if ( $m['status'] === 'ran' ) : ?>
                            <span class="bk-badge g">✓ pokrenuta</span>
                        <?php else : ?>
                            <span class="bk-badge a">⏳ pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Upustvo -->
        <div class="bk-info" style="margin-top:20px">
            <strong>Kako dodati novu migraciju:</strong><br>
            Kreiraj fajl <code>/migrations/000X_naziv.php</code> koji vraća array sa <code>'up'</code> i opciono <code>'down'</code> callable-om.
            Migracije se pokreću automatski pri sledećem učitavanju plugina, ili ručno ovde.
        </div>
        <?php endif; ?>
    </div>
    <?php
}
