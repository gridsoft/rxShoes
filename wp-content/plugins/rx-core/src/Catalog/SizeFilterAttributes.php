<?php
/**
 * Two extra global product attributes used only for shop-page filtering:
 * "Men's size" and "Women's size" (pa_mens-size / pa_womens-size).
 *
 * They exist alongside the purchasable pa_size attribute, not instead of
 * it. pa_size is what a shopper actually selects to add to cart, and it
 * carries both sizing systems together on one option (e.g. "US Men 4 /
 * US Women 5.5") because a men's and a women's size that describe the
 * same physical pair share one SKU and one stock count — see the import
 * command's is_size_like()/pairing logic. Splitting that into two
 * independently-selectable purchase options would let a shopper buy
 * "Men's 4" and "Women's 5.5" as if they were different stock, which
 * they aren't.
 *
 * pa_mens-size / pa_womens-size solve a different problem: letting the
 * shop page filter by "Men's size 5" or "Women's size 6.5" as their own
 * facets. They're assigned at the product level only (never per
 * variation, never variation=true), so they can't be selected as an
 * add-to-cart option — see ProductImportCommand::build_size_filter_attribute().
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Catalog;

use RX\Core\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures the two filter-only attribute taxonomies exist.
 */
final class SizeFilterAttributes implements Service {

	/**
	 * Attribute slugs (without the 'pa_' prefix WooCommerce adds).
	 */
	public const MENS_SIZE_SLUG   = 'mens-size';
	public const WOMENS_SIZE_SLUG = 'womens-size';

	/**
	 * Runs before WC_Post_Types::register_taxonomies() (hooked on 'init'
	 * at priority 5), so an attribute created here is registered as a
	 * real taxonomy in the same request it's first created in.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'ensure_attributes_exist' ), 4 );
	}

	/**
	 * Create the two attributes if they don't already exist. Safe to call
	 * on every request — wc_attribute_taxonomy_id_by_name() is a cheap
	 * cached lookup, and this only writes on the one request that finds
	 * either missing.
	 */
	public function ensure_attributes_exist(): void {
		$this->ensure_attribute( self::MENS_SIZE_SLUG, __( "Men's size", 'rx-core' ) );
		$this->ensure_attribute( self::WOMENS_SIZE_SLUG, __( "Women's size", 'rx-core' ) );
	}

	/**
	 * Create one global attribute taxonomy if missing.
	 */
	private function ensure_attribute( string $slug, string $label ): void {
		if ( ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
			return; // WooCommerce not loaded yet.
		}

		if ( wc_attribute_taxonomy_id_by_name( $slug ) ) {
			return;
		}

		wc_create_attribute(
			array(
				'name'         => $label,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
	}
}
