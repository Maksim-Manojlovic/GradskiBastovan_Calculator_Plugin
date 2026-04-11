<?php

/**
 * Plugin Name: Bastovanstvo Kalkulator
 * Description: Css enqueue
 * Version:     9.0
 * Author:      Maksim
 * Text Domain: bastovanstvo-kalkulator
 */

function bk_enqueue_assets() {

    if ( is_admin() ) return;

    if ( ! is_singular() ) return;

    global $post;

    if ( ! $post || ! has_shortcode( $post->post_content, 'bastovanstvo_kalkulator' ) ) {
        return;
    }

    wp_enqueue_style(
        'bk-kalkulator-style',
        BK_URL . 'assets/css/kalkulator.css',
        [],
        BK_VERSION
    );

    wp_enqueue_script(
        'bk-kalkulator-js',
        BK_URL . 'assets/js/kalkulator.js',
        [],
        BK_VERSION,
        true
    );
}

add_action('wp_enqueue_scripts', 'bk_enqueue_assets');
