<?php
/**
 * WooCommerce presentation helpers: shop-loop wrappers and the data the
 * product card (woocommerce/content-product.php) needs.
 *
 * Presentation only. Anything that becomes real business logic — the
 * bundle-eligibility flag, the bundle discount percentage — is exposed
 * through a filter so the rx-core plugin can take it over without the
 * theme changing (see PROJECT.md §4.1: no business logic in the theme).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Swap WooCommerce's default archive wrapper (which assumes a
 * sidebar-and-content layout) for the theme's shared container, so shop
 * pages line up with every other section (see --rx-container-max in
 * assets/css/tokens.css).
 */
function rx_theme_swap_content_wrappers(): void {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	add_action( 'woocommerce_before_main_content', 'rx_theme_content_wrapper_start', 10 );
	add_action( 'woocommerce_after_main_content', 'rx_theme_content_wrapper_end', 10 );
}
add_action( 'init', 'rx_theme_swap_content_wrappers', 20 );

/**
 * Open the shop content wrapper.
 */
function rx_theme_content_wrapper_start(): void {
	echo '<main id="primary" class="rx-shop">';
}

/**
 * Close the shop content wrapper.
 */
function rx_theme_content_wrapper_end(): void {
	echo '</main>';
}

/**
 * Four products per row on desktop (Figma's best-sellers grid and the
 * ~290px card width both point at 4). The actual column count is set by
 * CSS grid — this just keeps WooCommerce's own class in step.
 */
add_filter(
	'loop_shop_columns',
	static function (): int {
		return 4;
	}
);

/**
 * Whether a product shows the bundle treatment ("Bundle eligible"
 * badge, "as low as" pricing, rotation row, and an "Add to bundle"
 * button) — or the plain "Add to basket" button instead. Either way the
 * button is the same standard add-to-cart action.
 *
 * The answer comes from the "Eligible for bundle" checkbox on the
 * product edit screen, which the rx-core plugin owns (a business rule,
 * not presentation — see RX\Core\Bundles\BundleEligibility). It hooks
 * this filter. With that plugin inactive nothing hooks it, so the
 * default is false: every product falls back to the plain buy button.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_is_bundle_eligible( WC_Product $product ): bool {
	/**
	 * Filters whether a product is bundle-eligible.
	 *
	 * @param bool       $eligible Default false; rx-core answers from the product checkbox.
	 * @param WC_Product $product  Product being rendered.
	 */
	return (bool) apply_filters( 'rx_theme_product_is_bundle_eligible', false, $product );
}

/**
 * Fallback for the top bundle discount tier when nothing has been set
 * in the Customizer. 45 is the number the Figma cards' own maths uses
 * ($199 → $109.45 in a 3-pack is exactly 45% off).
 */
function rx_theme_bundle_default_discount_percent(): float {
	return 45.0;
}

/**
 * The top (3-pack) bundle discount, as a percentage (e.g. 45.0).
 *
 * Set under Appearance > Customize > Bundle Discount. The Customizer
 * control is registered as an *option* setting, not a theme_mod, so the
 * value lives in wp_options as `rx_bundle_max_discount_percent` and
 * survives a theme switch — and the rx-core pricing engine can read the
 * same option without depending on this theme. The client's 30/45 vs.
 * 40/55 tier numbers are still unresolved (PROJECT.md §6.2); this is
 * the one number the shop card uses.
 */
function rx_theme_bundle_max_discount_percent(): float {
	$stored  = get_option( 'rx_bundle_max_discount_percent', '' );
	$percent = '' === $stored ? rx_theme_bundle_default_discount_percent() : (float) $stored;

	/**
	 * Filters the best (3-pack) bundle discount percentage, e.g. so the
	 * pricing engine can override the Customizer value.
	 *
	 * @param float $percent Value from the Customizer option (or the default).
	 */
	return (float) apply_filters( 'rx_theme_bundle_max_discount_percent', $percent );
}

/**
 * Sanitize the bundle discount % from the Customizer: a number clamped
 * to 0–100 (a discount outside that range is never valid).
 *
 * @param mixed $value Raw submitted value.
 */
function rx_theme_sanitize_percent( $value ): float {
	return min( 100.0, max( 0.0, (float) $value ) );
}

/**
 * The card's "Best for:" text, from the product's "Best for" field
 * (the "Shop card" tab on the product edit screen, owned by rx-core —
 * RX\Core\Admin\ProductCardCopyFields; this reads its `_rx_best_for`
 * meta key). One item per line is joined with a
 * bullet — "Functional Training • Strength" — so an admin can list
 * items on separate lines instead of typing the separators; free text
 * on a single line is shown as typed. Empty string when not set.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_best_for( WC_Product $product ): string {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $product->get_meta( '_rx_best_for' ) );
	$lines = array_filter( array_map( 'trim', is_array( $lines ) ? $lines : array() ) );

	return implode( ' • ', $lines );
}

/**
 * Format an amount as the Figma shows it: currency symbol, amount,
 * currency code — "$199 AUD" / "$109.45 AUD". Whole-dollar amounts
 * drop the cents when $trim_zero is true.
 *
 * @param float $amount    Amount to format.
 * @param bool  $trim_zero Drop ".00" from whole amounts.
 * @param bool  $with_code Append the currency code (AUD).
 */
function rx_theme_format_money( float $amount, bool $trim_zero = false, bool $with_code = true ): string {
	$decimals = ( $trim_zero && floor( $amount ) === $amount ) ? 0 : 2;
	$symbol   = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
	$number   = number_format( $amount, $decimals, wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );

	return $symbol . $number . ( $with_code ? ' ' . get_woocommerce_currency() : '' );
}

/**
 * The product's price, as a float, for display maths. For variable
 * products WooCommerce's active price is the lowest variation price,
 * which is what a "from" style card should show.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_price( WC_Product $product ): float {
	return (float) $product->get_price();
}

/**
 * Price after the top bundle tier's discount.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_bundle_price( WC_Product $product ): float {
	$discount = rx_theme_bundle_max_discount_percent();

	return round( rx_theme_product_price( $product ) * ( 1 - $discount / 100 ), 2 );
}

/**
 * The product's primary category (first non-default one). Drives the
 * badge on the card, per the client: "the category span should come
 * from product category".
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_primary_category( WC_Product $product ): ?WP_Term {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( ! is_array( $terms ) ) {
		return null;
	}

	$default_id = (int) get_option( 'default_product_cat' );

	foreach ( $terms as $term ) {
		if ( (int) $term->term_id !== $default_id ) {
			return $term;
		}
	}

	return null;
}

/**
 * How many colour options the product has (terms of the global Colour
 * attribute, pa_colour) — the "6 Colours" pill on the card.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_colour_count( WC_Product $product ): int {
	$terms = wc_get_product_terms( $product->get_id(), 'pa_colour', array( 'fields' => 'ids' ) );

	return is_array( $terms ) ? count( $terms ) : 0;
}

/**
 * The small "type" line above the title ("NIKE PERFORMANCE"), from the
 * Type label field in the "Shop card" tab of the product edit screen
 * (owned by rx-core — RX\Core\Admin\ProductCardCopyFields; this reads
 * its `_rx_type_label` meta key). Empty string when not set.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_type_label( WC_Product $product ): string {
	return (string) $product->get_meta( '_rx_type_label' );
}

/**
 * PLACEHOLDER rating for the card's star line. Reviews are out of
 * scope for this build (PROJECT.md §15 Q8), so there's no real data to
 * show — this generates a stable, plausible-looking value per product
 * (derived from its ID, so it doesn't change between page loads) purely
 * so the design can be built and judged. Replace with real data, or
 * remove the star line, before launch: showing invented ratings on a
 * live store is misleading and, in Australia, a consumer-law risk.
 *
 * @param WC_Product $product Product being rendered.
 * @return array{rating:string,count:int}
 */
function rx_theme_product_placeholder_rating( WC_Product $product ): array {
	$seed = crc32( 'rx-placeholder-rating-' . $product->get_id() );

	return array(
		'rating' => number_format( 4.3 + ( $seed % 8 ) / 10, 1 ),
		'count'  => 24 + ( ( $seed >> 8 ) % 176 ),
	);
}
