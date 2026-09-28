<?php
/**
 * View Order
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/myaccount/view-order.php,
 * based on core version 10.1.0. The "Order #N was placed on … and is
 * currently …" sentence becomes a summary strip (order, date, status
 * badge, payment method, total), with a back link to the orders list.
 * Order updates and the woocommerce_view_order hook (which prints the
 * order details and the PayID payment box) are core's.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and variables, kept by their core names.

$notes = $order->get_customer_order_notes();
?>
<a class="rx-order-back" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">← <?php esc_html_e( 'All orders', 'rx-theme' ); ?></a>

<div class="rx-order-summary">
	<div class="rx-order-summary__head">
		<h2 class="rx-order-summary__number">
			<?php
			/* translators: %s: order number. */
			echo esc_html( sprintf( __( 'Order #%s', 'rx-theme' ), $order->get_order_number() ) );
			?>
		</h2>
		<span class="rx-order-status rx-order-status--<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
	</div>
	<dl class="rx-order-summary__facts">
		<div>
			<dt><?php esc_html_e( 'Placed', 'rx-theme' ); ?></dt>
			<dd><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></dd>
		</div>
		<?php if ( $order->get_payment_method_title() ) : ?>
			<div>
				<dt><?php esc_html_e( 'Payment', 'rx-theme' ); ?></dt>
				<dd><?php echo esc_html( $order->get_payment_method_title() ); ?></dd>
			</div>
		<?php endif; ?>
		<div>
			<dt><?php esc_html_e( 'Items', 'rx-theme' ); ?></dt>
			<dd><?php echo esc_html( (string) $order->get_item_count() ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Total', 'rx-theme' ); ?></dt>
			<dd class="rx-order-summary__total"><?php echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></dd>
		</div>
	</dl>
</div>

<?php if ( $notes ) : ?>
	<h2><?php esc_html_e( 'Order updates', 'woocommerce' ); ?></h2>
	<ol class="woocommerce-OrderUpdates commentlist notes">
		<?php foreach ( $notes as $note ) : ?>
		<li class="woocommerce-OrderUpdate comment note">
			<div class="woocommerce-OrderUpdate-inner comment_container">
				<div class="woocommerce-OrderUpdate-text comment-text">
					<p class="woocommerce-OrderUpdate-meta meta"><?php echo date_i18n( esc_html__( 'l jS \o\f F Y, h:ia', 'woocommerce' ), strtotime( $note->comment_date ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<div class="woocommerce-OrderUpdate-description description">
						<?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?>
					</div>
				</div>
			</div>
		</li>
		<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php do_action( 'woocommerce_view_order', $order_id ); ?>
