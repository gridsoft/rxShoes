<?php
/**
 * Per-product copy shown on the shop card: the "Type label" (the small
 * "NIKE PERFORMANCE" line) and the "Best for" text.
 *
 * Both are plain product meta; the theme reads them by key
 * (see rx_theme_product_type_label() / rx_theme_product_best_for()).
 * "Best for" is a textarea, not a WordPress taxonomy — a taxonomy is a
 * set of reusable terms and can't be a free-text field. The trade-off
 * is that shoppers can't filter the shop by it; that would need a real
 * taxonomy instead.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Admin;

use RX\Core\Service;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds and saves the copy field inside the Shop card tab.
 */
final class ProductCardCopyFields implements Service {

	/**
	 * Meta key for the small line above the product title.
	 */
	public const TYPE_LABEL = '_rx_type_label';

	/**
	 * Hook the fields into the tab and the product save.
	 */
	public function register(): void {
		add_action( ProductCardTab::FIELDS_ACTION, array( $this, 'render_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
	}

	/**
	 * Render the field.
	 */
	public function render_fields(): void {
		global $product_object;

		woocommerce_wp_text_input(
			array(
				'id'          => self::TYPE_LABEL,
				'label'       => __( 'Type label', 'rx-core' ),
				'placeholder' => __( 'e.g. Nike Performance', 'rx-core' ),
				'desc_tip'    => true,
				'description' => __( 'Small line shown above the product title on shop cards. Leave blank to hide it.', 'rx-core' ),
				'value'       => $product_object instanceof WC_Product ? $product_object->get_meta( self::TYPE_LABEL ) : '',
			)
		);
	}

	/**
	 * Save the field through the product CRUD object. WooCommerce has
	 * already verified the product-save nonce and the user's capability
	 * before this action fires.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public function save_fields( WC_Product $product ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WC_Meta_Box_Product_Data::save() before this action fires.
		if ( isset( $_POST[ self::TYPE_LABEL ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
			$product->update_meta_data( self::TYPE_LABEL, sanitize_text_field( wp_unslash( $_POST[ self::TYPE_LABEL ] ) ) );
		}
	}
}
