<?php
/**
 * Order details in the checkout's style (client request, 2026-09-25):
 * the order view (My Account → Orders → View, and the order-received
 * page) groups rotation pairs under "Your rotation" and lists the rest
 * under "Other items", with the same thumbnail / pair label / name /
 * size • colour lines as the checkout order review
 * (woocommerce/checkout/review-order.php).
 *
 * The checkout works that grouping out live from the cart. An order has
 * no cart, so the grouping is saved onto the order when it's placed:
 * each rotation line gets its pair number (hidden item meta) and the
 * order keeps the rotation note ("3 pairs • 45% off applied"). Orders
 * placed before this existed fall back to re-deriving it from the
 * products' bundle-eligible flag and the order's discount line.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Order item meta: 1-based rotation pair number. */
const RX_THEME_ORDER_PAIR_META = '_rx_rotation_pair';

/** Order meta: rotation discount percentage applied at checkout. */
const RX_THEME_ORDER_PERCENT_META = '_rx_rotation_percent';

/**
 * Cart grouping for the order being created, worked out once per
 * checkout (the line-item hook below runs once per line).
 */
function rx_theme_checkout_grouping(): array {
	static $grouping = null;

	if ( null === $grouping ) {
		$grouping = rx_theme_cart_grouping();
	}

	return $grouping;
}

/**
 * Save each rotation line's pair number onto its order item.
 *
 * @param WC_Order_Item_Product $item          Order item being created.
 * @param string                $cart_item_key Its cart line key.
 */
function rx_theme_save_order_item_pair( WC_Order_Item_Product $item, string $cart_item_key ): void {
	$pair_numbers = rx_theme_checkout_grouping()['pair_numbers'];

	if ( isset( $pair_numbers[ $cart_item_key ] ) ) {
		$item->add_meta_data( RX_THEME_ORDER_PAIR_META, $pair_numbers[ $cart_item_key ], true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'rx_theme_save_order_item_pair', 10, 2 );

/**
 * The rotation discount percentage actually charged on an order, read
 * from its discount fee line ("Rotation bundle discount (-45%)", added
 * by rx_theme_bundle_builder_apply_tier_discount()); 0 when there is none
 * (single pair, or the offer window had closed).
 *
 * @param WC_Order $order Order.
 */
function rx_theme_order_rotation_fee_percent( WC_Order $order ): float {
	foreach ( $order->get_fees() as $fee ) {
		if ( (float) $fee->get_total() < 0 && preg_match( '/\(-([\d.]+)%\)/', $fee->get_name(), $matches ) ) {
			return (float) $matches[1];
		}
	}

	return 0.0;
}

/**
 * Save the rotation discount percentage onto the order. Fee lines are
 * already on the order when this hook runs.
 *
 * @param WC_Order $order Order being created.
 */
function rx_theme_save_order_rotation( WC_Order $order ): void {
	if ( rx_theme_checkout_grouping()['pair_numbers'] ) {
		$order->update_meta_data( RX_THEME_ORDER_PERCENT_META, (string) rx_theme_order_rotation_fee_percent( $order ) );
	}
}
add_action( 'woocommerce_checkout_create_order', 'rx_theme_save_order_rotation' );

/**
 * Hide the pair-number meta from the admin order screen's item meta
 * list (it's shown as the "Pair N" label instead).
 *
 * @param string[] $hidden Hidden meta keys.
 * @return string[]
 */
function rx_theme_hide_order_item_pair_meta( array $hidden ): array {
	$hidden[] = RX_THEME_ORDER_PAIR_META;

	return $hidden;
}
add_filter( 'woocommerce_hidden_order_itemmeta', 'rx_theme_hide_order_item_pair_meta' );

/**
 * Grouping for an order's item table: rotation lines first (in pair
 * order), then everything else, plus the rotation header's note.
 *
 * @param WC_Order                 $order Order.
 * @param array<int,WC_Order_Item> $items The order's line items.
 * The free gift is left out of "items" and returned under "gifts".
 *
 * @return array{items: array<int,WC_Order_Item>, gifts: array<int,WC_Order_Item>, pair_numbers: array<int,int>, rotation_note: string}
 */
function rx_theme_order_grouping( WC_Order $order, array $items ): array {
	$gifts        = array_filter( $items, 'rx_theme_order_item_is_free_gift' );
	$items        = array_diff_key( $items, $gifts );
	$pair_numbers = array();

	foreach ( $items as $item_id => $item ) {
		$pair = (int) $item->get_meta( RX_THEME_ORDER_PAIR_META );
		if ( $pair > 0 ) {
			$pair_numbers[ $item_id ] = $pair;
		}
	}

	$percent = (float) $order->get_meta( RX_THEME_ORDER_PERCENT_META );

	// Older orders: nothing saved, so re-derive from the products and the discount line.
	if ( ! $pair_numbers ) {
		foreach ( $items as $item_id => $item ) {
			$product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;
			$parent  = $product && $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;

			if ( $parent instanceof WC_Product && rx_theme_product_is_bundle_eligible( $parent ) ) {
				$pair_numbers[ $item_id ] = count( $pair_numbers ) + 1;
			}
		}

		$percent = rx_theme_order_rotation_fee_percent( $order );
	}

	if ( ! $pair_numbers ) {
		return array(
			'items'         => $items,
			'gifts'         => $gifts,
			'pair_numbers'  => array(),
			'rotation_note' => '',
		);
	}

	asort( $pair_numbers );
	$ordered = array();
	foreach ( array_keys( $pair_numbers ) as $item_id ) {
		$ordered[ $item_id ] = $items[ $item_id ];
	}
	$ordered += array_diff_key( $items, $pair_numbers );

	$pairs = 0;
	foreach ( array_keys( $pair_numbers ) as $item_id ) {
		$pairs += (int) $items[ $item_id ]->get_quantity();
	}

	if ( $percent > 0 ) {
		$note = sprintf(
			/* translators: 1: pairs in the rotation, 2: discount percentage. */
			_n( '%1$d pair • %2$s%% off applied', '%1$d pairs • %2$s%% off applied', $pairs, 'rx-theme' ),
			$pairs,
			rx_theme_format_percent( $percent )
		);
	} else {
		/* translators: %d: pairs in the rotation. */
		$note = sprintf( _n( '%d pair', '%d pairs', $pairs, 'rx-theme' ), $pairs );
	}

	return array(
		'items'         => $ordered,
		'gifts'         => $gifts,
		'pair_numbers'  => $pair_numbers,
		'rotation_note' => $note,
	);
}

/**
 * Cart-item-shaped array for one order line, so the checkout's own
 * detail helpers (rx_theme_cart_item_details()) read it unchanged: the
 * chosen attributes come from the order item's pa_* meta.
 *
 * @param WC_Order_Item_Product $item Order item.
 * @return array{data: WC_Product|null, quantity: int, variation: array<string,string>}
 */
function rx_theme_order_item_as_cart_item( WC_Order_Item_Product $item ): array {
	$variation = array();

	foreach ( $item->get_meta_data() as $meta ) {
		if ( is_string( $meta->key ) && str_starts_with( $meta->key, 'pa_' ) && is_scalar( $meta->value ) ) {
			$variation[ 'attribute_' . $meta->key ] = (string) $meta->value;
		}
	}

	return array(
		'data'      => $item->get_product() ? $item->get_product() : null,
		'quantity'  => (int) $item->get_quantity(),
		'variation' => $variation,
	);
}

/**
 * The order item's own meta other than its variation attributes (those
 * are already on the "Size • Colour" line) — e.g. data other plugins
 * added — formatted as WooCommerce would.
 *
 * @param WC_Order_Item_Product $item Order item.
 * @return array<int,object{display_key:string,display_value:string}>
 */
function rx_theme_order_item_extra_meta( WC_Order_Item_Product $item ): array {
	return array_values(
		array_filter(
			$item->get_formatted_meta_data(),
			static fn( $meta ) => ! str_starts_with( (string) $meta->key, 'pa_' )
		)
	);
}
