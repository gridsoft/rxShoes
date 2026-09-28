<?php
/**
 * Order details
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/order/order-details.php,
 * based on core version 10.9.0. Shown on My Account → View order and the
 * order-received page. Changes from core, to match the checkout order
 * review (woocommerce/checkout/review-order.php):
 * - items grouped "Your rotation" / "Other items" with the same header
 *   rows (template-parts/cart/group-header.php), from
 *   rx_theme_order_grouping() (inc/order-details.php);
 * - the table carries the checkout table's look via .rx-order-review;
 * - each totals row is classed by its key (order-total, payment_method…)
 *   so the total can be styled like the checkout's.
 * Downloads, hooks, actions and customer details are core's.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.9.0
 *
 * @var bool $show_downloads Controls whether the downloads table should be rendered.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WooCommerce.Commenting.CommentHooks.MissingHookComment -- template override: WooCommerce's own hooks and variables, kept by their core names.

$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! $order ) {
	return;
}

$order_items        = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$downloads          = $order->get_downloadable_items();
$actions            = array_filter(
	wc_get_account_orders_actions( $order ),
	function ( $key ) {
		return 'view' !== $key;
	},
	ARRAY_FILTER_USE_KEY
);

// We make sure the order belongs to the user. This will also be true if the user is a guest, and the order belongs to a guest (userID === 0).
$show_customer_details = $order->get_user_id() === get_current_user_id();

$rx_theme_grouping = rx_theme_order_grouping( $order, $order_items );
$rx_theme_group    = '';

if ( $show_downloads ) {
	wc_get_template(
		'order/order-downloads.php',
		array(
			'downloads'  => $downloads,
			'show_title' => true,
		)
	);
}
?>
<section class="woocommerce-order-details">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

	<h2 class="woocommerce-order-details__title"><?php esc_html_e( 'Order details', 'woocommerce' ); ?></h2>

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details rx-order-review">

		<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			do_action( 'woocommerce_order_details_before_order_table_items', $order );

			foreach ( $rx_theme_grouping['items'] as $item_id => $item ) {
				$product = $item->get_product();

				$rx_theme_row_group = isset( $rx_theme_grouping['pair_numbers'][ $item_id ] ) ? 'rotation' : 'other';
				if ( $rx_theme_grouping['pair_numbers'] && $rx_theme_row_group !== $rx_theme_group ) {
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

				wc_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
						'rx_pair_number'     => $rx_theme_grouping['pair_numbers'][ $item_id ] ?? 0,
						'rx_row_group'       => $rx_theme_grouping['pair_numbers'] ? $rx_theme_row_group : '',
					)
				);
			}

			// The free gift (inc/free-gift.php): its own group, after everything else.
			foreach ( $rx_theme_grouping['gifts'] as $item_id => $item ) {
				if ( 'gift' !== $rx_theme_group ) {
					$rx_theme_group = 'gift';
					get_template_part(
						'template-parts/cart/group-header',
						null,
						array(
							'group'   => 'gift',
							'colspan' => 2,
						)
					);
				}

				$product = $item->get_product();
				wc_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
						'rx_pair_number'     => 0,
						'rx_row_group'       => 'gift',
					)
				);
			}

			do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
		</tbody>

		<tfoot>
			<?php
			foreach ( $order->get_order_item_totals() as $key => $total ) {
				?>
					<tr class="<?php echo esc_attr( 'order_total' === $key ? 'order-total' : sanitize_html_class( $key ) ); ?>">
						<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
						<td><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
			}
			?>
			<?php if ( $order->get_customer_note() ) : ?>
				<tr class="customer-note">
					<th><?php esc_html_e( 'Note:', 'woocommerce' ); ?></th>
					<td>
					<?php
					$customer_note = wc_wptexturize_order_note( $order->get_customer_note() );
					echo wp_kses( nl2br( $customer_note ), array( 'br' => array() ) );
					?>
					</td>
				</tr>
			<?php endif; ?>
		</tfoot>
	</table>

	<?php if ( ! empty( $actions ) ) : ?>
		<div class="rx-order-actions">
			<?php
			$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
			foreach ( $actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				if ( empty( $action['aria-label'] ) ) {
					/* translators: %1$s Action name, %2$s Order number. */
					$action_aria_label = sprintf( __( '%1$s order number %2$s', 'woocommerce' ), $action['name'], $order->get_order_number() );
				} else {
					$action_aria_label = $action['aria-label'];
				}
				echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button' . esc_attr( $wp_button_class ) . ' button ' . sanitize_html_class( $key ) . ' order-actions-button " aria-label="' . esc_attr( $action_aria_label ) . '">' . esc_html( $action['name'] ) . '</a>';
				unset( $action_aria_label );
			}
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

<?php
/**
 * Action hook fired after the order details.
 *
 * @since 4.4.0
 * @param WC_Order $order Order data.
 */
do_action( 'woocommerce_after_order_details', $order );

if ( $show_customer_details ) {
	wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
}
