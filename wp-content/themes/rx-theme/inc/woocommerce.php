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
	// Outside the offer's run window nothing is eligible (inc/bundle-offer.php).
	if ( ! rx_theme_bundle_offer_is_active() ) {
		return false;
	}

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
 * Fallback for the 2-pack bundle discount when nothing has been set in
 * the Customizer. 35 is the client's stated default (2026-09-21) — note
 * it matches neither Figma's 30% (most places) nor its 40% (hero copy);
 * see the open question in TODO.md #3.
 */
function rx_theme_bundle_default_two_pack_discount_percent(): float {
	return 35.0;
}

/**
 * The 2-pack bundle discount, as a percentage (e.g. 35.0) — what a
 * customer gets for buying two, as opposed to the top (3-pack) tier.
 *
 * Same storage story as the 3-pack value: an *option* setting, stored
 * as `rx_bundle_two_pack_discount_percent`, edited under Appearance >
 * Customize > Bundle Discount. Nothing on the shop card displays it
 * yet (the card only advertises the 3-pack price); it's stored and
 * readable now so the cart-level discount calculation can use both
 * tiers later.
 */
function rx_theme_bundle_two_pack_discount_percent(): float {
	$stored  = get_option( 'rx_bundle_two_pack_discount_percent', '' );
	$percent = '' === $stored ? rx_theme_bundle_default_two_pack_discount_percent() : (float) $stored;

	/**
	 * Filters the 2-pack bundle discount percentage, e.g. so the pricing
	 * engine can override the Customizer value.
	 *
	 * @param float $percent Value from the Customizer option (or the default).
	 */
	return (float) apply_filters( 'rx_theme_bundle_two_pack_discount_percent', $percent );
}

/**
 * A percentage the way it reads in copy: "45", "35", "12.5" — no
 * trailing zeros.
 *
 * @param float $percent Percentage, e.g. 45.0.
 */
function rx_theme_format_percent( float $percent ): string {
	return rtrim( rtrim( number_format( $percent, 2, '.', '' ), '0' ), '.' );
}

/**
 * The placeholders admins can type into homepage copy fields, mapped to
 * what they print. The tier percentages always come from Appearance >
 * Customize > Bundle Discount, so changing that setting updates every
 * mention at once instead of leaving stale numbers in the copy.
 *
 * The money tokens work out the example basket in the Power Rotation
 * calculator: the base is the number in its "original total" field
 * ($650.00 AUD), less the tier's discount.
 *
 * @return array<string,string> Token (with braces) => replacement.
 */
function rx_theme_bundle_token_values(): array {
	static $cache = array();

	$two   = rx_theme_bundle_two_pack_discount_percent();
	$three = rx_theme_bundle_max_discount_percent();
	$key   = $two . '|' . $three . '|' . (string) get_theme_mod( 'rx_rotation_calc_original_total', '' );

	if ( ! isset( $cache[ $key ] ) ) {
		$fields = rx_theme_all_mod_fields();
		$raw    = (string) get_theme_mod( 'rx_rotation_calc_original_total', $fields['rx_rotation_calc_original_total']['default'] ?? '' );
		$base   = (float) preg_replace( '/[^0-9.]/', '', $raw );
		$money  = static function ( float $percent ) use ( $base ): array {
			$total = round( $base * ( 1 - $percent / 100 ), 2 );

			return array(
				'total'   => $base > 0 ? rx_theme_format_money( $total, false, false ) : '',
				'savings' => $base > 0 ? rx_theme_format_money( round( $base - $total, 2 ), false, false ) : '',
			);
		};
		$m2     = $money( $two );
		$m3     = $money( $three );

		$cache[ $key ] = array(
			'{two_pack}'           => rx_theme_format_percent( $two ),
			'{three_pack}'         => rx_theme_format_percent( $three ),
			'{two_pack_total}'     => $m2['total'],
			'{two_pack_savings}'   => $m2['savings'],
			'{three_pack_total}'   => $m3['total'],
			'{three_pack_savings}' => $m3['savings'],
		);
	}

	return $cache[ $key ];
}

/**
 * Replace the bundle placeholders ({two_pack}, {three_pack}, …) in a
 * piece of copy. Text without a "{" is returned untouched.
 *
 * @param string $text Copy as stored in the Customizer.
 */
function rx_theme_apply_bundle_tokens( string $text ): string {
	if ( false === strpos( $text, '{' ) ) {
		return $text;
	}

	return strtr( $text, rx_theme_bundle_token_values() );
}

/**
 * Help text for the Customizer sections that accept the placeholders.
 */
function rx_theme_bundle_tokens_help(): string {
	return __( 'Discount numbers in these texts come from Appearance > Customize > Bundle Discount. Type {two_pack} or {three_pack} where a percentage should appear (e.g. "Save {three_pack}%"). In the calculator, {three_pack_total} and {three_pack_savings} (also {two_pack_total}, {two_pack_savings}) work out dollar amounts from its "original total".', 'rx-theme' );
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
 * The card's "Best for:" text: the names of the "Best for" terms ticked
 * on the product, joined with a bullet — "Functional Training •
 * Strength". The taxonomy is registered and managed by the rx-core
 * plugin (RXCoreCatalogBestForTaxonomy, key `rx_best_for`); with
 * that plugin inactive the taxonomy doesn't exist and this returns an
 * empty string, so the line is simply hidden.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_product_best_for( WC_Product $product ): string {
	$terms = get_the_terms( $product->get_id(), 'rx_best_for' );

	if ( ! is_array( $terms ) ) {
		return '';
	}

	return implode( ' • ', wp_list_pluck( $terms, 'name' ) );
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
