<?php
/**
 * Helpers — default podaci i getter funkcije
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Default vrednosti ────────────────────────────────────────────────────────

/**
 * Tipovi cena za podusluge:
 *  'po_m2'      — jedna fiksna cena po m²
 *  'raspon_m2'  — raspon cena po m² (min i max)
 *  'po_komadu'  — fiksna cena po komadu/stablu (ne množi se sa m²)
 *  'raspon_kom' — raspon cena po komadu
 *  'po_dogovoru'— cena po dogovoru (koristi se minimalna cena 4000 RSD)
 *
 * Za 'po_m2' i 'raspon_m2' klijent unosi površinu u m².
 * Za 'po_komadu' i 'raspon_kom' klijent unosi količinu (komade).
 * Za 'po_dogovoru' nema unosa — prikazuje se min. cena + "po dogovoru".
 *
 * 'jedinica_label' — šta se prikazuje pored inputa (npr. "m²", "kom", "m")
 */
function bk_default_usluge() {
    return array(

        // ── Košenje ───────────────────────────────────────────────────────────
        array(
            'naziv'     => 'Košenje',
            'emoji'     => '🌾',
            'slug'      => 'kosenje',
            'podusluge' => array(
                array(
                    'naziv'         => 'Trimer',
                    'slug'          => 'trimer',
                    'tip_cene'      => 'raspon_m2',
                    'cena_min'      => 9,
                    'cena_max'      => 15,
                    'jedinica_label'=> 'm²',
                    'napomena'      => 'Zavisno od visine trave, pristupačnosti i površine',
                ),
                array(
                    'naziv'         => 'Kosačicom',
                    'slug'          => 'kosacica',
                    'tip_cene'      => 'raspon_m2',
                    'cena_min'      => 5,
                    'cena_max'      => 20,
                    'jedinica_label'=> 'm²',
                    'napomena'      => 'Zavisno od visine trave i površine',
                ),
                array(
                    'naziv'         => 'Zapušteni placevi i površine > 500 m²',
                    'slug'          => 'zapusteni-plac',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => 'Nakon izlaska na teren',
                ),
            ),
        ),

        // ── Orezivanje ────────────────────────────────────────────────────────
        array(
            'naziv'     => 'Orezivanje',
            'emoji'     => '✂️',
            'slug'      => 'orezivanje',
            'podusluge' => array(
                array(
                    'naziv'         => 'Žive ograde',
                    'slug'          => 'zive-ograde',
                    'tip_cene'      => 'raspon_m2',
                    'cena_min'      => 400,
                    'cena_max'      => 700,
                    'jedinica_label'=> 'm',
                    'napomena'      => 'Zavisno od visine i širine (cena po dužnom metru)',
                ),
                array(
                    'naziv'         => 'Pojedinačni žbunovi i drveće',
                    'slug'          => 'zbunovi-drvece',
                    'tip_cene'      => 'raspon_kom',
                    'cena_min'      => 500,
                    'cena_max'      => 2000,
                    'jedinica_label'=> 'kom',
                    'napomena'      => 'Cena po komadu',
                ),
                array(
                    'naziv'         => 'Oblikovanje',
                    'slug'          => 'oblikovanje',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'kom',
                    'napomena'      => '',
                ),
                array(
                    'naziv'         => 'Voćnjaci',
                    'slug'          => 'vocnjaci',
                    'tip_cene'      => 'raspon_kom',
                    'cena_min'      => 200,
                    'cena_max'      => 600,
                    'jedinica_label'=> 'stabla',
                    'napomena'      => 'Zavisno od visine i gustine, cena po stablu',
                ),
                array(
                    'naziv'         => 'Formirana stabla > 3 m',
                    'slug'          => 'formirana-stabla',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'kom',
                    'napomena'      => '',
                ),
                array(
                    'naziv'         => 'Vinograd',
                    'slug'          => 'vinograd',
                    'tip_cene'      => 'raspon_kom',
                    'cena_min'      => 60,
                    'cena_max'      => 150,
                    'jedinica_label'=> 'čokoti',
                    'napomena'      => 'Cena po čokotu',
                ),
                array(
                    'naziv'         => 'Zapušteni vinogradi',
                    'slug'          => 'zapusteni-vinograd',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'čokoti',
                    'napomena'      => '',
                ),
                array(
                    'naziv'         => 'Postavka malča i kamena',
                    'slug'          => 'malc-kamen',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => 'Nakon izlaska na teren',
                ),
            ),
        ),

        // ── Setva trave ───────────────────────────────────────────────────────
        array(
            'naziv'     => 'Setva trave',
            'emoji'     => '🌱',
            'slug'      => 'setva-trave',
            'podusluge' => array(
                array(
                    'naziv'         => 'Površine do 500 m²',
                    'slug'          => 'setva-do-500',
                    'tip_cene'      => 'raspon_m2',
                    'cena_min'      => 150,
                    'cena_max'      => 750,
                    'jedinica_label'=> 'm²',
                    'napomena'      => 'Zavisno od stanja površine i potrebnih radova',
                ),
                array(
                    'naziv'         => 'Površine > 500 m²',
                    'slug'          => 'setva-preko-500',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => '',
                ),
            ),
        ),

        // ── Postavka tepih trave ──────────────────────────────────────────────
        array(
            'naziv'     => 'Postavka tepih trave',
            'emoji'     => '🟩',
            'slug'      => 'tepih-trava',
            'podusluge' => array(
                array(
                    'naziv'         => 'Montiranje sistema za navodnjavanje',
                    'slug'          => 'navodnjavanje',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => 'Nakon izlaska na teren',
                ),
                array(
                    'naziv'         => 'Uređenje terasa i krovova',
                    'slug'          => 'terase-krovovi',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => 'Nakon izlaska na teren',
                ),
            ),
        ),

        // ── Sadnja ────────────────────────────────────────────────────────────
        array(
            'naziv'     => 'Sadnja',
            'emoji'     => '🪴',
            'slug'      => 'sadnja',
            'podusluge' => array(
                array(
                    'naziv'         => 'Sadnice do 0,50 m',
                    'slug'          => 'sadnice-male',
                    'tip_cene'      => 'raspon_kom',
                    'cena_min'      => 150,
                    'cena_max'      => 300,
                    'jedinica_label'=> 'kom',
                    'napomena'      => 'Cena po sadnici',
                ),
                array(
                    'naziv'         => 'Sadnice 0,50 – 1,50 m',
                    'slug'          => 'sadnice-srednje',
                    'tip_cene'      => 'raspon_kom',
                    'cena_min'      => 400,
                    'cena_max'      => 800,
                    'jedinica_label'=> 'kom',
                    'napomena'      => 'Cena po sadnici',
                ),
                array(
                    'naziv'         => 'Sadnice > 1,50 m',
                    'slug'          => 'sadnice-velike',
                    'tip_cene'      => 'po_komadu',
                    'cena_min'      => 1000,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'kom',
                    'napomena'      => '1000 din i više, cena po sadnici',
                ),
                array(
                    'naziv'         => 'Formiranje cvećanjaka',
                    'slug'          => 'cvecnjak',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => '',
                ),
                array(
                    'naziv'         => 'Sadnja > 50 većih biljaka',
                    'slug'          => 'sadnja-vise-50',
                    'tip_cene'      => 'po_dogovoru',
                    'cena_min'      => 0,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'kom',
                    'napomena'      => '',
                ),
            ),
        ),

        // ── Pranje pod visokim pritiskom ──────────────────────────────────────
        array(
            'naziv'     => 'Pranje pod visokim pritiskom',
            'emoji'     => '💦',
            'slug'      => 'pranje-pritiskom',
            'podusluge' => array(
                array(
                    'naziv'         => 'Terase i dvorišta 10–50 m²',
                    'slug'          => 'pranje-malo',
                    'tip_cene'      => 'po_m2',
                    'cena_min'      => 300,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => '',
                ),
                array(
                    'naziv'         => 'Terase i dvorišta 50–150 m²',
                    'slug'          => 'pranje-srednje',
                    'tip_cene'      => 'po_m2',
                    'cena_min'      => 250,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => '',
                ),
                array(
                    'naziv'         => 'Terase i dvorišta 150+ m²',
                    'slug'          => 'pranje-veliko',
                    'tip_cene'      => 'po_m2',
                    'cena_min'      => 200,
                    'cena_max'      => 0,
                    'jedinica_label'=> 'm²',
                    'napomena'      => '',
                ),
            ),
        ),

    );
}

function bk_default_opstine() {
    return array(
        array( 'naziv' => 'Novi Beograd', 'grupa' => 'Zona 1 — do 7km',         'transport' => 1500 ),
        array( 'naziv' => 'Zemun',        'grupa' => 'Zona 1 — do 7km',         'transport' => 1500 ),
        array( 'naziv' => 'Surčin',       'grupa' => 'Zona 1 — do 7km',         'transport' => 1500 ),
        array( 'naziv' => 'Stari grad',   'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Savski venac', 'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Vračar',       'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Zvezdara',     'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Voždovac',     'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Čukarica',     'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Rakovica',     'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Palilula',     'grupa' => 'Zona 2 — 7–15km',         'transport' => 1800 ),
        array( 'naziv' => 'Grocka',       'grupa' => 'Zona 3 — udaljene',        'transport' => 2300 ),
        array( 'naziv' => 'Barajevo',     'grupa' => 'Zona 3 — udaljene',        'transport' => 2300 ),
        array( 'naziv' => 'Sopot',        'grupa' => 'Zona 3 — udaljene',        'transport' => 2300 ),
        array( 'naziv' => 'Obrenovac',    'grupa' => 'Zona 3 — udaljene',        'transport' => 2300 ),
        array( 'naziv' => 'Mladenovac',   'grupa' => 'Zona 4 — najudaljenije',   'transport' => 2600 ),
        array( 'naziv' => 'Lazarevac',    'grupa' => 'Zona 4 — najudaljenije',   'transport' => 2600 ),
    );
}

function bk_default_email() {
    return array(
        'boja'         => '#2d6a2d',
        'subject_k'    => '🌿 Vaša procena cene bastovanskih usluga',
        'uvod_k'       => 'Hvala što ste koristili naš kalkulator! Evo detaljne procene na osnovu vaših izbora.',
        'napomena_k'   => 'Ovo je okvirna procena. Tačna cena zavisi od stanja terena i specifičnih zahteva.',
        'cta_tekst'    => '📞 Zatražite tačnu ponudu',
        'cta_url'      => 'tel:+381600000000',
        'footer_tekst' => 'Ovaj email je automatski generisan kalkulatorom.',
    );
}

// ── Getteri ───────────────────────────────────────────────────────────────────

function bk_get_usluge() {
    $key  = defined('BK_OPTION_KEY') ? BK_OPTION_KEY : 'bk_usluge';
    $data = get_option( $key, array() );
    return ! empty( $data ) ? $data : bk_default_usluge();
}

function bk_get_opstine() {
    $key  = defined('BK_OPSTINE_KEY') ? BK_OPSTINE_KEY : 'bk_opstine';
    $data = get_option( $key, array() );
    return ! empty( $data ) ? $data : bk_default_opstine();
}

function bk_get_email() {
    $key  = defined('BK_EMAIL_KEY') ? BK_EMAIL_KEY : 'bk_email';
    $data = get_option( $key, array() );
    return array_merge( bk_default_email(), $data );
}
