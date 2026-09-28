<?php
/**
 * Free gift display (the one free pair of socks per order; the rule
 * itself — adding it, price 0, not removable — lives in rx-core's
 * Cart\FreeGift). The theme only asks "is this line the free gift?"
 * through filters rx-core answers, then keeps the gift out of the
 * rotation / "Other items" groups and shows it on its own "Free with
 * your order" row in the cart, mini cart, checkout and order views.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a cart line is the free gift.
 *
 * @param array $cart_item One WC()->cart->get_cart() entry.
 */
function rx_theme_cart_item_is_free_gift( array $cart_item ): bool {
	return (bool) apply_filters( 'rx_theme_cart_item_is_free_gift', false, $cart_item );
}

/**
 * Whether an order line is the free gift.
 *
 * @param WC_Order_Item $item Order item.
 */
function rx_theme_order_item_is_free_gift( WC_Order_Item $item ): bool {
	return (bool) apply_filters( 'rx_theme_order_item_is_free_gift', false, $item );
}

/**
 * The cart's free gift lines, keyed by cart item key (normally one).
 *
 * @return array<string,array>
 */
function rx_theme_cart_free_gifts(): array {
	if ( ! WC()->cart ) {
		return array();
	}

	return array_filter( WC()->cart->get_cart(), 'rx_theme_cart_item_is_free_gift' );
}
