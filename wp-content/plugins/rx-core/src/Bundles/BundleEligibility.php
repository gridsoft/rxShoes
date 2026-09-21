<?php
/**
 * Per-product "Eligible for bundle" flag.
 *
 * A checkbox in the product edit screen's "Shop card" tab (not General —
 * WooCommerce hides that tab for variable products, i.e. every shoe). It's a business
 * rule, so it lives here rather than in the theme: the theme only asks
 * "is this product bundle-eligible?" through the
 * rx_theme_product_is_bundle_eligible filter (see the theme's
 * inc/woocommerce.php) and this service answers it.
 *
 * Eligible products get the bundle treatment on shop cards (badge,
 * "as low as" pricing, rotation row, and an "Add to bundle" button in
 * place of "Add to basket"). Both buttons are the same standard
 * add-to-cart action; the bundle discount is calculated later from the
 * cart contents.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Bundles;

use RX\Core\Admin\ProductCardTab;
use RX\Core\Service;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the bundle-eligible checkbox and answers the theme's
 * eligibility filter from it.
 */
final class BundleEligibility implements Service {

	/**
	 * Product meta key. Stored as 'yes' / 'no', WooCommerce's checkbox
	 * convention.
	 */
	public const META_KEY = '_rx_bundle_eligible';

	/**
	 * Add the field, its save handler, and the theme-facing filter.
	 */
	public function register(): void {
		add_action( ProductCardTab::FIELDS_ACTION, array( $this, 'render_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_field' ) );
		add_filter( 'rx_theme_product_is_bundle_eligible', array( $this, 'filter_is_eligible' ), 10, 2 );
	}

	/**
	 * Whether a product is flagged bundle-eligible. Anything not
	 * explicitly ticked is treated as not eligible.
	 *
	 * @param WC_Product $product Product to check.
	 */
	public static function is_eligible( WC_Product $product ): bool {
		return 'yes' === $product->get_meta( self::META_KEY );
	}

	/**
	 * Render the checkbox in the General tab of the product data box.
	 */
	public function render_field(): void {
		global $product_object;

		woocommerce_wp_checkbox(
			array(
				'id'          => self::META_KEY,
				'label'       => __( 'Eligible for bundle', 'rx-core' ),
				'description' => __( 'Ticked: the shop card shows the bundle badge, "as low as" pricing and an "Add to bundle" button. Unticked: a normal "Add to basket" button instead.', 'rx-core' ),
				'value'       => $product_object instanceof WC_Product ? $product_object->get_meta( self::META_KEY ) : '',
			)
		);
	}

	/**
	 * Save the checkbox through the product CRUD object. WooCommerce has
	 * already verified the product-save nonce and the user's capability
	 * before this action fires.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public function save_field( WC_Product $product ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WC_Meta_Box_Product_Data::save() before this action fires.
		$product->update_meta_data( self::META_KEY, isset( $_POST[ self::META_KEY ] ) ? 'yes' : 'no' );
	}

	/**
	 * Answer the theme's "is this product bundle-eligible?" question.
	 *
	 * @param bool       $eligible Value so far (the theme's default).
	 * @param WC_Product $product  Product being rendered.
	 */
	public function filter_is_eligible( bool $eligible, WC_Product $product ): bool {
		unset( $eligible ); // The meta is authoritative; the theme default only applies when this plugin is inactive.

		return self::is_eligible( $product );
	}
}
