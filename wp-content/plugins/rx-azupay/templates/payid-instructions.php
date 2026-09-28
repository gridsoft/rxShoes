<?php
/**
 * PayID payment instructions — order-received page and My Account order
 * view.
 *
 * Override by copying to yourtheme/rx-azupay/payid-instructions.php.
 * The data-rx-payid-* attributes are what assets/js/azupay-payid.js
 * hooks into (copy buttons, live status); keep them in an override.
 *
 * @package RX\AzuPay
 *
 * @var WC_Order $order        Order.
 * @var string   $state        'waiting', 'paid' or 'closed'.
 * @var string   $payid        PayID to pay to.
 * @var float    $amount       Amount to pay (AUD).
 * @var string   $reference    Description the customer's bank shows for the PayID.
 * @var string   $checkout_url AzuPay hosted payment page (QR code), or ''.
 * @var string   $expires_text Formatted expiry date/time.
 * @var string   $status_url   REST URL polled for payment status.
 * @var string   $simulate_url Simulator-mode endpoint base, or '' (normal mode).
 */

defined( 'ABSPATH' ) || exit;

$rx_azupay_amount_plain = number_format( (float) $amount, 2, '.', '' );
?>
<section class="rx-payid rx-payid--<?php echo esc_attr( $state ); ?>" data-rx-payid data-rx-payid-state="<?php echo esc_attr( $state ); ?>" data-rx-payid-status-url="<?php echo esc_url( $status_url ); ?>">

	<div class="rx-payid__paid" <?php echo 'paid' === $state ? '' : 'hidden'; ?>>
		<h2 class="rx-payid__title"><?php esc_html_e( 'Payment received', 'rx-azupay' ); ?></h2>
		<p><?php esc_html_e( 'Thanks — your PayID payment has arrived and your order is confirmed. We\'ll email you when it ships.', 'rx-azupay' ); ?></p>
	</div>

	<?php if ( 'closed' === $state ) : ?>
		<h2 class="rx-payid__title"><?php esc_html_e( 'PayID expired', 'rx-azupay' ); ?></h2>
		<p><?php esc_html_e( 'The payment window for this order has closed and the order was cancelled. Nothing was charged. If you still want these items, please place a new order.', 'rx-azupay' ); ?></p>
	<?php elseif ( 'waiting' === $state ) : ?>
		<div class="rx-payid__waiting">
			<h2 class="rx-payid__title">
				<?php
				printf(
					/* translators: %s: amount. */
					esc_html__( 'Pay %s by PayID to confirm your order', 'rx-azupay' ),
					wp_kses_post( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) )
				);
				?>
			</h2>
			<p class="rx-payid__lead"><?php esc_html_e( 'Your order is reserved. Open your banking app, choose Pay → PayID, and use the details below.', 'rx-azupay' ); ?></p>

			<dl class="rx-payid__details">
				<div class="rx-payid__row">
					<dt><?php esc_html_e( 'PayID (email)', 'rx-azupay' ); ?></dt>
					<dd>
						<span class="rx-payid__value rx-payid__value--payid"><?php echo esc_html( $payid ); ?></span>
						<button type="button" class="rx-payid__copy" data-rx-payid-copy="<?php echo esc_attr( $payid ); ?>"><?php esc_html_e( 'Copy', 'rx-azupay' ); ?></button>
					</dd>
				</div>
				<div class="rx-payid__row">
					<dt><?php esc_html_e( 'Amount', 'rx-azupay' ); ?></dt>
					<dd>
						<span class="rx-payid__value"><?php echo wp_kses_post( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ); ?></span>
						<button type="button" class="rx-payid__copy" data-rx-payid-copy="<?php echo esc_attr( $rx_azupay_amount_plain ); ?>"><?php esc_html_e( 'Copy', 'rx-azupay' ); ?></button>
					</dd>
				</div>
				<div class="rx-payid__row">
					<dt><?php esc_html_e( 'Your bank will show', 'rx-azupay' ); ?></dt>
					<dd><span class="rx-payid__value"><?php echo esc_html( $reference ); ?></span></dd>
				</div>
			</dl>

			<p class="rx-payid__note">
				<?php
				printf(
					/* translators: %s: expiry date and time. */
					esc_html__( 'Pay the exact amount. This PayID is for this order only and expires %s — unpaid orders are then cancelled.', 'rx-azupay' ),
					'<strong>' . esc_html( $expires_text ) . '</strong>'
				);
				?>
			</p>

			<?php if ( '' !== $checkout_url ) : ?>
				<p><a class="rx-payid__qr" href="<?php echo esc_url( $checkout_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Paying from another device? Show a QR code', 'rx-azupay' ); ?></a></p>
			<?php endif; ?>

			<p class="rx-payid__status" role="status" aria-live="polite" data-rx-payid-status>
				<?php esc_html_e( 'Waiting for your payment… this page updates by itself once it arrives.', 'rx-azupay' ); ?>
			</p>

			<?php if ( '' !== $simulate_url ) : ?>
				<div class="rx-payid__simulator" data-rx-payid-simulate="<?php echo esc_url( $simulate_url ); ?>" data-rx-payid-key="<?php echo esc_attr( $order->get_order_key() ); ?>">
					<strong><?php esc_html_e( 'Simulator mode', 'rx-azupay' ); ?></strong>
					<button type="button" data-rx-payid-simulate-outcome="pay"><?php esc_html_e( 'Simulate payment', 'rx-azupay' ); ?></button>
					<button type="button" data-rx-payid-simulate-outcome="expire"><?php esc_html_e( 'Simulate expiry', 'rx-azupay' ); ?></button>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
