<?php
/**
 * Email Order Items
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/emails/email-order-items.php,
 * based on core version 11.0.0. Changes from core, to match the website's
 * order view (woocommerce/order/order-details.php):
 * - items grouped "Your rotation" (with the pairs / discount note),
 *   "Other items" and "Free with your order", from
 *   rx_theme_order_grouping() (inc/order-details.php);
 * - each line: product photo (an email-safe JPEG, inc/emails.php), a
 *   "Pair N" / "Free gift" label, the model name and a "Size • Colour"
 *   line instead of WooCommerce's "Colour: … Size: …" meta list;
 * - the free gift's price reads "Free".
 * Hooks, quantity/refund display, SKU (admin emails) and purchase notes
 * are core's.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.0.0
 *
 * @var WC_Order $order
 * @var array    $items
 * @var bool     $show_sku
 * @var bool     $show_image
 * @var bool     $show_purchase_note
 * @var bool     $plain_text
 * @var bool     $sent_to_admin
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and variables, kept by their core names.

$rx_theme_grouping = rx_theme_order_grouping( $order, $items );
$rx_theme_rows     = array();

foreach ( $rx_theme_grouping['items'] as $item_id => $item ) {
	$rx_theme_rows[] = array( $item_id, $item, isset( $rx_theme_grouping['pair_numbers'][ $item_id ] ) ? 'rotation' : 'other' );
}
foreach ( $rx_theme_grouping['gifts'] as $item_id => $item ) {
	$rx_theme_rows[] = array( $item_id, $item, 'gift' );
}

// Group headers only when there's more than one kind of line (a plain one-item order needs none).
$rx_theme_show_groups = count( array_unique( array_column( $rx_theme_rows, 2 ) ) ) > 1 || $rx_theme_grouping['pair_numbers'];
$rx_theme_current     = '';

foreach ( $rx_theme_rows as list( $item_id, $item, $rx_theme_group ) ) :
	if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
		continue;
	}

	if ( $rx_theme_show_groups && $rx_theme_group !== $rx_theme_current ) :
		$rx_theme_current = $rx_theme_group;
		?>
		<tr class="rx-email-group rx-email-group--<?php echo esc_attr( $rx_theme_group ); ?>">
			<th colspan="3" class="td">
				<?php if ( 'rotation' === $rx_theme_group ) : ?>
					<span class="rx-email-group__title"><?php esc_html_e( 'Your rotation', 'rx-theme' ); ?></span>
					<span class="rx-email-group__note"><?php echo esc_html( $rx_theme_grouping['rotation_note'] ); ?></span>
				<?php elseif ( 'gift' === $rx_theme_group ) : ?>
					<span class="rx-email-group__title"><?php esc_html_e( 'Free with your order', 'rx-theme' ); ?></span>
					<span class="rx-email-group__note"><?php esc_html_e( 'One pair with every order', 'rx-theme' ); ?></span>
				<?php else : ?>
					<span class="rx-email-group__title"><?php esc_html_e( 'Other items', 'rx-theme' ); ?></span>
					<span class="rx-email-group__note"><?php esc_html_e( 'Full price — not in the rotation discount', 'rx-theme' ); ?></span>
				<?php endif; ?>
			</th>
		</tr>
		<?php
	endif;

	$product       = $item->get_product();
	$sku           = is_object( $product ) ? $product->get_sku() : '';
	$purchase_note = is_object( $product ) ? $product->get_purchase_note() : '';
	$rx_theme_parent  = $product && $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
	$rx_theme_parent  = $rx_theme_parent instanceof WC_Product ? $rx_theme_parent : null;
	$rx_theme_pair    = (int) ( $rx_theme_grouping['pair_numbers'][ $item_id ] ?? 0 );
	$rx_theme_details = $item instanceof WC_Order_Item_Product ? rx_theme_cart_item_details( rx_theme_order_item_as_cart_item( $item ), false ) : array();
	$rx_theme_extra   = $item instanceof WC_Order_Item_Product ? rx_theme_order_item_extra_meta( $item ) : array();

	if ( 'gift' === $rx_theme_group ) {
		$rx_theme_label = __( 'Free gift', 'rx-theme' );
	} elseif ( $rx_theme_pair && $rx_theme_parent ) {
		$rx_theme_label = rx_theme_bundle_pair_label( $rx_theme_parent, $rx_theme_pair );
	} else {
		$rx_theme_label = '';
	}

	/** This filter is documented in woocommerce/templates/emails/email-order-items.php */
	$order_item_class = apply_filters( 'woocommerce_order_item_class', 'order_item rx-email-item rx-email-item--' . $rx_theme_group, $item, $order );
	?>
	<tr class="<?php echo esc_attr( $order_item_class ); ?>">
		<td class="td font-family text-align-left" style="word-wrap:break-word;">
			<table class="order-item-data" role="presentation" cellpadding="0" cellspacing="0" border="0">
				<tr>
					<?php if ( $show_image ) : ?>
						<td class="rx-email-item__thumb" style="border:0;padding:0 16px 0 0;">
							<?php
							/** This filter is documented in woocommerce/templates/emails/email-order-items.php */
							echo wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', rx_theme_email_item_image( $product ), $item ) );
							?>
						</td>
					<?php endif; ?>
					<td style="border:0;padding:0;">
						<?php if ( $rx_theme_label ) : ?>
							<span class="rx-email-item__label"><?php echo esc_html( $rx_theme_label ); ?></span><br>
						<?php endif; ?>
						<?php
						/** This filter is documented in woocommerce/templates/emails/email-order-items.php */
						$order_item_name = apply_filters( 'woocommerce_order_item_name', $rx_theme_parent ? $rx_theme_parent->get_name() : $item->get_name(), $item, false );
						echo wp_kses_post( '<h3 class="rx-email-item__name">' . $order_item_name . '</h3>' );

						if ( $show_sku && $sku ) {
							echo '<p class="rx-email-item__meta">' . esc_html( '#' . $sku ) . '</p>';
						}

						if ( $rx_theme_details ) {
							echo '<p class="rx-email-item__meta">' . esc_html( implode( ' • ', $rx_theme_details ) ) . '</p>';
						}

						/** This action is documented in woocommerce/templates/emails/email-order-items.php */
						do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );

						foreach ( $rx_theme_extra as $rx_theme_meta ) {
							echo '<p class="rx-email-item__meta">' . wp_kses_post( $rx_theme_meta->display_key ) . ': ' . esc_html( wp_strip_all_tags( (string) $rx_theme_meta->display_value ) ) . '</p>';
						}

						/** This action is documented in woocommerce/templates/emails/email-order-items.php */
						do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );
						?>
					</td>
				</tr>
			</table>
		</td>
		<td class="td font-family text-align-right rx-email-item__qty">
			<?php
			$qty          = $item->get_quantity();
			$refunded_qty = $order->get_qty_refunded_for_item( $item_id );
			$qty_display  = $refunded_qty ? '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>' : esc_html( $qty );

			/** This filter is documented in woocommerce/templates/emails/email-order-items.php */
			$quantity = apply_filters( 'woocommerce_email_order_item_quantity', $qty_display, $item );
			if ( '' !== $quantity ) {
				echo wp_kses_post( '&times;' . $quantity );
			}
			?>
		</td>
		<td class="td font-family text-align-right rx-email-item__price">
			<?php if ( 'gift' === $rx_theme_group ) : ?>
				<span class="rx-email-item__free"><?php esc_html_e( 'Free', 'rx-theme' ); ?></span>
			<?php else : ?>
				<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
			<?php endif; ?>
		</td>
	</tr>
	<?php if ( $show_purchase_note && $purchase_note ) : ?>
		<tr>
			<td colspan="3" class="font-family text-align-left">
				<?php echo wp_kses_post( wpautop( do_shortcode( $purchase_note ) ) ); ?>
			</td>
		</tr>
	<?php endif; ?>
<?php endforeach; ?>
