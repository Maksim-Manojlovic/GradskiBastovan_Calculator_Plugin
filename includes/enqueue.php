<?php

/**
 * Plugin Name: Bastovanstvo Kalkulator
 * Description: Css enqueue
 * Version:     9.0
 * Author:      Maksim
 * Text Domain: bastovanstvo-kalkulator
 */

function bk_enqueue_styles() {
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'bastovanstvo_kalkulator' ) ) {
        return;
    }

    // Inline the CSS — no HTTP request, no render-blocking
    // Falls back to a regular <link> if file_get_contents fails on the server
    add_action( 'wp_head', function() {
        $css_file = BK_DIR . 'assets/css/kalkulator.css';
        $css      = file_exists( $css_file ) ? @file_get_contents( $css_file ) : false;
        if ( $css ) {
            echo '<style id="bk-kalkulator-style">' . $css . '</style>' . "\n";
        } else {
            echo '<link rel="stylesheet" id="bk-kalkulator-style" href="' . esc_url( BK_URL . 'assets/css/kalkulator.css?ver=' . BK_VERSION ) . '" media="all">' . "\n";
        }
    }, 10 );
}

add_action( 'wp_enqueue_scripts', 'bk_enqueue_styles' );
