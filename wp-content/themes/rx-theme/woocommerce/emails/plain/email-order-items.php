<?php
/**
 * Email Order Items (plain text)
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/emails/plain/email-order-items.php,
 * based on core version 10.8.0. Same grouping as the HTML version
 * (woocommerce/emails/email-order-items.php): "YOUR ROTATION", "OTHER
 * ITEMS", "FREE WITH YOUR ORDER", with "Pair N" labels and a
 * "Size • Colour" line per item.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.Security.EscapeOutput.OutputNotEscaped -- template override: core's hooks/variables; plain-text email body, no HTML context.

$rx_theme_grouping = rx_theme_order_grouping( $order, $items );
$rx_theme_rows     = array();

foreach ( $rx_theme_grouping['items'] as $item_id => $item ) {
	$rx_theme_rows[] = array( $item_id, $item, isset( $rx_theme_grouping['pair_numbers'][ $item_id ] ) ? 'rotation' : 'other' );
}
foreach ( $rx_theme_grouping['gifts'] as $item_id => $item ) {
	$rx_theme_rows[] = array( $item_id, $item, 'gift' );
}

$rx_theme_show_groups = count( array_unique( array_column( $rx_theme_rows, 2 ) ) ) > 1 || $rx_theme_grouping['pair_numbers'];
$rx_theme_current     = '';

foreach ( $rx_theme_rows as list( $item_id, $item, $rx_theme_group ) ) :
	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		continue;
	}

	if ( $rx_theme_show_groups && $rx_theme_group !== $rx_theme_current ) {
		$rx_theme_current = $rx_theme_group;

		if ( 'rotation' === $rx_theme_group ) {
			echo strtoupper( __( 'Your rotation', 'rx-theme' ) ) . ' — ' . $rx_theme_grouping['rotation_note'] . "\n\n";
		} elseif ( 'gift' === $rx_theme_group ) {
			echo strtoupper( __( 'Free with your order', 'rx-theme' ) ) . "\n\n";
		} else {
			echo strtoupper( __( 'Other items', 'rx-theme' ) ) . ' — ' . __( 'Full price — not in the rotation discount', 'rx-theme' ) . "\n\n";
		}
	}

	$product          = $item->get_product();
	$sku              = is_object( $product ) ? $product->get_sku() : '';
	$purchase_note    = is_object( $product ) ? $product->get_purchase_note() : '';
	$rx_theme_parent  = $product && $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
	$rx_theme_parent  = $rx_theme_parent instanceof WC_Product ? $rx_theme_parent : null;
	$rx_theme_pair    = (int) ( $rx_theme_grouping['pair_numbers'][ $item_id ] ?? 0 );
	$rx_theme_details = $item instanceof WC_Order_Item_Product ? rx_theme_cart_item_details( rx_theme_order_item_as_cart_item( $item ), false ) : array();

	if ( $rx_theme_pair ) {
		/* translators: %d: rotation pair number. */
		echo sprintf( __( 'Pair %d', 'rx-theme' ), $rx_theme_pair ) . ': ';
	}

	/** This filter is documented in woocommerce/templates/emails/plain/email-order-items.php */
	$product_name = html_entity_decode( wp_strip_all_tags( apply_filters( 'woocommerce_order_item_name', $rx_theme_parent ? $rx_theme_parent->get_name() : $item->get_name(), $item, false ) ), ENT_QUOTES, 'UTF-8' );
	/** This filter is documented in woocommerce/templates/emails/plain/email-order-items.php */
	$quantity = apply_filters( 'woocommerce_email_order_item_quantity', $item->get_quantity(), $item );
	if ( '' !== $quantity ) {
		$product_name .= ' × ' . $quantity;
	}
	$price = 'gift' === $rx_theme_group ? __( 'Free', 'rx-theme' ) : wp_kses( $order->get_formatted_line_subtotal( $item ), array() );

	// Plain text: no HTML escaping (it would turn "&" back into "&amp;").
	echo str_pad( $product_name, 40 ) . ' ' . str_pad( html_entity_decode( $price, ENT_QUOTES, 'UTF-8' ), 20, ' ', STR_PAD_LEFT ) . "\n";

	if ( $rx_theme_details ) {
		echo implode( ' • ', $rx_theme_details ) . "\n";
	}
	if ( $show_sku && $sku ) {
		echo '(#' . $sku . ")\n";
	}

	/** This action is documented in woocommerce/templates/emails/plain/email-order-items.php */
	do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );

	if ( $item instanceof WC_Order_Item_Product ) {
		foreach ( rx_theme_order_item_extra_meta( $item ) as $rx_theme_meta ) {
			echo '- ' . wp_strip_all_tags( $rx_theme_meta->display_key ) . ': ' . wp_strip_all_tags( (string) $rx_theme_meta->display_value ) . "\n";
		}
	}

	/** This action is documented in woocommerce/templates/emails/plain/email-order-items.php */
	do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );

	if ( $show_purchase_note && $purchase_note ) {
		echo "\n" . do_shortcode( wp_kses_post( $purchase_note ) );
	}
	echo "\n\n";
endforeach;
