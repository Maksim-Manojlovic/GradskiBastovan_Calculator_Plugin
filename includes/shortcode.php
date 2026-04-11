<?php
/**
 * Shortcode — [bastovanstvo_kalkulator]
 * 3-koračni wizard: Usluge → Detalji → Kontakt
 *
 * JS logika je u assets/js/kalkulator.js.
 * Katalog usluga prosleđuje se kao window.bkUsluge via wp_localize_script.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'bastovanstvo_kalkulator', 'bk_shortcode_render' );

// Prosleđivanje kataloga usluga JS-u (hook priority 20 — posle enqueue-a)
function bk_localize_kalkulator() {
    wp_localize_script( 'bk-script', 'bkUsluge', bk_get_usluge() );
}
add_action( 'wp_enqueue_scripts', 'bk_localize_kalkulator', 20 );

function bk_shortcode_render() {
    $usluge  = bk_get_usluge();
    $opstine = bk_get_opstine();

    $grupe = array();
    foreach ( $opstine as $o ) {
        $grupe[ $o['grupa'] ][] = $o;
    }

    ob_start();
    ?>
    <div class="bk-wrapper">

        <div class="bk-hero">
            <div class="bk-hero-label">Beograd &middot; Online kalkulator</div>
            <h1>Kalkulator cena<br>bastovanskih usluga</h1>
            <p>Saznajte okvirnu cenu za vaš zeleni prostor za samo par sekundi</p>
        </div>

        <div class="bk-progress">
            <div class="bk-progress-track">
                <div class="bk-progress-fill" id="bk-progress-fill"></div>
            </div>
            <div class="bk-progress-steps">
                <div class="bk-progress-step active" data-step="1">
                    <div class="bk-step-dot">1</div>
                    <div class="bk-step-label">Usluge</div>
                </div>
                <div class="bk-progress-step" data-step="2">
                    <div class="bk-step-dot">2</div>
                    <div class="bk-step-label">Detalji</div>
                </div>
                <div class="bk-progress-step" data-step="3">
                    <div class="bk-step-dot">3</div>
                    <div class="bk-step-label">Kontakt</div>
                </div>
            </div>
        </div>

        <div class="bk-main">
            <div class="bk-steps-container">

                <!-- KORAK 1 -->
                <div class="bk-step" id="bk-step-1">

                        <div id="bk-cart" style="display:none; margin-bottom:16px">
                            <div class="bk-cart-header">&#128722; Odabrane usluge</div>
                            <div class="bk-cart-items"></div>
                        </div>

                    <div class="bk-section">
                        <div class="bk-section-title"><span class="bk-icon">&#127807;</span> Odaberite uslugu</div>
                        <p class="bk-section-desc">Dodajte jednu ili više usluga, pa pređite na detalje</p>

                        <div class="bk-services" id="bk-primarne">
                            <?php foreach ( $usluge as $u ) : ?>
                            <label class="bk-service-card bk-primary-card">
                                <input type="radio"
                                       name="primarna_usluga"
                                       value="<?php echo esc_attr( $u['slug'] ); ?>"
                                       data-naziv="<?php echo esc_attr( $u['naziv'] ); ?>">
                                <div class="bk-service-label">
                                    <span class="bk-service-emoji"><?php echo esc_html( $u['emoji'] ); ?></span>
                                    <div class="bk-service-name"><?php echo esc_html( $u['naziv'] ); ?></div>
                                </div>
                                <div class="bk-service-check">&#10003;</div>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div id="bk-podusluge-wrap" style="display:none; margin-top:18px">
                            <div class="bk-section-title" style="font-size:14px; margin-bottom:8px">
                                <span class="bk-icon">&#128295;</span>
                                Izaberite poduslugu:
                                <span id="bk-primarna-naziv" style="color:#2d6a2d; font-weight:800"></span>
                            </div>
                            <div id="bk-podusluge-lista" class="bk-podusluge-grid"></div>
                        </div>

                        <div id="bk-kolicina-wrap" style="display:none; margin-top:18px">
                            <div class="bk-kolicina-box">
                                <div class="bk-kolicina-title" id="bk-kolicina-title">Unesite količinu</div>
                                <div class="bk-kolicina-napomena" id="bk-kolicina-napomena"></div>
                                <div class="bk-kolicina-row">
                                    <input type="number" id="bk-kolicina-input" class="bk-input"
                                           min="1" value="50" placeholder="npr. 100">
                                </div>
                                <div class="bk-live-cena" id="bk-live-cena" style="display:none">
                                    <div class="bk-live-cena-label">Okvirna cena</div>
                                    <div class="bk-live-cena-value" id="bk-live-cena-value">&#8212; RSD</div>
                                    <div class="bk-live-cena-tip"   id="bk-live-cena-tip"></div>
                                </div>
                            </div>
                        </div>

                        <div id="bk-dodaj-wrap" style="display:none; margin-top:16px">
                            <button class="bk-btn-dodaj" id="bk-btn-dodaj" disabled>
                                &#43; Dodaj uslugu u listu
                            </button>
                        </div>
                    </div>

                    <div id="bk-empty-hint" class="bk-empty-hint">
                        &#128070; Odaberite barem jednu uslugu da biste nastavili
                    </div>

                    <div class="bk-step-nav">
                        <div></div>
                        <button class="bk-btn bk-btn-primary" id="bk-next-1" disabled>
                            Dalje: Detalji <span class="bk-btn-arrow">&#8594;</span>
                        </button>
                    </div>
                    <div class="bk-error" id="bk-error-1"></div>
                </div>

                <!-- KORAK 2 -->
                <div class="bk-step" id="bk-step-2" style="display:none">

                    <div class="bk-section">
                        <div class="bk-section-title"><span class="bk-icon">&#128205;</span> Op&#353;tina</div>
                        <p class="bk-section-desc">
                            Izaberite op&#353;tinu &mdash; putni tro&#353;ak se dodaje na cenu usluge
                        </p>
                        <div class="bk-field">
                            <div class="bk-select-wrap">
                                <select class="bk-select" id="opstina">
                                    <option value="">&#8212; Izaberite op&#353;tinu &#8212;</option>
                                    <?php foreach ( $grupe as $naziv_grupe => $lista ) : ?>
                                    <optgroup label="<?php echo esc_attr( $naziv_grupe ); ?>">
                                        <?php foreach ( $lista as $o ) :
                                            $transport = isset( $o['transport'] ) ? (int) $o['transport'] : (int) ( $o['dodatak'] ?? 0 );
                                        ?>
                                        <option value="<?php echo esc_attr( $transport ); ?>"
                                                data-naziv="<?php echo esc_attr( $o['naziv'] ); ?>">
                                            <?php echo esc_html( $o['naziv'] ); ?>
                                            (+<?php echo number_format( $transport, 0, ',', '.' ); ?> RSD)
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bk-section">
                        <div class="bk-section-title"><span class="bk-icon">&#9881;&#65039;</span> Dodatne opcije</div>
                        <p class="bk-section-desc">Ove opcije mogu uticati na finalnu cenu</p>

                        <div class="bk-field">
                            <label class="bk-label">U&#269;estalost usluge</label>
                            <div class="bk-chips">
                                <label class="bk-chip">
                                    <input type="radio" name="ucestalost" value="0" checked>
                                    <span class="bk-chip-label">Jednokratno</span>
                                </label>
                                <label class="bk-chip">
                                    <input type="radio" name="ucestalost" value="-15">
                                    <span class="bk-chip-label">Mese&#269;ni ugovor <span class="bk-chip-badge">-15%</span></span>
                                </label>
                                <label class="bk-chip">
                                    <input type="radio" name="ucestalost" value="-25">
                                    <span class="bk-chip-label">Sezonski ugovor <span class="bk-chip-badge">-25%</span></span>
                                </label>
                            </div>
                        </div>

                        <div class="bk-divider"></div>

                        <div class="bk-field" style="margin-bottom:0">
                            <label class="bk-label">Hitnost</label>
                            <div class="bk-chips">
                                <label class="bk-chip">
                                    <input type="radio" name="hitnost" value="0" checked>
                                    <span class="bk-chip-label">Standardno (3-5 dana)</span>
                                </label>
                                <label class="bk-chip">
                                    <input type="radio" name="hitnost" value="20">
                                    <span class="bk-chip-label">Ekspresno (24h) <span class="bk-chip-badge">+20%</span></span>
                                </label>
                                <label class="bk-chip">
                                    <input type="radio" name="hitnost" value="40">
                                    <span class="bk-chip-label">Isti dan <span class="bk-chip-badge">+40%</span></span>
                                </label>
                            </div>
                        </div>

                        <div class="bk-note">
                            Kombinovanjem sezonskog ugovora i standardnog termina mo&#382;ete u&#353;tedeti i do 25%.
                        </div>
                    </div>

                    <div class="bk-step-nav">
                        <button class="bk-btn bk-btn-secondary" id="bk-back-2">
                            <span class="bk-btn-arrow">&#8592;</span> Nazad
                        </button>
                        <button class="bk-btn bk-btn-primary" id="bk-next-2">
                            Dalje: Kontakt <span class="bk-btn-arrow">&#8594;</span>
                        </button>
                    </div>
                    <div class="bk-error" id="bk-error-2"></div>
                </div>

                <!-- KORAK 3 -->
                <div class="bk-step" id="bk-step-3" style="display:none">

                    <div class="bk-review-card" id="bk-review">
                        <div class="bk-review-title">&#128203; Pregled va&#353;eg izbora</div>
                        <div class="bk-review-grid" id="bk-review-grid"></div>
                    </div>

                    <div class="bk-email-section">
                        <div class="bk-email-info">
                            <span class="bk-email-icon">&#128231;</span>
                            <div>
                                <strong>Unesite email adresu</strong> da biste primili detaljnu procenu.
                                Besplatno i bez obaveza.
                            </div>
                        </div>
                        <div class="bk-email-wrap">
                            <input type="email" id="bk-email" class="bk-email-input"
                                   placeholder="vasa@email.com" autocomplete="email">
                            <span class="bk-email-check" id="bk-email-check">&#10003;</span>
                        </div>
                    </div>

                    <div class="bk-step-nav">
                        <button class="bk-btn bk-btn-secondary" id="bk-back-3">
                            <span class="bk-btn-arrow">&#8592;</span> Nazad
                        </button>
                        <button class="bk-btn bk-btn-primary" id="bk-btn-izracunaj" disabled>
                            Izra&#269;unaj i po&#353;alji procenu &#128232;
                        </button>
                    </div>

                    <div class="bk-error"       id="bk-error-3"></div>
                    <div class="bk-send-status" id="bk-send-status"></div>

                    <!-- bk-result je SAKRIVEN — JS dodaje .active da ga pokaže -->
                    <div class="bk-result" id="bk-result">
                        <div class="bk-result-header">
                            <div>
                                <div class="bk-result-title">Okvirna procena cene</div>
                                <div class="bk-result-subtitle" id="result-subtitle">Za va&#353; zeleni prostor</div>
                            </div>
                            <div class="bk-result-price">
                                <div class="bk-result-price-label">Ukupno</div>
                                <div class="bk-result-price-value">
                                    <span class="bk-result-price-currency">&asymp;</span>
                                    <span id="result-cena">0</span>
                                </div>
                                <div style="font-size:12px; opacity:.6; margin-top:4px">RSD (bez PDV-a)</div>
                            </div>
                        </div>
                        <div class="bk-result-breakdown">
                            <div class="bk-breakdown-title">Razrada cene</div>
                            <div id="result-breakdown"></div>
                        </div>
                        <div class="bk-result-tags" id="result-tags"></div>
                        <div class="bk-cta">
                            <button class="bk-btn bk-btn-primary"  id="bk-btn-kontakt">Zatra&#382;ite ta&#269;nu ponudu</button>
                            <button class="bk-btn bk-btn-secondary" id="bk-btn-reset">&#8635; Novi obra&#269;un</button>
                        </div>
                    </div>

                </div><!-- /step-3 -->

            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
