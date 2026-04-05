<?php

/**
 * Plugin Name: Bastovanstvo Kalkulator
 * Description: Css enqueue
 * Version:     9.0
 * Author:      Maksim
 * Text Domain: bastovanstvo-kalkulator
 */

function bk_enqueue_styles() {
    // Only load on pages that use the calculator shortcode
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'bastovanstvo_kalkulator' ) ) {
        return;
    }

    // Defer — calculator is not above the fold
    wp_enqueue_style(
        'bk-kalkulator-style',
        BK_URL . 'assets/css/kalkulator.css',
        [],
        BK_VERSION,
        'print'
    );

    add_filter( 'style_loader_tag', function( $tag, $handle ) {
        if ( $handle !== 'bk-kalkulator-style' ) return $tag;
        $tag = str_replace( "media='print'", "media='print' onload=\"this.media='all'\"", $tag );
        return $tag . "<noscript>" . str_replace( " onload=\"this.media='all'\"", '', $tag ) . "</noscript>\n";
    }, 10, 2 );
}

add_action( 'wp_enqueue_scripts', 'bk_enqueue_styles' );
