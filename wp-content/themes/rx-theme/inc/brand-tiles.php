<?php
/**
 * Homepage "Shop by Brand" section (Figma section 6, "Official Partners")
 * — data helpers.
 *
 * The tiles are the store's product brands (WooCommerce Brands, managed
 * under Products > Brands), not separate content. A brand's logo is its
 * native "Thumbnail" on the brand edit screen (term meta `thumbnail_id`);
 * a brand without one falls back to its name set in the display font.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The brands that have at least one product, most products first (ties
 * alphabetically), as WP_Term objects. Empty when the Brands taxonomy
 * isn't available.
 *
 * @return WP_Term[]
 */
function rx_theme_brands_with_products(): array {
	static $brands = null;

	if ( null !== $brands ) {
		return $brands;
	}

	$brands = array();

	if ( taxonomy_exists( 'product_brand' ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_brand',
				'hide_empty' => true,
			)
		);

		if ( is_array( $terms ) ) {
			usort(
				$terms,
				static function ( WP_Term $a, WP_Term $b ): int {
					$rx_theme_by_count = $b->count <=> $a->count;

					return 0 !== $rx_theme_by_count ? $rx_theme_by_count : strnatcasecmp( $a->name, $b->name );
				}
			);
			$brands = $terms;
		}
	}

	return $brands;
}

/**
 * A brand's logo attachment ID (its WooCommerce Brands thumbnail), or 0.
 *
 * @param WP_Term $brand Brand term.
 */
function rx_theme_brand_logo_id( WP_Term $brand ): int {
	return (int) get_term_meta( $brand->term_id, 'thumbnail_id', true );
}
