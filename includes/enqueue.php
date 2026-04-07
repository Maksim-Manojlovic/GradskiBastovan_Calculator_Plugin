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
    add_action( 'wp_head', function() {
        $css_file = BK_DIR . 'assets/css/kalkulator.css';
        if ( ! file_exists( $css_file ) ) return;
        echo '<style id="bk-kalkulator-style">' . file_get_contents( $css_file ) . '</style>' . "\n";
    }, 10 );
}

add_action( 'wp_enqueue_scripts', 'bk_enqueue_styles' );
