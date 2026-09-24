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

require RX_THEME_DIR . '/inc/template-tags.php';
require RX_THEME_DIR . '/inc/customizer.php';
require RX_THEME_DIR . '/inc/taxonomy-fields.php';
require RX_THEME_DIR . '/inc/woocommerce.php';
require RX_THEME_DIR . '/inc/archive-header.php';
require RX_THEME_DIR . '/inc/shop-filters.php';
require RX_THEME_DIR . '/inc/shop-sidebar.php';
require RX_THEME_DIR . '/inc/shop-pagination.php';
require RX_THEME_DIR . '/inc/single-product.php';
require RX_THEME_DIR . '/inc/customizer-bundle.php';
require RX_THEME_DIR . '/inc/biomechanics.php';
require RX_THEME_DIR . '/inc/brand-tiles.php';
require RX_THEME_DIR . '/inc/community.php';
require RX_THEME_DIR . '/inc/best-sellers.php';
require RX_THEME_DIR . '/inc/bundle-builder.php';
require RX_THEME_DIR . '/inc/mini-cart.php';
require RX_THEME_DIR . '/inc/cart-page.php';

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
 *
 * Both stylesheets version off their own filemtime(), not the static
 * RX_THEME_VERSION constant — this theme is under active development
 * with style.css changing every session, and a fixed version string
 * means browsers (and any caching layer) keep serving a stale copy
 * after every single edit until it happens to expire on its own. The
 * theme's enqueued scripts already do this (see e.g.
 * rx_theme_enqueue_variation_swatches_script()); the stylesheets hadn't
 * been brought in line with that.
 */
function rx_theme_enqueue_assets(): void {
	$tokens_file = RX_THEME_DIR . '/assets/css/tokens.css';
	$style_file  = RX_THEME_DIR . '/style.css';

	wp_enqueue_style( 'rx-theme-tokens', RX_THEME_URI . '/assets/css/tokens.css', array(), file_exists( $tokens_file ) ? (string) filemtime( $tokens_file ) : RX_THEME_VERSION );
	wp_enqueue_style( 'rx-theme-style', get_stylesheet_uri(), array( 'rx-theme-tokens' ), file_exists( $style_file ) ? (string) filemtime( $style_file ) : RX_THEME_VERSION );
}
add_action( 'wp_enqueue_scripts', 'rx_theme_enqueue_assets' );

/**
 * WooCommerce ships its own front-end stylesheet. The Figma build replaces
 * it entirely, so it's disabled here rather than overridden piecemeal.
 * Re-enable selectively if a WC component's markup is reused as-is.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Static fallback for the primary nav until an admin sets one up under
 * Appearance > Menus. Labels match the Figma header (Home/Bundle Builder
 * frames) — Men/Women/Unisex/Brands/Sale. These will become WooCommerce
 * product category links once the catalog exists (Milestone 2); plain
 * anchors for now so the header isn't empty before that.
 */
function rx_theme_primary_nav_fallback(): void {
	$items = array(
		__( 'Men', 'rx-theme' )    => '/product-category/men/',
		__( 'Women', 'rx-theme' )  => '/product-category/women/',
		__( 'Unisex', 'rx-theme' ) => '/product-category/unisex/',
		__( 'Brands', 'rx-theme' ) => '/brands/',
		__( 'Sale', 'rx-theme' )   => '/sale/',
	);

	echo '<nav class="rx-nav"><ul class="rx-nav__list">';
	foreach ( $items as $label => $path ) {
		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( home_url( $path ) ),
			esc_html( $label )
		);
	}
	echo '</ul></nav>';
}
