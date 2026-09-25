<?php
/**
 * Cart Page
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/cart/cart.php, based on core
 * version 11.0.0. Same presentation as the checkout order review
 * (woocommerce/checkout/review-order.php) and the mini cart: rotation
 * pairs listed first under a "Your rotation" header, other items under
 * "Other items". Each line is laid out as the bundle builder's pair card
 * (see the comment above the row below). Thumbnail, subtotal and coupon
 * markup and the core hooks/filters are kept; the quantity field and
 * the AJAX remove are replaced by "Edit size / colour" and "Replace"
 * (client request, 2026-09-24).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and loop variables, kept by their core names.

$rx_theme_grouping     = rx_theme_cart_grouping();
$rx_theme_pair_numbers = $rx_theme_grouping['pair_numbers'];
$rx_theme_group        = '';

do_action( 'woocommerce_before_cart' ); ?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<thead>
			<tr>
				<th scope="col" class="product-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove item', 'woocommerce' ); ?></span></th>
				<th scope="col" class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Thumbnail image', 'woocommerce' ); ?></span></th>
				<th scope="col" class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th scope="col" class="product-price"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
				<th scope="col" class="product-quantity"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
				<th scope="col" class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( $rx_theme_grouping['items'] as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				/**
				 * Filter whether this cart item is visible in the cart.
				 *
				 * @since 2.1.0
				 * @param bool   $visible     Whether the cart item is visible. Default true.
				 * @param array  $cart_item     The cart item data.
				 * @param string $cart_item_key The cart item key.
				 */
				$visible = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

				if ( $_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible ) {
					/**
					 * Filter the product name.
					 *
					 * @since 2.1.0
					 * @param string $product_name Name of the product in the cart.
					 * @param array $cart_item The product in the cart.
					 * @param string $cart_item_key Key for the product in the cart.
					 */
					$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

					$rx_theme_parent_id = $_product->get_parent_id();
					$rx_theme_parent    = $rx_theme_parent_id ? wc_get_product( $rx_theme_parent_id ) : $_product;
					$rx_theme_parent    = $rx_theme_parent instanceof WC_Product ? $rx_theme_parent : $_product;
					$rx_theme_row_group = isset( $rx_theme_pair_numbers[ $cart_item_key ] ) ? 'rotation' : 'other';

					if ( $rx_theme_pair_numbers && $rx_theme_row_group !== $rx_theme_group ) {
						$rx_theme_group = $rx_theme_row_group;
						get_template_part(
							'template-parts/cart/group-header',
							null,
							array(
								'group'   => $rx_theme_group,
								'note'    => $rx_theme_grouping['rotation_note'],
								'colspan' => 6,
							)
						);
					}
					?>
					<?php
					/*
					 * The row is laid out as the bundle builder's pair card
					 * (template-parts/bundle-builder/pair-card.php), reusing its
					 * rx-bundle-pair__* classes. No quantity field (one pair per
					 * line, as on the pair card — a quantity above 1 still shows in
					 * the specs) and no plain remove: the actions are "Edit size /
					 * colour", which opens the pair card's own variation picker in
					 * place of the card body (same checkbox toggle; its fields submit
					 * through a form printed after the cart, since a form can't nest
					 * inside it — inc/cart-page.php), and "Replace", which
					 * removes the line and sends the shopper to pick another.
					 */
					$rx_theme_pair_no  = $rx_theme_pair_numbers[ $cart_item_key ] ?? 0;
					$rx_theme_series   = rx_theme_bundle_builder_gender_series_label( $rx_theme_parent );
					$rx_theme_type     = rx_theme_product_type_label( $rx_theme_parent );
					$rx_theme_best_for = rx_theme_product_best_for( $rx_theme_parent );
					$rx_theme_colour   = rx_theme_bundle_builder_configured_colour( $cart_item );
					$rx_theme_size     = rx_theme_bundle_builder_configured_size( $cart_item );
					$rx_theme_sku      = $_product->get_sku() ? $_product->get_sku() : $rx_theme_parent->get_sku();
					$rx_theme_stock    = rx_theme_bundle_builder_stock_state( $_product );
					$rx_theme_summary  = ( ! $rx_theme_size && ! $rx_theme_colour ) ? rx_theme_bundle_builder_variation_summary( $cart_item ) : '';
					$rx_theme_editable = rx_theme_cart_item_is_editable( $cart_item );
					$rx_theme_toggle   = 'rx-cart-edit-toggle-' . $cart_item_key;

					if ( ! $rx_theme_pair_no ) {
						$rx_theme_price_note = '';
					} elseif ( $rx_theme_grouping['tier_percent'] > 0 ) {
						/* translators: %s: discount percentage applied to the rotation. */
						$rx_theme_price_note = sprintf( __( '(-%s%% in rotation)', 'rx-theme' ), rx_theme_format_percent( $rx_theme_grouping['tier_percent'] ) );
					} else {
						/* translators: %s: 2-pack discount percentage. */
						$rx_theme_price_note = sprintf( __( '(Eligible for -%s%%)', 'rx-theme' ), rx_theme_format_percent( rx_theme_bundle_two_pack_discount_percent() ) );
					}
					?>
					<tr class="woocommerce-cart-form__cart-item rx-cart-card <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) . ( $rx_theme_pair_numbers ? ' rx-review-row--' . $rx_theme_row_group : '' ) ); ?>">

						<td class="rx-cart-card__head">
							<?php if ( $rx_theme_editable ) : ?>
								<input type="checkbox" id="<?php echo esc_attr( $rx_theme_toggle ); ?>" class="rx-bundle-pair__edit-toggle rx-cart-card__edit-toggle">
							<?php endif; ?>
							<div class="rx-bundle-pair__head-badges">
								<?php if ( $rx_theme_pair_no ) : ?>
									<span class="rx-badge rx-badge--lime rx-bundle-pair__complete-badge">
										<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" fill="#111"/><path d="m8 12.5 2.8 2.8L16 9.5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
										<?php echo esc_html( sprintf( /* translators: %d: pair number. */ __( 'Pair %d • Complete', 'rx-theme' ), $rx_theme_pair_no ) ); ?>
									</span>
								<?php elseif ( rx_theme_bundle_offer_is_active() ) : ?>
									<?php // "Full price" only means something next to discounted pairs; with the offer off every line is a plain product. ?>
									<span class="rx-badge"><?php esc_html_e( 'Full price', 'rx-theme' ); ?></span>
								<?php endif; ?>
								<?php if ( $rx_theme_series ) : ?>
									<span class="rx-badge"><?php echo esc_html( $rx_theme_series ); ?></span>
								<?php endif; ?>
							</div>
						</td>

						<td class="product-thumbnail">
							<?php if ( $rx_theme_type ) : ?>
								<span class="rx-bundle-pair__type-badge"><?php echo esc_html( $rx_theme_type ); ?></span>
							<?php endif; ?>
							<?php
							/**
							 * Filter the product thumbnail displayed in the WooCommerce cart.
							 *
							 * @param string $thumbnail     The HTML for the product image.
							 * @param array  $cart_item     The cart item data.
							 * @param string $cart_item_key Unique key for the cart item.
							 *
							 * @since 2.1.0
							 */
							$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );

							if ( ! $product_permalink ) {
								echo '<span class="rx-bundle-pair__image">' . $thumbnail . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core thumbnail markup, as in WooCommerce's template.
							} else {
								printf( '<a class="rx-bundle-pair__image" href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core thumbnail markup, as in WooCommerce's template.
							}
							?>
							<?php if ( $rx_theme_colour ) : ?>
								<p class="rx-bundle-pair__colour">
									<span class="rx-bundle-pair__colour-dot" style="<?php echo esc_attr( rx_theme_colour_swatch_style( $rx_theme_colour ) ); ?>"></span>
									<?php echo esc_html( $rx_theme_colour->name ); ?>
								</p>
							<?php endif; ?>
						</td>

						<td role="rowheader" class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
							<p class="rx-bundle-pair__title">
							<?php
							/*
							 * The parent's name ("Puma Fuse 3.0"), not the variation's
							 * auto-generated one ("Puma Fuse 3.0 - 7, Royal Blue") — the
							 * size/colour have their own fields, as on the pair card.
							 */
							if ( ! $product_permalink ) {
								echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $rx_theme_parent->get_name(), $cart_item, $cart_item_key ) );
							} else {
								/**
								 * This filter is documented above.
								 *
								 * @since 2.1.0
								 */
								echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $rx_theme_parent->get_name() ), $cart_item, $cart_item_key ) );
							}
							?>
							</p>
							<?php if ( $rx_theme_best_for ) : ?>
								<p class="rx-bundle-pair__bestfor"><?php echo esc_html( $rx_theme_best_for ); ?></p>
							<?php endif; ?>

							<?php do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key ); ?>

							<div class="rx-bundle-pair__specs">
								<?php if ( $rx_theme_size ) : ?>
									<div class="rx-bundle-pair__spec">
										<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'Configured size', 'rx-theme' ); ?></span>
										<strong><?php echo esc_html( $rx_theme_size ); ?></strong>
									</div>
								<?php elseif ( $rx_theme_summary ) : ?>
									<div class="rx-bundle-pair__spec">
										<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'Options', 'rx-theme' ); ?></span>
										<strong><?php echo esc_html( $rx_theme_summary ); ?></strong>
									</div>
								<?php endif; ?>
								<?php if ( $rx_theme_sku ) : ?>
									<div class="rx-bundle-pair__spec">
										<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'SKU', 'rx-theme' ); ?></span>
										<strong><?php echo esc_html( $rx_theme_sku ); ?></strong>
									</div>
								<?php endif; ?>
								<div class="rx-bundle-pair__spec">
									<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'Stock', 'rx-theme' ); ?></span>
									<strong class="rx-bundle-pair__stock<?php echo $rx_theme_stock['is_ok'] ? '' : ' rx-bundle-pair__stock--low'; ?>"><?php echo esc_html( $rx_theme_stock['label'] ); ?></strong>
								</div>
								<?php if ( (int) $cart_item['quantity'] > 1 ) : ?>
									<div class="rx-bundle-pair__spec">
										<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'Qty', 'rx-theme' ); ?></span>
										<strong><?php echo esc_html( (string) (int) $cart_item['quantity'] ); ?></strong>
									</div>
									<div class="rx-bundle-pair__spec">
										<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'Price each', 'rx-theme' ); ?></span>
										<strong><?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core price markup, as in WooCommerce's template. ?></strong>
									</div>
								<?php endif; ?>
							</div>

							<?php
							// Other item meta data (attributes are shown in the specs above).
							$rx_theme_extra_data = rx_theme_cart_item_extra_data( $cart_item );
							if ( $rx_theme_extra_data ) {
								wc_get_template( 'cart/cart-item-data.php', array( 'item_data' => $rx_theme_extra_data ) );
							}

							// Backorder notification.
							if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
								echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
							}
							?>
						</td>

						<td class="rx-cart-card__actions">
							<div class="rx-bundle-pair__actions">
								<?php if ( $rx_theme_editable ) : ?>
									<label class="rx-bundle-pair__action rx-bundle-pair__action--button" for="<?php echo esc_attr( $rx_theme_toggle ); ?>">
										<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M4 6h10M4 12h6M4 18h12"/><circle cx="17" cy="6" r="2" fill="currentColor"/><circle cx="13" cy="12" r="2" fill="currentColor"/><circle cx="19" cy="18" r="2" fill="currentColor"/></svg>
										<?php esc_html_e( 'Edit size / colour', 'rx-theme' ); ?>
									</label>
								<?php endif; ?>
								<?php
								/*
								 * "Replace" = WooCommerce's own nonce'd remove URL, with
								 * _wp_http_referer set to where the shopper picks the
								 * replacement (WC_Form_Handler redirects there after the
								 * remove): the bundle builder's open slot for a rotation
								 * pair, the shop for anything else. A plain link, not the
								 * AJAX .product-remove one, since it leaves the page.
								 */
								$rx_theme_replace_to = $rx_theme_pair_no ? home_url( '/build-a-bundle/' ) : wc_get_page_permalink( 'shop' );
								?>
								<a class="rx-bundle-pair__action rx-bundle-pair__action--divided" href="<?php echo esc_url( add_query_arg( '_wp_http_referer', rawurlencode( $rx_theme_replace_to ), wc_get_cart_remove_url( $cart_item_key ) ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'Replace %s', 'rx-theme' ), $rx_theme_parent->get_name() ) ); ?>">
									<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M17 2l4 4-4 4M3 11V9a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v2a4 4 0 0 1-4 4H3"/></svg>
									<?php esc_html_e( 'Replace', 'rx-theme' ); ?>
								</a>
							</div>
						</td>

						<?php if ( $rx_theme_editable ) : ?>
							<td class="rx-cart-card__edit">
								<?php
								/*
								 * The bundle builder's inline picker, shown in place of
								 * the card body while the toggle above is checked. Its
								 * fields belong to the external form printed after the
								 * cart (inc/cart-page.php), not the cart form.
								 */
								get_template_part(
									'template-parts/bundle-builder/pair-edit-panel',
									null,
									array(
										'entry'       => array(
											'key'       => $cart_item_key,
											'cart_item' => $cart_item,
											'product'   => $_product,
											'parent'    => $rx_theme_parent,
										),
										'toggle_id'   => $rx_theme_toggle,
										'form_id'     => rx_theme_cart_edit_form_id( $cart_item_key ),
										'button_text' => __( 'Update size / colour', 'rx-theme' ),
										'id_prefix'   => 'rx-cart-edit-' . substr( $cart_item_key, 0, 8 ) . '-',
									)
								);
								?>
							</td>
						<?php endif; ?>

						<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
							<span class="rx-bundle-pair__price">
								<?php
									echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup, unchanged from WooCommerce's template.
								?>
							</span>
							<?php if ( $rx_theme_price_note ) : ?>
								<span class="rx-bundle-pair__eligible"><?php echo esc_html( $rx_theme_price_note ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php
				}
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>

			<tr>
				<td colspan="6" class="actions">

					<?php if ( wc_coupons_enabled() ) { ?>
						<div class="coupon">
							<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label> <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" /> <button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
					<?php } ?>

					<button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>

					<?php do_action( 'woocommerce_cart_actions' ); ?>

					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</td>
			</tr>

			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		</tbody>
	</table>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

<div class="cart-collaterals">
	<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'woocommerce_cart_collaterals' );
	?>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
