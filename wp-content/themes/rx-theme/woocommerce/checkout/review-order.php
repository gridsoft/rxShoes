<?php
/**
 * Review order table
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/checkout/review-order.php,
 * based on core version 11.0.0. Rebuilt for the checkout mockup's left
 * column (inc/checkout.php): "Your selection" as item cards — rotation
 * pairs first under a "Your rotation" header, then "Other items" — and
 * the "RX rotation saving" box with the totals.
 *
 * The root element keeps the woocommerce-checkout-review-order-table
 * class, so WooCommerce's AJAX update still swaps this whole block in
 * place after every change. It's a <div> now, with the totals (subtotal,
 * coupons, shipping choice, fees, tax, total) in a real table inside the
 * saving box; core's row markup, filters and hooks are kept, including
 * the shipping method radios from wc_cart_totals_shipping_html().
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and loop variables, kept by their core names.

$rx_theme_grouping     = rx_theme_cart_grouping();
$rx_theme_pair_numbers = $rx_theme_grouping['pair_numbers'];
$rx_theme_rotation     = rx_theme_checkout_rotation();
$rx_theme_group        = '';
$rx_theme_note         = $rx_theme_grouping['rotation_note'];

// Discount removed by the payment method choice: the rotation header says
// what PayID would give instead of "applied".
if ( $rx_theme_rotation['available'] > 0 && $rx_theme_rotation['percent'] <= 0 ) {
	$rx_theme_note = sprintf(
		/* translators: 1: pairs in the rotation, 2: discount percentage. */
		_n( '%1$d pair • %2$s%% off with PayID', '%1$d pairs • %2$s%% off with PayID', $rx_theme_rotation['pairs'], 'rx-theme' ),
		$rx_theme_rotation['pairs'],
		rx_theme_format_percent( $rx_theme_rotation['available'] )
	);
}
?>
<div class="woocommerce-checkout-review-order-table rx-co-review">
	<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>

	<ul class="rx-co-items">
		<?php
		foreach ( $rx_theme_grouping['items'] as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			/** This filter is documented in woocommerce/templates/checkout/review-order.php. */
			$visible = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );

			if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
				continue;
			}

			$rx_theme_parent_id  = $_product->get_parent_id();
			$rx_theme_parent     = $rx_theme_parent_id ? wc_get_product( $rx_theme_parent_id ) : $_product;
			$rx_theme_parent     = $rx_theme_parent instanceof WC_Product ? $rx_theme_parent : $_product;
			$rx_theme_details    = rx_theme_cart_item_details( $cart_item, false );
			$rx_theme_extra_data = rx_theme_cart_item_extra_data( $cart_item );
			// Chips: the product's "Best for" terms, like the mockup's TRAIN / RUN / LIFT.
			$rx_theme_tags      = get_the_terms( $rx_theme_parent->get_id(), 'rx_best_for' );
			$rx_theme_tags      = is_array( $rx_theme_tags ) ? array_slice( wp_list_pluck( $rx_theme_tags, 'name' ), 0, 2 ) : array();
			$rx_theme_row_group = isset( $rx_theme_pair_numbers[ $cart_item_key ] ) ? 'rotation' : 'other';

			if ( $rx_theme_pair_numbers && $rx_theme_row_group !== $rx_theme_group ) {
				$rx_theme_group = $rx_theme_row_group;
				?>
				<li class="rx-co-items__group rx-co-items__group--<?php echo esc_attr( $rx_theme_group ); ?>">
					<?php if ( 'rotation' === $rx_theme_group ) : ?>
						<span class="rx-co-items__group-title"><span class="rx-review-group__dot" aria-hidden="true"></span><?php esc_html_e( 'Your rotation', 'rx-theme' ); ?></span>
						<span class="rx-co-items__group-note"><?php echo esc_html( $rx_theme_note ); ?></span>
					<?php else : ?>
						<span class="rx-co-items__group-title"><?php esc_html_e( 'Other items', 'rx-theme' ); ?></span>
						<span class="rx-co-items__group-note"><?php esc_html_e( 'Full price — not in the rotation discount', 'rx-theme' ); ?></span>
					<?php endif; ?>
				</li>
				<?php
			}
			?>
			<li class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) . ' rx-co-item' . ( $rx_theme_pair_numbers ? ' rx-co-item--' . $rx_theme_row_group : '' ) ); ?>">
				<span class="rx-co-item__thumb"><?php echo wp_kses_post( $_product->get_image( 'woocommerce_gallery_thumbnail' ) ); ?></span>
				<div class="rx-co-item__text">
					<?php if ( $rx_theme_tags || isset( $rx_theme_pair_numbers[ $cart_item_key ] ) ) : ?>
						<span class="rx-co-item__chips">
							<?php if ( isset( $rx_theme_pair_numbers[ $cart_item_key ] ) ) : ?>
								<?php /* translators: %d: pair number in the rotation. */ ?>
								<span class="rx-co-item__chip rx-co-item__chip--pair"><?php echo esc_html( sprintf( __( 'Pair %d', 'rx-theme' ), $rx_theme_pair_numbers[ $cart_item_key ] ) ); ?></span>
							<?php endif; ?>
							<?php foreach ( $rx_theme_tags as $rx_theme_tag ) : ?>
								<span class="rx-co-item__chip"><?php echo esc_html( $rx_theme_tag ); ?></span>
							<?php endforeach; ?>
						</span>
					<?php endif; ?>
					<span class="rx-co-item__name">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $rx_theme_parent->get_name(), $cart_item, $cart_item_key ) ); ?>
						<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<?php if ( $rx_theme_details ) : ?>
						<span class="rx-co-item__meta"><?php echo esc_html( implode( ' / ', $rx_theme_details ) ); ?></span>
					<?php endif; ?>
					<?php
					if ( $rx_theme_extra_data ) {
						wc_get_template( 'cart/cart-item-data.php', array( 'item_data' => $rx_theme_extra_data ) );
					}
					?>
				</div>
				<span class="rx-co-item__price">
					<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</li>
			<?php
		}
		?>
		<?php
		// The free gift (inc/free-gift.php): its own row, marked free, after everything else.
		foreach ( $rx_theme_grouping['gifts'] as $rx_theme_gift ) :
			$rx_theme_gift_product = $rx_theme_gift['data'] ?? null;
			if ( ! $rx_theme_gift_product instanceof WC_Product ) {
				continue;
			}
			?>
			<li class="rx-co-items__group rx-co-items__group--gift">
				<span class="rx-co-items__group-title"><?php esc_html_e( 'Free with your order', 'rx-theme' ); ?></span>
			</li>
			<li class="cart_item rx-co-item rx-co-item--gift">
				<span class="rx-co-item__thumb"><?php echo wp_kses_post( $rx_theme_gift_product->get_image( 'woocommerce_gallery_thumbnail' ) ); ?></span>
				<div class="rx-co-item__text">
					<span class="rx-co-item__chips"><span class="rx-co-item__chip rx-co-item__chip--gift"><?php esc_html_e( 'Free gift', 'rx-theme' ); ?></span></span>
					<span class="rx-co-item__name"><?php echo esc_html( $rx_theme_gift_product->get_name() ); ?></span>
					<?php if ( $rx_theme_gift_product->get_short_description() ) : ?>
						<span class="rx-co-item__meta"><?php echo esc_html( wp_strip_all_tags( $rx_theme_gift_product->get_short_description() ) ); ?></span>
					<?php endif; ?>
				</div>
				<span class="rx-co-item__price rx-co-item__price--free"><?php esc_html_e( 'Free', 'rx-theme' ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>

	<div class="rx-co-saving<?php echo $rx_theme_rotation['percent'] > 0 ? ' rx-co-saving--active' : ''; ?>">
		<h3 class="rx-co-saving__title">
			<?php if ( $rx_theme_rotation['percent'] > 0 ) : ?>
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
				<?php esc_html_e( 'RX rotation saving', 'rx-theme' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Order summary', 'rx-theme' ); ?>
			<?php endif; ?>
		</h3>

		<?php if ( $rx_theme_rotation['available'] > 0 && $rx_theme_rotation['percent'] <= 0 ) : ?>
			<?php // The chosen payment method doesn't qualify: say so, and offer the way back (PROJECT.md §6.2). ?>
			<div class="rx-co-lost" role="alert">
				<p>
					<strong>
						<?php
						/* translators: %s: discount percentage. */
						echo esc_html( sprintf( __( 'Your %s%% rotation discount has been removed', 'rx-theme' ), rx_theme_format_percent( $rx_theme_rotation['available'] ) ) );
						?>
					</strong>
					<?php esc_html_e( 'The bundle discount only applies when you pay by PayID.', 'rx-theme' ); ?>
				</p>
				<button type="button" class="rx-co-lost__switch" data-rx-switch-payid><?php esc_html_e( 'Switch to PayID', 'rx-theme' ); ?></button>
			</div>
		<?php endif; ?>

		<table class="rx-co-totals">
			<tbody>
				<tr class="cart-subtotal">
					<th><?php echo esc_html( $rx_theme_rotation['percent'] > 0 ? __( 'Original value', 'rx-theme' ) : __( 'Subtotal', 'woocommerce' ) ); ?></th>
					<td><?php wc_cart_totals_subtotal_html(); ?></td>
				</tr>

				<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
					<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
						<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
					<tr class="fee<?php echo (float) $fee->amount < 0 ? ' fee--discount' : ''; ?>">
						<th><?php echo esc_html( $fee->name ); ?></th>
						<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

					<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

					<?php wc_cart_totals_shipping_html(); ?>

					<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

				<?php endif; ?>

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
			</tbody>
			<tfoot>
				<tr class="order-total">
					<th>
						<?php if ( $rx_theme_rotation['percent'] > 0 ) : ?>
							<span class="rx-co-saving__unlocked">
								<?php
								/* translators: %s: discount percentage. */
								echo esc_html( sprintf( __( '%s%% off unlocked', 'rx-theme' ), rx_theme_format_percent( $rx_theme_rotation['percent'] ) ) );
								?>
								<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
							</span>
						<?php endif; ?>
						<span class="rx-co-saving__label"><?php esc_html_e( 'You pay', 'rx-theme' ); ?></span>
					</th>
					<td data-rx-checkout-total><?php wc_cart_totals_order_total_html(); ?></td>
				</tr>
			</tfoot>
		</table>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</div>
</div>
