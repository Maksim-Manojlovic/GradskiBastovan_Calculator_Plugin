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

    // Preconnect to Google Fonts origins to eliminate DNS/TLS overhead
    add_action( 'wp_head', function() {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }, 1 );

    // Google Fonts — deferred, non-blocking
    wp_enqueue_style(
        'bk-google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap',
        [],
        null,
        'print'
    );

    // Defer kalkulator.css — calculator is not above the fold
    wp_enqueue_style(
        'bk-kalkulator-style',
        BK_URL . 'assets/css/kalkulator.css',
        [],
        BK_VERSION,
        'print'
    );

    $deferred = [ 'bk-google-fonts', 'bk-kalkulator-style' ];
    add_filter( 'style_loader_tag', function( $tag, $handle ) use ( $deferred ) {
        if ( ! in_array( $handle, $deferred, true ) ) return $tag;
        $tag = str_replace( "media='print'", "media='print' onload=\"this.media='all'\"", $tag );
        return $tag . "<noscript>" . str_replace( " onload=\"this.media='all'\"", '', $tag ) . "</noscript>\n";
    }, 10, 2 );
}

add_action( 'wp_enqueue_scripts', 'bk_enqueue_styles' );
