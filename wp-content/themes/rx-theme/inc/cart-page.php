<?php
/**
 * Cart page "Edit size / colour" (client request, 2026-09-24): the same
 * inline variation picker the bundle builder's pair cards use
 * (template-parts/bundle-builder/pair-edit-panel.php), opened inside the
 * cart card itself by the same pure-CSS checkbox toggle
 * (woocommerce/cart/cart.php).
 *
 * The picker is a real WooCommerce variation add-to-cart carrying
 * rx_bundle_replace_key (swapped in by
 * rx_theme_bundle_builder_swap_variation()), but it sits inside the cart
 * <form>, and forms can't nest. So the panel renders as a <div> with
 * every field tied, via the HTML form attribute, to an empty <form>
 * printed here after the cart (woocommerce_after_cart). Its action is
 * the product page without rx_add_to_rotation, which lands back on the
 * cart (rx_theme_bundle_builder_add_to_cart_redirect()).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The external form id for one cart line's editor.
 *
 * @param string $cart_item_key Cart item key.
 */
function rx_theme_cart_edit_form_id( string $cart_item_key ): string {
	return 'rx-cart-edit-form-' . $cart_item_key;
}

/**
 * Whether a cart line can be edited in place: a variation of a variable
 * product (simple products have no size/colour to change).
 *
 * @param array $cart_item One WC()->cart->get_cart() entry.
 */
function rx_theme_cart_item_is_editable( array $cart_item ): bool {
	$product = $cart_item['data'] ?? null;

	if ( ! $product instanceof WC_Product || ! $product->get_parent_id() ) {
		return false;
	}

	return wc_get_product( $product->get_parent_id() ) instanceof WC_Product_Variable;
}

/**
 * Print the (empty) external form each inline editor's fields submit
 * through, after the cart form.
 */
function rx_theme_cart_edit_forms(): void {
	if ( ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		if ( ! rx_theme_cart_item_is_editable( $cart_item ) ) {
			continue;
		}

		$parent = wc_get_product( $cart_item['data']->get_parent_id() );
		?>
		<form id="<?php echo esc_attr( rx_theme_cart_edit_form_id( $cart_item_key ) ); ?>" class="rx-cart-edit-form" action="<?php echo esc_url( $parent->get_permalink() ); ?>" method="post" enctype="multipart/form-data" hidden></form>
		<?php
	}
}
add_action( 'woocommerce_after_cart', 'rx_theme_cart_edit_forms' );
