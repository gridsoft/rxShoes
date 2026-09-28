<?php
/**
 * Order Item Details
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/order/order-details-item.php,
 * based on core version 5.2.0. The product cell shows the same line as
 * the checkout order review (woocommerce/checkout/review-order.php):
 * thumbnail, "Pair N: Best for" label for rotation pairs, the parent
 * product name linked to the product, quantity, and the chosen size and
 * colour. Refunded quantities, item meta hooks, other plugins' item meta
 * and the purchase note are kept from core.
 *
 * Extra args from order/order-details.php: rx_pair_number (0 when not a
 * rotation pair), rx_row_group ('rotation', 'other', 'gift' for the free
 * gift — labelled "Free gift", total "Free" — or '' ungrouped).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and variables, kept by their core names.

if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
	return;
}

$rx_theme_pair_number = (int) ( $rx_pair_number ?? 0 );
$rx_theme_row_group   = (string) ( $rx_row_group ?? '' );
$rx_theme_parent      = $product && $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
$rx_theme_parent      = $rx_theme_parent instanceof WC_Product ? $rx_theme_parent : null;
$rx_theme_details     = $item instanceof WC_Order_Item_Product ? rx_theme_cart_item_details( rx_theme_order_item_as_cart_item( $item ), false ) : array();
$rx_theme_extra_meta  = $item instanceof WC_Order_Item_Product ? rx_theme_order_item_extra_meta( $item ) : array();

$rx_theme_row_class = apply_filters( 'woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order );
if ( '' !== $rx_theme_row_group ) {
	$rx_theme_row_class .= ' rx-review-row--' . $rx_theme_row_group;
}
?>
<tr class="<?php echo esc_attr( $rx_theme_row_class ); ?>">

	<td class="woocommerce-table__product-name product-name">
		<?php
		$is_visible        = $product && $product->is_visible();
		$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $order );
		$rx_theme_name     = $rx_theme_parent ? $rx_theme_parent->get_name() : $item->get_name();

		$qty          = $item->get_quantity();
		$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

		if ( $refunded_qty ) {
			$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
		} else {
			$qty_display = esc_html( $qty );
		}
		?>
		<div class="rx-review-item">
			<span class="rx-review-item__thumb">
				<?php echo wp_kses_post( $product ? $product->get_image( 'woocommerce_gallery_thumbnail' ) : wc_placeholder_img( 'woocommerce_gallery_thumbnail' ) ); ?>
			</span>
			<div class="rx-review-item__text">
				<?php if ( 'gift' === $rx_theme_row_group ) : ?>
					<span class="rx-review-item__label"><?php esc_html_e( 'Free gift', 'rx-theme' ); ?></span>
				<?php elseif ( $rx_theme_pair_number && $rx_theme_parent ) : ?>
					<span class="rx-review-item__label"><?php echo esc_html( rx_theme_bundle_pair_label( $rx_theme_parent, $rx_theme_pair_number ) ); ?></span>
				<?php endif; ?>
				<span class="rx-review-item__name">
					<?php
					echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $product_permalink ? sprintf( '<a href="%s">%s</a>', $product_permalink, esc_html( $rx_theme_name ) ) : esc_html( $rx_theme_name ), $item, $is_visible ) );
					echo apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $qty_display ) . '</strong>', $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</span>
				<?php if ( $rx_theme_details ) : ?>
					<span class="rx-review-item__meta"><?php echo esc_html( implode( ' • ', $rx_theme_details ) ); ?></span>
				<?php endif; ?>
				<?php
				do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );

				if ( $rx_theme_extra_meta ) :
					?>
					<dl class="variation">
						<?php foreach ( $rx_theme_extra_meta as $rx_theme_meta ) : ?>
							<dt><?php echo wp_kses_post( $rx_theme_meta->display_key ); ?>:</dt>
							<dd><?php echo wp_kses_post( wp_strip_all_tags( (string) $rx_theme_meta->display_value ) ); ?></dd>
						<?php endforeach; ?>
					</dl>
					<?php
				endif;

				do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
				?>
			</div>
		</div>
	</td>

	<td class="woocommerce-table__product-total product-total">
		<?php if ( 'gift' === $rx_theme_row_group ) : ?>
			<?php esc_html_e( 'Free', 'rx-theme' ); ?>
		<?php else : ?>
			<?php echo $order->get_formatted_line_subtotal( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</td>

</tr>

<?php if ( $show_purchase_note && $purchase_note ) : ?>

<tr class="woocommerce-table__product-purchase-note product-purchase-note">

	<td colspan="2"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>

</tr>

<?php endif; ?>
