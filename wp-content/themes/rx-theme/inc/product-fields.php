<?php
/**
 * Custom fields on the product edit screen.
 *
 * Only presentation copy lives here (the "Type label" shown on the shop
 * card). The bundle-eligible checkbox is a business rule and will be
 * added by the rx-core plugin, not this theme — the card reads it
 * through the rx_theme_product_is_bundle_eligible filter.
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
 * Save the "Type label" through the product CRUD object (HPOS-safe,
 * no direct post-meta writes). WooCommerce has already verified the
 * product-save nonce and the user's capability before this filter runs.
 *
 * @param WC_Product $product Product being saved.
 */
function rx_theme_save_product_type_label( WC_Product $product ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WC_Meta_Box_Product_Data::save() before this hook fires.
	if ( isset( $_POST['_rx_type_label'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		$product->update_meta_data( '_rx_type_label', sanitize_text_field( wp_unslash( $_POST['_rx_type_label'] ) ) );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'rx_theme_save_product_type_label' );
