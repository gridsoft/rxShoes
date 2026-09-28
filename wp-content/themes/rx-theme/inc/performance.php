<?php
/**
 * Front-end performance (PROJECT.md §10; client priority — ~90% mobile
 * traffic). Each change here came out of a Lighthouse mobile run on the
 * five key templates (home, shop, product, cart, checkout); see the
 * 2026-09-25 dev-log entry for the before/after numbers.
 *
 * - No render-blocking jQuery: jQuery moves to the footer on the front
 *   end (anything that needs it in the head still pulls it there — WP
 *   resolves dependencies), and jquery-migrate is dropped (nothing on
 *   the site uses the old APIs it shims).
 * - No WordPress emoji script/styles (23 KB of JS on every page; every
 *   browser we target renders emoji natively).
 * - No WooCommerce Brands stylesheet (the theme styles brands itself).
 * - The hero photo is a CSS background, so the browser only finds it
 *   after the whole stylesheet: it's preloaded, at high priority, and
 *   phones get the 1024px size instead of the 1920px original.
 * - The fonts used above the fold are preloaded, so text doesn't reflow
 *   when they arrive (that swap was the shop page's layout shift).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Drop jquery-migrate from jQuery's dependencies on the front end.
 *
 * @param WP_Scripts $scripts Script registry.
 */
function rx_theme_remove_jquery_migrate( WP_Scripts $scripts ): void {
	if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
		return;
	}

	$scripts->registered['jquery']->deps = array_values( array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) ) );
}
add_action( 'wp_default_scripts', 'rx_theme_remove_jquery_migrate' );

/**
 * Print jQuery in the footer instead of the head.
 */
function rx_theme_jquery_in_footer(): void {
	foreach ( array( 'jquery', 'jquery-core' ) as $handle ) {
		wp_script_add_data( $handle, 'group', 1 );
	}
}
add_action( 'wp_enqueue_scripts', 'rx_theme_jquery_in_footer', 1 );

/**
 * Print WooCommerce's front-end scripts in the footer too. WooCommerce
 * enqueues them in the head (with defer), and because they depend on
 * jQuery, that dragged jQuery back into the head as a render-blocking
 * script. They're deferred either way, so they already ran after the
 * page was parsed — moving them changes when they download, not when
 * they run. (Deferring jQuery itself instead would break WooCommerce's
 * inline jQuery(...) snippets, which run during parsing.)
 */
function rx_theme_woocommerce_scripts_in_footer(): void {
	foreach ( wp_scripts()->registered as $handle => $script ) {
		if ( is_string( $script->src ) && str_contains( $script->src, '/plugins/woocommerce/' ) ) {
			wp_script_add_data( $handle, 'group', 1 );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'rx_theme_woocommerce_scripts_in_footer', 100 );

/**
 * Remove the emoji detection script and styles from the front end.
 */
function rx_theme_disable_emoji(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'rx_theme_disable_emoji' );

/**
 * WooCommerce Brands' stylesheet isn't used — the theme styles brands.
 */
function rx_theme_dequeue_unused_styles(): void {
	wp_dequeue_style( 'brands-styles' );
}
add_action( 'wp_enqueue_scripts', 'rx_theme_dequeue_unused_styles', 100 );

/**
 * The hero photo's URLs: the full image, and a phone-sized one (the
 * attachment's 1024px "large" size) when the image is in the media
 * library.
 *
 * @return array{full:string, small:string}
 */
function rx_theme_hero_image_urls(): array {
	$full  = rx_theme_local_upload_url( (string) get_theme_mod( 'rx_hero_image', '' ) );
	$small = '';

	if ( '' !== $full ) {
		$id = attachment_url_to_postid( $full );
		if ( $id ) {
			$large = wp_get_attachment_image_src( $id, 'large' );
			$small = is_array( $large ) ? (string) $large[0] : '';
		}
	}

	return array(
		'full'  => $full,
		'small' => '' !== $small ? $small : $full,
	);
}

/**
 * Preload what the first screen needs before the stylesheet reveals it:
 * the hero photo (front page only; phone and desktop sizes, matching
 * the hero's CSS breakpoint) and the above-the-fold fonts.
 */
function rx_theme_preload_critical_assets(): void {
	if ( is_admin() ) {
		return;
	}

	if ( is_front_page() ) {
		$hero = rx_theme_hero_image_urls();
		if ( '' !== $hero['full'] ) {
			printf( '<link rel="preload" as="image" href="%s" media="(max-width: 47.99em)" fetchpriority="high">' . "\n", esc_url( $hero['small'] ) );
			printf( '<link rel="preload" as="image" href="%s" media="(min-width: 48em)" fetchpriority="high">' . "\n", esc_url( $hero['full'] ) );
		}
	}

	foreach ( array( 'inter-variable', 'barlow-condensed-600', 'barlow-condensed-700', 'barlow-condensed-800', 'barlow-condensed-900' ) as $font ) {
		printf(
			'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin>' . "\n",
			esc_url( RX_THEME_URI . '/assets/fonts/' . $font . '.woff2' )
		);
	}
}
add_action( 'wp_head', 'rx_theme_preload_critical_assets', 2 );
