<?php
/**
 * Theme bootstrap.
 *
 * Presentation only — no business logic here. Business logic lives in the
 * rx-core plugin (see wp-content/plugins/rx-core).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RX_THEME_VERSION', '0.1.0' );
define( 'RX_THEME_DIR', get_template_directory() );
define( 'RX_THEME_URI', get_template_directory_uri() );

/**
 * Theme setup: supports, menus, image sizes.
 *
 * Design tokens (colour, type, spacing) come from Figma and belong in
 * theme.json / assets/css/tokens.css, not hard-coded here.
 */
function rx_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// WooCommerce integration. Gallery features off for now — custom PDP
	// gallery arrives with the Figma build (Milestone 3).
	add_theme_support( 'woocommerce' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'rx-theme' ),
			'footer'  => __( 'Footer Menu', 'rx-theme' ),
		)
	);

	load_theme_textdomain( 'rx-theme', RX_THEME_DIR . '/languages' );
}
add_action( 'after_setup_theme', 'rx_theme_setup' );

/**
 * Enqueue theme assets, conditionally.
 */
function rx_theme_enqueue_assets(): void {
	wp_enqueue_style( 'rx-theme-style', get_stylesheet_uri(), array(), RX_THEME_VERSION );
}
add_action( 'wp_enqueue_scripts', 'rx_theme_enqueue_assets' );

/**
 * WooCommerce ships its own front-end stylesheet. The Figma build replaces
 * it entirely, so it's disabled here rather than overridden piecemeal.
 * Re-enable selectively if a WC component's markup is reused as-is.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
