<?php
/**
 * Review order table
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/checkout/review-order.php,
 * based on core version 11.0.0. Only the product cell changes: instead of
 * the bare variation title, each line shows the same details as the mini
 * cart (template-parts/mini-cart/item.php) — thumbnail, "Pair N: Best
 * for" label for rotation pairs, the parent product name, and the chosen
 * size and colour. Everything else (visibility filter, quantity and
 * subtotal filters, totals, fees, shipping) is core's markup unchanged,
 * so the AJAX order-review refresh keeps working as normal.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and loop variables, kept by their core names.

/*
 * Rotation pairs first, grouped under their own header (pair count +
 * discount tier), then everything else under "Other items" — shared with
 * the cart page (rx_theme_cart_grouping(), inc/bundle-builder.php).
 */
$rx_theme_grouping     = rx_theme_cart_grouping();
$rx_theme_cart_items   = $rx_theme_grouping['items'];
$rx_theme_pair_numbers = $rx_theme_grouping['pair_numbers'];
$rx_theme_group        = '';
?>
<table class="shop_table woocommerce-checkout-review-order-table">
	<thead>
		<tr>
			<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( $rx_theme_cart_items as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			/**
			 * Filter whether this cart item is visible in the checkout review order table.
			 *
			 * @since 2.1.0
			 * @param bool   $visible       Whether the cart item is visible. Default true.
			 * @param array  $cart_item     The cart item data.
			 * @param string $cart_item_key The cart item key.
			 */
			$visible = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );

			if ( $_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible ) {
				$rx_theme_parent_id = $_product->get_parent_id();
				$rx_theme_parent    = $rx_theme_parent_id ? wc_get_product( $rx_theme_parent_id ) : $_product;
				$rx_theme_parent    = $rx_theme_parent instanceof WC_Product ? $rx_theme_parent : $_product;
				$rx_theme_details   = rx_theme_cart_item_details( $cart_item, false );

				$rx_theme_extra_data = rx_theme_cart_item_extra_data( $cart_item );

				$rx_theme_row_group = isset( $rx_theme_pair_numbers[ $cart_item_key ] ) ? 'rotation' : 'other';
				if ( $rx_theme_pair_numbers && $rx_theme_row_group !== $rx_theme_group ) {
					$rx_theme_group = $rx_theme_row_group;
					get_template_part(
						'template-parts/cart/group-header',
						null,
						array(
							'group'   => $rx_theme_group,
							'note'    => $rx_theme_grouping['rotation_note'],
							'colspan' => 2,
						)
					);
				}
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) . ( $rx_theme_pair_numbers ? ' rx-review-row--' . $rx_theme_row_group : '' ) ); ?>">
					<td class="product-name">
						<div class="rx-review-item">
							<span class="rx-review-item__thumb"><?php echo wp_kses_post( $_product->get_image( 'woocommerce_gallery_thumbnail' ) ); ?></span>
							<div class="rx-review-item__text">
								<?php if ( isset( $rx_theme_pair_numbers[ $cart_item_key ] ) ) : ?>
									<span class="rx-review-item__label"><?php echo esc_html( rx_theme_bundle_pair_label( $rx_theme_parent, $rx_theme_pair_numbers[ $cart_item_key ] ) ); ?></span>
								<?php endif; ?>
								<span class="rx-review-item__name">
									<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $rx_theme_parent->get_name(), $cart_item, $cart_item_key ) ); ?>
									<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<?php if ( $rx_theme_details ) : ?>
									<span class="rx-review-item__meta"><?php echo esc_html( implode( ' • ', $rx_theme_details ) ); ?></span>
								<?php endif; ?>
								<?php
								if ( $rx_theme_extra_data ) {
									wc_get_template( 'cart/cart-item-data.php', array( 'item_data' => $rx_theme_extra_data ) );
								}
								?>
							</div>
						</div>
					</td>
					<td class="product-total">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>
	<tfoot>

		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</tfoot>
</table>
