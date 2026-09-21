<?php
/**
 * Custom fields on the product edit screen.
 *
 * Only presentation copy lives here — the "Type label" and "Best for"
 * text shown on the shop card. The "Eligible for bundle" checkbox is a
 * business rule and lives in the rx-core plugin
 * (RX\Core\Bundles\BundleEligibility); the card reads it through the
 * rx_theme_product_is_bundle_eligible filter.
 *
 * "Best for" is a plain per-product textarea, not a WordPress taxonomy:
 * a taxonomy is a set of reusable, filterable terms and can't be a
 * free-text field. If shoppers should later be able to filter the shop
 * by "best for", that needs a real taxonomy instead.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the "Type label" input to the General tab of the product data box.
 */
function rx_theme_product_type_label_field(): void {
	woocommerce_wp_text_input(
		array(
			'id'          => '_rx_type_label',
			'label'       => __( 'Type label', 'rx-theme' ),
			'placeholder' => __( 'e.g. Nike Performance', 'rx-theme' ),
			'desc_tip'    => true,
			'description' => __( 'Small line shown above the product title on shop cards. Leave blank to hide it.', 'rx-theme' ),
		)
	);
}
add_action( 'woocommerce_product_options_general_product_data', 'rx_theme_product_type_label_field' );

/**
 * Add the "Best for" textarea to the General tab, under the type label.
 */
function rx_theme_product_best_for_field(): void {
	woocommerce_wp_textarea_input(
		array(
			'id'          => '_rx_best_for',
			'label'       => __( 'Best for', 'rx-theme' ),
			'placeholder' => __( "Functional Training\nStrength", 'rx-theme' ),
			'desc_tip'    => true,
			'description' => __( 'Shown on shop cards as "Best for: …". Put one item per line and they are joined with a bullet, or type one line of free text. Leave blank to hide it.', 'rx-theme' ),
			'rows'        => 3,
		)
	);
}
add_action( 'woocommerce_product_options_general_product_data', 'rx_theme_product_best_for_field' );

/**
 * Save the "Type label" and "Best for" fields through the product CRUD
 * object (HPOS-safe, no direct post-meta writes). WooCommerce has
 * already verified the product-save nonce and the user's capability
 * before this filter runs.
 *
 * @param WC_Product $product Product being saved.
 */
function rx_theme_save_product_card_fields( WC_Product $product ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WC_Meta_Box_Product_Data::save() before this hook fires.
	if ( isset( $_POST['_rx_type_label'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		$product->update_meta_data( '_rx_type_label', sanitize_text_field( wp_unslash( $_POST['_rx_type_label'] ) ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
	if ( isset( $_POST['_rx_best_for'] ) ) {
		// sanitize_textarea_field() keeps the line breaks, which are the item separators.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		$product->update_meta_data( '_rx_best_for', sanitize_textarea_field( wp_unslash( $_POST['_rx_best_for'] ) ) );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'rx_theme_save_product_card_fields' );
